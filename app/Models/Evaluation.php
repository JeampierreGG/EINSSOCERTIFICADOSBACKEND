<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'instructions',
        'attempts',
        'time_limit',
        'start_date',
        'end_date',
        'questions',
    ];

    protected $casts = [
        'questions' => 'array',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function questionsRelation()
    {
        return $this->hasMany(EvaluationQuestion::class)->orderBy('position');
    }
}
