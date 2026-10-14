<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'password',
        'role',
        'status',
        'assessor_registration_number',
        'is_committee_head',
        'tuk_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function tuk(): BelongsTo
    {
        return $this->belongsTo(Tuk::class);
    }

    public function scheduleAssessments(): HasMany
    {
        return $this->hasMany(Assessment::class, 'asesi_id');
    }

    public function assessorAssessments(): HasMany
    {
        return $this->hasMany(Assessment::class, 'assessor_id');
    }

    public function assignedSchedules(): BelongsToMany
    {
        return $this->belongsToMany(Schedule::class, 't_schedule_assessors', 'assessor_id', 'schedule_id');
    }

    public function scopeRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function isRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['SUPER_ADMIN', 'ADMIN_LSP']);
    }
}
