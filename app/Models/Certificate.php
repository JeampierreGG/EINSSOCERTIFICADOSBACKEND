<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'megapack_group_id',
        'institution_id',
        'category',
        'title',
        'hours',
        'grade',
        'issue_date',
        'code',
        'file_path',
    ];

    protected $casts = [
        'hours' => 'integer',
        'grade' => 'integer',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(CertificateItem::class);
    }

    public function groupSiblings()
    {
        return $this->hasMany(self::class, 'megapack_group_id', 'megapack_group_id')
            ->where('type', 'megapack');
    }
}
