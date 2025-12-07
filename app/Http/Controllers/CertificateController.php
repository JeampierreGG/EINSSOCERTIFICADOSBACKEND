<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller
{
    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response()->json([
                'userName' => '',
                'certificates' => [],
            ]);
        }

        $certs = Certificate::with(['institution', 'items', 'user.profile'])
            ->where(function ($w) use ($q) {
                $w->whereHas('user.profile', function ($p) use ($q) {
                    $p->where('dni_ce', $q);
                })
                ->orWhere('code', $q);
            })
            ->orderByDesc('issue_date')
            ->get();

        if ($certs->isEmpty()) {
            return response()->json([
                'userName' => '',
                'certificates' => [],
            ]);
        }

        $first = $certs->first();
        $userName = (string) optional($first->user)->name;

        $results = [];
        foreach ($certs as $c) {
            if ($c->type === 'solo') {
                $institutionName = optional($c->institution)->name ?? '';
                $logoPath = optional($c->institution)->logo_path;
                $fileBase = trim(($c->title ?? 'certificado').' '.($c->code ?? ''));
                $results[] = [
                    'id' => (string) $c->id,
                    'courseTitle' => $c->title,
                    'category' => self::mapCategory($c->category),
                    'institution' => $institutionName,
                    'hours' => (int) ($c->hours ?? 0),
                    'grade' => (int) ($c->grade ?? 0),
                    'issueDate' => $c->issue_date ? \Carbon\Carbon::parse($c->issue_date)->format('d/m/Y') : '',
                    'code' => (string) ($c->code ?? ''),
                    'logo' => $logoPath ? url('/api/institutions/'.$c->institution_id.'/logo') : null,
                    'filePath' => $c->file_path,
                ];
            } else {
                foreach ($c->items as $item) {
                    $institutionName = optional($item->institution)->name ?? '';
                    $logoPath = optional($item->institution)->logo_path;
                    $fileBase = trim(($item->title ?? 'certificado').' '.($item->code ?? ''));
                    $results[] = [
                        'id' => (string) $item->id,
                        'courseTitle' => $item->title,
                        'category' => self::mapCategory($item->category),
                        'institution' => $institutionName,
                        'hours' => (int) ($item->hours ?? 0),
                        'grade' => (int) ($item->grade ?? 0),
                    'issueDate' => $item->issue_date ? \Carbon\Carbon::parse($item->issue_date)->format('d/m/Y') : '',
                        'code' => (string) ($item->code ?? ''),
                        'logo' => $logoPath ? url('/api/institutions/'.$item->institution_id.'/logo') : null,
                        'filePath' => $item->file_path,
                    ];
                }
            }
        }

        return response()->json([
            'userName' => $userName,
            'certificates' => $results,
        ]);
    }

    private static function mapCategory(?string $cat): ?string
    {
        return match ($cat) {
            'curso' => 'Curso',
            'modular' => 'Modular',
            'diplomado' => 'Diplomado',
            default => null,
        };
    }
}
