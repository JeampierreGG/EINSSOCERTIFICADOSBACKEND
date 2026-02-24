<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Institution extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'logo_path',
    ];

    /**
     * Slugs fijos de los tipos de certificado del sistema.
     */
    const SLUG_CIP      = 'cip';
    const SLUG_EINSSO   = 'einsso';
    const SLUG_MEGAPACK = 'megapack';

    /**
     * Obtiene la institución CIP.
     */
    public static function cip(): ?self
    {
        return static::where('slug', self::SLUG_CIP)->first();
    }

    /**
     * Obtiene la institución Einsso.
     */
    public static function einsso(): ?self
    {
        return static::where('slug', self::SLUG_EINSSO)->first();
    }

    /**
     * Obtiene la institución Megapack.
     */
    public static function megapack(): ?self
    {
        return static::where('slug', self::SLUG_MEGAPACK)->first();
    }

    /**
     * Verifica si este tipo es editable (solo Megapack permite editar logo).
     */
    public function isLogoEditable(): bool
    {
        return $this->slug !== self::SLUG_MEGAPACK;
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }
}
