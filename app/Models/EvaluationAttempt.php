<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationAttempt extends Model
{
    use HasFactory;
    
    protected $fillable = ['user_id', 'evaluation_id', 'course_id', 'score', 'completed_at', 'attempt_number'];

    public function evaluation() {
        return $this->belongsTo(Evaluation::class);
    }
    
    public function course() {
        return $this->belongsTo(Course::class);
    }
}
