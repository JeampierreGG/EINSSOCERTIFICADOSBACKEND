<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'header_logo',
        'footer_logo',
        'loading_logo',
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
