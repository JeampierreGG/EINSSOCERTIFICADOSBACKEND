<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseModule extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'order',
        'enable_date',
        'is_published',
        'pdf_path',
        'zoom_url',
        'class_time',
        'video_url',
    ];

    protected $casts = [
        'enable_date' => 'datetime',
        'is_published' => 'boolean',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons()
    {
        return $this->hasMany(CourseLesson::class, 'module_id')->orderBy('order');
    }
}
