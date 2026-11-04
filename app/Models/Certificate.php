<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    use HasFactory;

    protected $table = 't_certificates';

    protected $fillable = [
        'assessment_id',
        'schedule_id',
        'asesi_id',
        'scheme_id',
        'certificate_number',
        'blanko_number',
        'issued_date',
        'valid_until',
        'file_path',
        'original_filename',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'issued_date' => 'date',
            'valid_until' => 'date',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function asesi(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asesi_id');
    }

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(Scheme::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isExpired(): bool
    {
        return $this->valid_until ? $this->valid_until->isPast() : false;
    }

    public function isActive(): bool
    {
        return ! $this->isExpired();
    }
}
