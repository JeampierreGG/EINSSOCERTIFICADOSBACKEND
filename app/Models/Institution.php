<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Institution extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'logo_path',
    ];

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }
}
