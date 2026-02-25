<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseReminderImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'type',
        'evaluation_id',
        'image_path',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function evaluation()
    {
        return $this->belongsTo(Evaluation::class);
    }
}
