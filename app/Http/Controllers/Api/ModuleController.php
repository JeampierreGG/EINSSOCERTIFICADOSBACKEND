<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ModuleController extends Controller
{
    /**
     * Servir el material (PDF) del módulo usando Proxy Robusto.
     * Replica la lógica de CourseController@brochure.
     */
    public function material($id)
    {
        // Limpiar ID si viene con prefijo (e.g. 'mod-17')
        if (!is_numeric($id)) {
            $id = preg_replace('/[^0-9]/', '', $id);
        }

        $module = CourseModule::findOrFail($id);
        $path = $module->pdf_path;

        if (!$path) {
            return response()->json(['message' => 'Material no disponible'], 404);
        }

        // --- INICIO LÓGICA PROXY ROBUSTA (Idéntica a brochure) ---

        // Limpieza de Path (Manejo de Array/JSON de Filament)
        if (is_string($path) && (str_starts_with($path, '[') || str_starts_with($path, '{'))) {
            $decoded = json_decode($path, true);
            $path = is_array($decoded) ? ($decoded[0] ?? array_values($decoded)[0] ?? $path) : $path;
        }
        if (is_array($path)) {
            $path = $path[0] ?? array_values($path)[0] ?? null;
        }

        if (!$path || !is_string($path)) {
             return response()->json(['message' => 'Ruta inválida'], 404);
        }

        // Si es URL completa, extraer path
        if (str_starts_with($path, 'http')) {
             $parsed = parse_url($path);
             $path = ltrim($parsed['path'] ?? '', '/');
        }

        $path = ltrim(trim($path), '/');

        // Fix S3: Remove bucket name from path if present (because driver handles bucket)
        // Only if using S3
        if (config('filesystems.default') === 's3') {
            $bucket = config('filesystems.disks.s3.bucket');
            if ($bucket && str_starts_with($path, $bucket . '/')) {
                $path = substr($path, strlen($bucket) + 1);
            }
        }
        $disk = Storage::disk(config('filesystems.default'));

        // Estrategia de Fuerza Bruta: Probar variantes comunes de encoding
        $candidates = array_unique([
            $path,                          
            urldecode($path),               
            str_replace('%', '%25', $path), 
            str_replace('=', '%3D', $path), 
            str_replace('%3D', '=', $path), 
        ]);

        $finalPath = null;
        foreach ($candidates as $candidate) {
            if ($disk->exists($candidate)) {
                $finalPath = $candidate;
                break;
            }
        }

        if (!$finalPath) {
            // Último intento: Listar directorio
            $dir = dirname($path);
            $base = basename($path);
            
            if ($dir && $dir !== '.' && $dir !== '/') {
                 try {
                     $files = $disk->files($dir);
                     $baseDecoded = urldecode($base);
                     foreach ($files as $f) {
                         if (basename($f) === $base || urldecode(basename($f)) === $baseDecoded) {
                             $finalPath = $f;
                             break;
                         }
                     }
                 } catch (\Throwable $e) {}
            }
        }

        if ($finalPath) {
            try {
                $content = $disk->get($finalPath);
                $mime = 'application/pdf';
                
                // Obtener nombre humanizado idéntico al frontend (humanizeFileName)
                $filename = basename($finalPath);
                $humanName = $filename;

                // 1. Detectar formato Filament meta: -meta[BASE64]-.ext (Regex flexible)
                if (preg_match('/-meta(.+?)-(\.[a-zA-Z0-9]+)$/', $filename, $matches)) {
                    $decoded = base64_decode($matches[1]);
                    if ($decoded) {
                        $humanName = $decoded;
                    }
                } else {
                    // 2. Fallback: Limpieza estándar
                    $humanName = urldecode($humanName);
                    // Quitar prefijos aleatorios (UUIDs o strings >10 chars + guion) generados por Filament/Storage
                    $humanName = preg_replace('/^[a-zA-Z0-9\-_]{10,}-/', '', $humanName);
                }

                $name = $humanName;

                // Soporte para tildes usando RFC 5987 (filename*)
                $asciiName = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
                $asciiName = preg_replace('/[^A-Za-z0-9_\-\. ]/', '', $asciiName);
                $encodedName = rawurlencode($name);

                return response($content, 200, [
                    'Content-Type' => $mime,
                    'Content-Disposition' => "inline; filename=\"{$asciiName}\"; filename*=UTF-8''{$encodedName}",
                    'Cache-Control' => 'no-cache, no-store, must-revalidate',
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Error sirviendo material modulo: ' . $e->getMessage());
            }
        }

        return response()->json(['message' => 'Archivo no encontrado en el servidor'], 404);
    }
}
