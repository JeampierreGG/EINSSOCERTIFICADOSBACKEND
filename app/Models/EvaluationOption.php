<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'evaluation_question_id',
        'option_text',
        'is_correct',
        'position'
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function question()
    {
        return $this->belongsTo(EvaluationQuestion::class, 'evaluation_question_id');
    }
}
