<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModuleMaterial extends Model
{
    use HasFactory;

    protected $fillable = ['course_module_id', 'file_path'];

    public function courseModule()
    {
        return $this->belongsTo(CourseModule::class);
    }
}
