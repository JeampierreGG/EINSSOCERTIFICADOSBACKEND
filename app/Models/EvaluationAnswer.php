<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'evaluation_attemps_id',  // Nota: typo en BD, debería ser 'attempt' pero es 'attemps'
        'evaluation_question_id',
        'evaluation_option_id',
        'user_id',
    ];

    public function attempt()
    {
        return $this->belongsTo(EvaluationAttempt::class, 'evaluation_attemps_id');
    }

    public function question()
    {
        return $this->belongsTo(EvaluationQuestion::class, 'evaluation_question_id');
    }

    public function option()
    {
        return $this->belongsTo(EvaluationOption::class, 'evaluation_option_id');
    }
}
