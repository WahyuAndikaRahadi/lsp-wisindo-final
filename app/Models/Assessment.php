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
        'mapa01_data',
        'ak04_briefed_at',
        'has_rejection',
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
            'mapa01_data' => 'array',
            'ak04_briefed_at' => 'datetime',
            'has_rejection' => 'boolean',
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

    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class);
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
     * Check if FR.AK.01 has been completed/approved by the assessor.
     */
    public function hasAk01Completed(): bool
    {
        return ! empty($this->ak01_data['selected_instruments'])
            || ! empty($this->ak01_agreed_at)
            || ! empty($this->schedule?->ak01_data);
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

        $scheduleRaw = $this->schedule?->ak01_data['selected_instruments'] ?? $this->schedule?->selected_instruments ?? null;
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
        if ($this->status === 'COMPLETED') {
            return true;
        }

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

    /**
     * Determine if FR.AK.03 (Umpan Balik Asesi) is unlocked and ready for the candidate.
     * Unlocks when the candidate has completed all their required assessment activities
     * in Ruang Uji (e.g., CBT submitted, report sheet uploaded), or when the assessment is completed,
     * without needing to wait for the assessor to close the schedule.
     */
    public function canAsesiAccessAk03(): bool
    {
        // If already submitted AK03, it can always be viewed
        if ($this->relationLoaded('ak03Item') ? (bool) $this->ak03Item : $this->ak03Item()->exists()) {
            return true;
        }

        // If assessment is already completed or schedule closed
        if ($this->status === 'COMPLETED' || ($this->schedule && $this->schedule->is_closed)) {
            return true;
        }

        // Must have AK01 agreed / assigned instruments active
        if (! $this->hasAk01Completed()) {
            return false;
        }

        $assigned = $this->getAssignedInstruments();
        if (empty($assigned)) {
            return false;
        }

        $responses = $this->relationLoaded('iaResponses')
            ? $this->iaResponses->keyBy(fn ($r) => strtoupper($r->instrument_code))
            : $this->iaResponses()->get()->keyBy(fn ($r) => strtoupper($r->instrument_code));

        $hasAsesiTask = false;

        // 1. If CBT (IA05) is assigned, asesi must have submitted the CBT exam
        if (in_array('IA05', $assigned, true)) {
            $hasAsesiTask = true;
            $respIa05 = $responses->get('IA05');
            if (! $respIa05) {
                return false;
            }
            $dataJson = is_array($respIa05->data_json)
                ? $respIa05->data_json
                : (json_decode($respIa05->data_json ?? '[]', true) ?: []);

            $isCbtSubmitted = ($respIa05->score !== null || $respIa05->asesi_signed_at !== null)
                && ($dataJson['status'] ?? null) !== 'DRAFT';

            if (! $isCbtSubmitted) {
                return false;
            }
        }

        // 2. If Report Sheet (IA01B) is assigned, asesi must have uploaded the work sheet evidence
        if (in_array('IA01B', $assigned, true)) {
            $hasAsesiTask = true;
            $respIa01b = $responses->get('IA01B');
            $hasEvidence = false;
            if ($respIa01b) {
                $dataJson = is_array($respIa01b->data_json)
                    ? $respIa01b->data_json
                    : (json_decode($respIa01b->data_json ?? '[]', true) ?: []);
                $hasEvidence = ! empty($dataJson['evidence_image_path']) || ! empty($respIa01b->assessor_signed_at);
            }
            if (! $hasEvidence) {
                return false;
            }
        }

        // 3. If Portfolio (IA08) is assigned, asesi must have uploaded portfolio files
        if (in_array('IA08', $assigned, true)) {
            $hasAsesiTask = true;
            $respIa08 = $responses->get('IA08');
            $hasPortfolios = false;
            if ($respIa08) {
                $dataJson = is_array($respIa08->data_json)
                    ? $respIa08->data_json
                    : (json_decode($respIa08->data_json ?? '[]', true) ?: []);
                $hasPortfolios = ! empty($dataJson['portfolios']) || ! empty($respIa08->assessor_signed_at);
            }
            if (! $hasPortfolios) {
                return false;
            }
        }

        // 4. If there were asesi online tasks (like IA05, IA01B, or IA08) and they are all done, AK03 unlocks!
        if ($hasAsesiTask) {
            return true;
        }

        // 5. If only assessor-evaluated instruments are assigned (e.g. IA01, IA02, IA03, IA09),
        // check if assessor has evaluated them or if all assigned are completed
        return $this->hasCompletedAllAssignedInstruments();
    }

    /**
     * Determine if this assessment is closed / finished for the evaluating assessor.
     */
    public function isClosedForAssessor(?User $assessor = null): bool
    {
        if ($this->schedule && $this->schedule->isClosedForAssessor($assessor)) {
            return true;
        }

        return false;
    }

    /**
     * Determine if this candidate is eligible to submit/view an assessment appeal (FR.AK.04).
     */
    public function canAppeal(): bool
    {
        $hasAppeal = $this->relationLoaded('appeals')
            ? $this->appeals->isNotEmpty()
            : $this->appeals()->exists();

        $isEligible = (bool) $this->has_rejection || $hasAppeal;

        if (! $isEligible) {
            return false;
        }

        return $this->status === 'COMPLETED'
            || ! empty($this->final_result)
            || (bool) ($this->schedule?->is_closed);
    }
}
