<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'evaluation_id',
        'question_text',
        'points',
        'position'
    ];

    public function evaluation()
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function options()
    {
        return $this->hasMany(EvaluationOption::class)->orderBy('position');
    }
}
