<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'code',
        'title',
        'subtitle',
        'description',
        'objectives',
        'target_audience',
        'image_path',
        'welcome_image_path',
        'brochure_path',
        'whatsapp_number',
        'start_date',
        'end_date',
        'end_date',
        'duration_text',
        'academic_hours',
        'level',
        'status',
        'is_free',
        'price',
        'teacher_id',
        'category',
        'sessions_count',
        'class_type',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_free' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function modules()
    {
        return $this->hasMany(CourseModule::class)->orderBy('order');
    }

    public function enrollments()
    {
        return $this->hasMany(CourseEnrollment::class);
    }
    
    public function students()
    {
        return $this->belongsToMany(User::class, 'course_enrollments')
                    ->withPivot(['status', 'enrolled_at'])
                    ->withTimestamps();
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function certificateOptions()
    {
        return $this->hasMany(CourseCertificateOption::class);
    }

    public function certificationBlocks()
    {
        return $this->hasMany(CertificationBlock::class);
    }
}
