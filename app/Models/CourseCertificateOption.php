<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseCertificateOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'type',
        'title',
        'description',
        'price',
        'image_1_path',
        'image_2_path',
        'megapack_items',
        'details',
        'academic_hours',
        'discount_percentage',
        'discount_end_date',
        'certification_block_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'discount_end_date' => 'date',
        'megapack_items' => 'array',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function block()
    {
        return $this->belongsTo(CertificationBlock::class, 'certification_block_id');
    }
}
