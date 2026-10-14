<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    use HasFactory;

    protected $table = 't_schedules';

    protected $fillable = [
        'code',
        'name',
        'scheme_id',
        'tuk_name',
        'date',
        'start_time',
        'end_time',
        'duration_minutes',
        'selected_instruments',
        'notes',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'selected_instruments' => 'array',
            'duration_minutes' => 'integer',
        ];
    }

    /**
     * Get effective list of instruments, defaulting to full standard suite when empty.
     */
    public function getEffectiveInstruments(): array
    {
        $instruments = $this->selected_instruments;
        if (! empty($instruments) && is_array($instruments)) {
            return $instruments;
        }

        return ['AK01', 'IA01', 'IA01B', 'IA02', 'IA03', 'IA05'];
    }

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(Scheme::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assessors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 't_schedule_assessors', 'schedule_id', 'assessor_id');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }
}
