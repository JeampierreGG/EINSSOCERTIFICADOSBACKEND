<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\CertificateItem;
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

        $item = CertificateItem::with(['institution', 'certificate.user.profile'])
            ->whereRaw('LOWER(TRIM(code)) = ?', [strtolower($q)])
            ->first();

        if ($item) {
            $institutionName = optional($item->institution)->name ?? '';
            $logoPath = optional($item->institution)->logo_path;
            return response()->json([
                'userName' => (string) optional(optional($item->certificate)->user)->name,
                'certificates' => [[
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
                    'downloadUrl' => url('/api/certificates/'.$item->id.'/download?type=item'),
                ]],
            ]);
        }

        $certAny = Certificate::with(['institution', 'user.profile'])
            ->whereRaw('LOWER(TRIM(code)) = ?', [strtolower($q)])
            ->first();

        if ($certAny) {
            $institutionName = optional($certAny->institution)->name ?? '';
            $logoPath = optional($certAny->institution)->logo_path;
            return response()->json([
                'userName' => (string) optional($certAny->user)->name,
                'certificates' => [[
                    'id' => (string) $certAny->id,
                    'courseTitle' => $certAny->title,
                    'category' => self::mapCategory($certAny->category),
                    'institution' => $institutionName,
                    'hours' => (int) ($certAny->hours ?? 0),
                    'grade' => (int) ($certAny->grade ?? 0),
                    'issueDate' => $certAny->issue_date ? \Carbon\Carbon::parse($certAny->issue_date)->format('d/m/Y') : '',
                    'code' => (string) ($certAny->code ?? ''),
                    'logo' => $logoPath ? url('/api/institutions/'.$certAny->institution_id.'/logo') : null,
                    'filePath' => $certAny->file_path,
                    'downloadUrl' => url('/api/certificates/'.$certAny->id.'/download?type=solo'),
                ]],
            ]);
        }

        $certs = Certificate::with(['institution', 'items.institution', 'user.profile'])
            ->whereHas('user.profile', function ($p) use ($q) {
                $p->where('dni_ce', $q);
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
                    'downloadUrl' => url('/api/certificates/'.$c->id.'/download?type=solo'),
                ];
            } else {
                foreach ($c->items as $it) {
                    $institutionName = optional($it->institution)->name ?? '';
                    $logoPath = optional($it->institution)->logo_path;
                    $results[] = [
                        'id' => (string) $it->id,
                        'courseTitle' => $it->title,
                        'category' => self::mapCategory($it->category),
                        'institution' => $institutionName,
                        'hours' => (int) ($it->hours ?? 0),
                        'grade' => (int) ($it->grade ?? 0),
                        'issueDate' => $it->issue_date ? \Carbon\Carbon::parse($it->issue_date)->format('d/m/Y') : '',
                        'code' => (string) ($it->code ?? ''),
                        'logo' => $logoPath ? url('/api/institutions/'.$it->institution_id.'/logo') : null,
                        'filePath' => $it->file_path,
                        'downloadUrl' => url('/api/certificates/'.$it->id.'/download?type=item'),
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

    public function download(Request $request, string $id)
    {
        $type = $request->query('type', 'solo');
        $record = null;
        if ($type === 'item') {
            $record = CertificateItem::find($id);
        } else {
            $record = Certificate::find($id);
        }
        if (! $record || ! $record->file_path) {
            return response()->json(['message' => 'Archivo no disponible'], 404);
        }
        $nameBase = trim(($record->title ?? 'certificado').' '.($record->code ?? ''));
        $safeName = preg_replace('/[^A-Za-z0-9_\- ]+/','', $nameBase);
        $downloadName = ($safeName !== '' ? $safeName : 'certificado').'.pdf';
        return Storage::disk(config('filesystems.default'))
            ->download($record->file_path, $downloadName);
    }
}
