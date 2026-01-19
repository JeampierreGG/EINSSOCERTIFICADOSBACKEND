<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseContent extends Model
{
    use HasFactory;

    protected $table = 'course_contents_view';
    
    // The view's ID is a string (e.g., 'module-1')
    public $incrementing = false;
    protected $keyType = 'string';

    // View is read-only via this model
    protected $guarded = [];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }


}
