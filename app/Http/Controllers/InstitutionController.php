<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class InstitutionController extends Controller
{
    public function logo(Institution $institution)
    {
        $path = $institution->logo_path;
        if (!$path || !Storage::disk(config('filesystems.default'))->exists($path)) {
            return response()->noContent(404);
        }

        $disk = Storage::disk(config('filesystems.default'));
        $content = $disk->get($path);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimeMap = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'pdf' => 'application/pdf',
        ];
        $mime = $mimeMap[$extension] ?? 'application/octet-stream';

        return response($content, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=300',
        ]);
    }
}
