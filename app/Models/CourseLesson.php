<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseLesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'title',
        'type',
        'content_url',
        'external_url',
        'order',
    ];

    public function module()
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }
}
