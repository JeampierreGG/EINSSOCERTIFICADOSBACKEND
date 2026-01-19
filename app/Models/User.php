<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable implements FilamentUser, HasName
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        // Check "is_admin" flag OR role_id = 1 (assuming 1 is Administrator)
        return (bool) ($this->is_admin ?? false) || ($this->role_id === 1);
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    public function certificateItems()
    {
        return $this->hasManyThrough(
            CertificateItem::class,
            Certificate::class,
            'user_id',
            'certificate_id',
            'id',
            'id'
        );
    }
}
