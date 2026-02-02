<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationUserExtension extends Model
{
    use HasFactory;
    
    protected $fillable = ['user_id', 'evaluation_id', 'extra_attempts', 'extended_end_date'];
}
