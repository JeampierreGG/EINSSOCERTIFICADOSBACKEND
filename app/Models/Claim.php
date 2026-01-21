<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Claim extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_code',
        'tipo_documento',
        'numero_documento',
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'domicilio',
        'telefono',
        'email',
        'padre_nombres',
        'tipo_bien',
        'monto_reclamado',
        'descripcion_bien',
        'tipo_reclamacion',
        'detalle',
        'pedido',
        'status',
        'respuesta_admin',
        'fecha_atencion',
        'acepto_terminos',
    ];

    protected static function booted()
    {
        static::creating(function ($claim) {
            // Generar código único: LIB-YYYYMR-XXXX (Ej: LIB-202601-0001)
            // Para simplicidad y unicidad rápida: RECLAMO-{TIMESTAMP}-{RANDOM}
            // O mejor un formato legible: REC-YYYY-RANDOMABCD
            $year = date('Y');
            $random = strtoupper(substr(uniqid(), -5));
            $claim->ticket_code = "REC-{$year}-{$random}";
        });
    }
}
