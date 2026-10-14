<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    use HasFactory;

    protected $table = 't_assessments';

    protected $fillable = [
        'schedule_id',
        'asesi_id',
        'assessor_id',
        'apl_status',
        'apl_revision_notes',
        'last_active_step',
        'status',
        'registered_at',
        'approved_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function asesi(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asesi_id');
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessor_id');
    }

    public function proofs(): HasMany
    {
        return $this->hasMany(AssessmentProof::class);
    }

    public function apl02Items(): HasMany
    {
        return $this->hasMany(AssessmentApl02Item::class);
    }

    public function ak07Items(): HasMany
    {
        return $this->hasMany(AssessmentAk07Item::class);
    }

    public function iaResponses(): HasMany
    {
        return $this->hasMany(AssessmentIaResponse::class);
    }
}
