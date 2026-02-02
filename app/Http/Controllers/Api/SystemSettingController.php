<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SystemSettingController extends Controller
{
    public function index()
    {
        $settings = SystemSetting::first();

        if (!$settings) {
            return response()->json([
                'header_logo' => null,
                'footer_logo' => null,
                'loading_logo' => null,
                'facebook_url' => null,
                'instagram_url' => null,
                'tiktok_url' => null,
                'youtube_url' => null,
                'x_url' => null,
                'address' => null,
                'phone' => null,
                'email' => null,
            ]);
        }

        return response()->json([
            'header_logo' => $this->generateUrl($settings->header_logo),
            'footer_logo' => $this->generateUrl($settings->footer_logo),
            'loading_logo' => $this->generateUrl($settings->loading_logo),
            'facebook_url' => $settings->facebook_url,
            'instagram_url' => $settings->instagram_url,
            'tiktok_url' => $settings->tiktok_url,
            'youtube_url' => $settings->youtube_url,
            'x_url' => $settings->x_url,
            'address' => $settings->address,
            'phone' => $settings->phone,
            'email' => $settings->email,
        ]);
    }

    private function generateUrl($path)
    {
        if (!$path) return null;

        if (is_string($path) && (str_starts_with($path, '[') || str_starts_with($path, '{'))) {
            $path = json_decode($path, true);
        }

        if (is_array($path)) {
            $path = $path[0] ?? array_values($path)[0] ?? null;
            if (is_array($path)) {
                $path = array_values($path)[0] ?? null;
            }
        }

        if (!$path || !is_string($path)) return null;

        $path = ltrim(trim($path), '/');

        // Force S3 since we are saving to S3 in the Resource
        $disk = 's3';

        try {
            if ($disk === 's3') {
                $url = Storage::disk($disk)->temporaryUrl($path, now()->addMinutes(120)); // Generous expiry for UI elements
            } else {
                $url = Storage::disk($disk)->url($path);
            }
        } catch (\Throwable $e) {
            // Fallback if s3 fails or not configured, though Resource uses it
            try {
                $url = Storage::disk($disk)->url($path);
            } catch (\Throwable $e2) {
                 return null;
            }
        }

        // Fix: Decodificar %3D a = para compatibilidad con Contabo/S3 y filenames de Filament que usan Base64
        return str_replace('%3D', '=', $url);
    }
}
