<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ally;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class AllyController extends Controller
{
    /**
     * Devuelve la lista de aliados activos, ordenados por sort_order.
     *
     * ESTRATEGIA DE URL PARA S3/CONTABO:
     * ─────────────────────────────────────────────────────────────
     * 1. Solo se devuelven los campos necesarios (id, logo_url).
     * 2. Se intentan URLs temporales (presignadas) primero para máxima
     *    compatibilidad con Contabo Object Storage.
     * 3. Fallback a URL pública si presignada falla.
     * 4. Se decodifica %3D → = para compatibilidad con nombres de ficheros
     *    generados por Filament (que usa base64 en los nombres).
     * ─────────────────────────────────────────────────────────────
     */
    public function index(): JsonResponse
    {
        $allies = Ally::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $data = $allies->map(fn (Ally $ally) => [
            'id'       => $ally->id,
            'logo_url' => $this->generateUrl($ally->logo_path),
        ])->filter(fn ($item) => $item['logo_url'] !== null)->values();

        return response()->json($data);
    }

    private function generateUrl($path): ?string
    {
        if (!$path) return null;

        // --- Manejo de posibles datos codificados en JSON por Filament ---
        // Filament a veces guarda rutas como JSON array: ["allies/filename.png"]
        if (is_string($path) && (str_starts_with($path, '[') || str_starts_with($path, '{'))) {
            $decoded = json_decode($path, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $path = $decoded;
            }
        }

        // Extraer la ruta real si es un array
        if (is_array($path)) {
            $path = $path[0] ?? array_values($path)[0] ?? null;
            if (is_array($path)) {
                $path = array_values($path)[0] ?? null;
            }
        }

        if (!$path || !is_string($path)) return null;

        // Sanitizar path: quitar slashes iniciales
        $path = ltrim(trim($path), '/');

        try {
            // Intentamos URL temporal presignada (compatible con Contabo S3)
            $url = Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(120));
        } catch (\Throwable $e) {
            try {
                // Fallback: URL pública directa
                $url = Storage::disk('s3')->url($path);
            } catch (\Throwable $e2) {
                return null;
            }
        }

        // Fix crítico: Contabo/S3 + nombres de Filament en base64 usan '=' que
        // puede estar codificado como '%3D' en la URL → decodificar para evitar 403
        return str_replace('%3D', '=', $url);
    }
}
