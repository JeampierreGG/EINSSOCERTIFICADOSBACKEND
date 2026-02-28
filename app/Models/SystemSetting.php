<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'facebook_url',
        'instagram_url',
        'tiktok_url',
        'youtube_url',
        'x_url',
        'address',
        'phone',
        'email',
    ];
}
