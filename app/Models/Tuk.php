<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tuk extends Model
{
    use HasFactory;

    protected $table = 'm_tuks';

    protected $fillable = [
        'code',
        'name',
        'type',
        'address',
        'postal_code',
        'kode_provinsi',
        'nama_provinsi',
        'kode_kota',
        'nama_kota',
        'phone',
        'email',
        'pic_name',
        'pic_phone',
        'pic_position',
        'is_active',
        'verification_status',
        'verification_notes',
        'verified_at',
        'verified_by',
        'room_photos',
        'internet_data_photos',
        'internet_connection_photos',
        'equipment_photos',
        'room_video_path',
        'schema_documents',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'verified_at' => 'datetime',
            'room_photos' => 'array',
            'internet_data_photos' => 'array',
            'internet_connection_photos' => 'array',
            'equipment_photos' => 'array',
            'schema_documents' => 'array',
        ];
    }

    public function admins(): HasMany
    {
        return $this->hasMany(User::class, 'tuk_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function schemes(): BelongsToMany
    {
        return $this->belongsToMany(Scheme::class, 'm_tuk_schemes', 'tuk_id', 'scheme_id')
            ->withPivot('is_verified')
            ->withTimestamps();
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'tuk_id');
    }

    public function isVerified(): bool
    {
        return $this->verification_status === 'VERIFIED';
    }

    public function canHostScheme(int|string $schemeId): bool
    {
        if (! $this->isVerified()) {
            return false;
        }

        return $this->schemes()
            ->where('m_schemes.id', $schemeId)
            ->wherePivot('is_verified', true)
            ->exists();
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->verification_status) {
            'VERIFIED' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            'PENDING_VERIFICATION' => 'bg-amber-100 text-amber-800 border-amber-300',
            'REVISION' => 'bg-rose-100 text-rose-800 border-rose-300',
            default => 'bg-slate-100 text-slate-700 border-slate-300',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->verification_status) {
            'VERIFIED' => 'Terverifikasi (Aktif)',
            'PENDING_VERIFICATION' => 'Menunggu Verifikasi',
            'REVISION' => 'Perlu Revisi',
            default => 'Belum Melengkapi Berkas',
        };
    }
}
