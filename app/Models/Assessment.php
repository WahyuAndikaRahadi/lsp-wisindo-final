<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Assessment extends Model
{
    use HasFactory;

    protected $table = 't_assessments';

    protected $fillable = [
        'schedule_id',
        'asesi_id',
        'assessor_id',
        'assessor_signature',
        'assessor_signed_at',
        'asesi_signature',
        'asesi_signed_at',
        'apl_status',
        'tujuan_asesmen',
        'apl_revision_notes',
        'last_active_step',
        'status',
        'admin_status',
        'admin_notes',
        'admin_approved_at',
        'admin_id',
        'admin_reviewed_at',
        'admin_reviewer_id',
        'final_result',
        'final_result_at',
        'final_notes',
        'follow_up_action',
        'ak01_agreed_at',
        'ak01_data',
        'pleno_decision',
        'pleno_notes',
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
            'admin_approved_at' => 'datetime',
            'admin_reviewed_at' => 'datetime',
            'ak01_agreed_at' => 'datetime',
            'ak01_data' => 'array',
            'final_result_at' => 'datetime',
            'assessor_signed_at' => 'datetime',
            'asesi_signed_at' => 'datetime',
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

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
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

    public function ak02Items(): HasMany
    {
        return $this->hasMany(AssessmentAk02Item::class);
    }

    public function ak03Item(): HasOne
    {
        return $this->hasOne(AssessmentAk03Item::class);
    }

    public function ak03Items(): HasMany
    {
        return $this->hasMany(AssessmentAk03Item::class);
    }

    public function appeals(): HasMany
    {
        return $this->hasMany(AssessmentAppeal::class)->latest();
    }

    public function latestAppeal(): HasOne
    {
        return $this->hasOne(AssessmentAppeal::class)->latestOfMany();
    }

    public function iaResponses(): HasMany
    {
        return $this->hasMany(AssessmentIaResponse::class);
    }

    public function adminReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_reviewer_id');
    }

    /**
     * Get list of assigned FR.IA instrument codes for this assessment.
     *
     * @return array<string>
     */
    public function getAssignedInstruments(): array
    {
        $raw = $this->ak01_data['selected_instruments'] ?? null;
        if (! empty($raw) && is_array($raw)) {
            $filtered = array_values(array_filter($raw, fn ($c) => str_starts_with(strtoupper($c), 'IA')));
            if (! empty($filtered)) {
                return $filtered;
            }
        }

        $scheduleRaw = $this->schedule?->selected_instruments ?? null;
        if (! empty($scheduleRaw) && is_array($scheduleRaw)) {
            $filtered = array_values(array_filter($scheduleRaw, fn ($c) => str_starts_with(strtoupper($c), 'IA')));
            if (! empty($filtered)) {
                return $filtered;
            }
        }

        return ['IA01', 'IA01B', 'IA02', 'IA03', 'IA05'];
    }

    /**
     * Check if a specific FR.IA instrument is assigned to this candidate.
     */
    public function hasAssignedInstrument(string $code): bool
    {
        $clean = strtoupper(str_replace(['FR.', 'fr.', '.'], '', $code));

        return in_array($clean, $this->getAssignedInstruments(), true);
    }

    /**
     * Determine if all assigned FR.IA instruments for this candidate have been evaluated/completed.
     */
    public function hasCompletedAllAssignedInstruments(): bool
    {
        $assigned = $this->getAssignedInstruments();
        if (empty($assigned)) {
            return true;
        }

        $responses = $this->iaResponses->keyBy(fn ($r) => strtoupper($r->instrument_code));

        foreach ($assigned as $code) {
            $codeUpper = strtoupper($code);
            $resp = $responses->get($codeUpper);
            if (! $resp) {
                return false;
            }

            // CBT (IA05) autosave draft does not count as complete until submitted
            if ($codeUpper === 'IA05') {
                $dataJson = is_array($resp->data_json) ? $resp->data_json : (json_decode($resp->data_json ?? '[]', true) ?: []);
                if (($dataJson['status'] ?? null) === 'DRAFT') {
                    return false;
                }
            }
        }

        return true;
    }
}
