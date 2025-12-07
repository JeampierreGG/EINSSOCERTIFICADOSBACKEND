<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CertificateItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'certificate_id',
        'institution_id',
        'title',
        'category',
        'hours',
        'grade',
        'issue_date',
        'code',
        'file_path',
    ];

    protected $casts = [
        'hours' => 'integer',
        'grade' => 'integer',
    ];

    public function certificate()
    {
        return $this->belongsTo(Certificate::class);
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }
}
