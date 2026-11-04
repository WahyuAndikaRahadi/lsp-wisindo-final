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
        'tuk_id',
        'tuk_name',
        'date',
        'start_time',
        'end_time',
        'duration_minutes',
        'pic_name',
        'pic_position',
        'pic_signature',
        'selected_instruments',
        'cbt_question_ids',
        'notes',
        'ak01_data',
        'ak07_data',
        'ak05_report',
        'ak06_review',
        'is_closed',
        'closed_at',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'closed_at' => 'datetime',
            'is_closed' => 'boolean',
            'ak01_data' => 'array',
            'ak07_data' => 'array',
            'ak05_report' => 'array',
            'ak06_review' => 'array',
            'selected_instruments' => 'array',
            'cbt_question_ids' => 'array',
            'duration_minutes' => 'integer',
        ];
    }

    public function getPicTukNameAttribute(): ?string
    {
        return $this->pic_name;
    }

    public function getPicTukPositionAttribute(): ?string
    {
        return $this->pic_position;
    }

    public function getPicTukSignatureAttribute(): ?string
    {
        return $this->pic_signature;
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

    /**
     * Get deterministic 20 random question IDs for CBT on this schedule.
     * All candidates in the same schedule receive the exact same 20 questions.
     *
     * @return array<int>
     */
    public function getCbtQuestionIds(): array
    {
        if (! empty($this->cbt_question_ids) && is_array($this->cbt_question_ids)) {
            return $this->cbt_question_ids;
        }

        $allIds = QuestionBank::where('scheme_id', $this->scheme_id)
            ->inRandomOrder()
            ->limit(20)
            ->pluck('id')
            ->toArray();

        if (! empty($allIds)) {
            $this->update(['cbt_question_ids' => $allIds]);
        }

        return $allIds;
    }

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(Scheme::class);
    }

    public function tuk(): BelongsTo
    {
        return $this->belongsTo(Tuk::class);
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

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * Determine whether a lifecycle step in the assessment workflow is unlocked for this schedule.
     */
    public function isStepUnlocked(string $step): bool
    {
        if ($this->is_closed) {
            return true;
        }

        return match ($step) {
            'apl' => true,
            'ak01' => $this->isAk01Unlocked(),
            'ak07' => $this->isAk07Unlocked(),
            'ruang-uji' => $this->isRuangUjiUnlocked(),
            'ak02' => $this->isAk02Unlocked(),
            'reports' => $this->isReportsUnlocked(),
            default => true,
        };
    }

    public function isAk01Unlocked(): bool
    {
        $hasAssessments = $this->assessments()->exists();
        if (! $hasAssessments) {
            return false;
        }

        $hasApproved = $this->assessments()->where('apl_status', 'APPROVED')->exists();
        if (! $hasApproved) {
            return false;
        }

        // If AK.01 was already saved and there is at least one approved candidate, keep it accessible/editable
        if (! empty($this->ak01_data) || $this->assessments()->whereNotNull('ak01_agreed_at')->exists()) {
            return true;
        }

        // Otherwise, requires all submitted APLs to be reviewed (none pending in SUBMITTED status)
        $hasPendingSubmitted = $this->assessments()->where('apl_status', 'SUBMITTED')->exists();

        return ! $hasPendingSubmitted;
    }

    public function isAk07Unlocked(): bool
    {
        if (! $this->isAk01Unlocked()) {
            return false;
        }

        // Requires AK.01 to be completed and saved
        return ! empty($this->ak01_data) || $this->assessments()->whereNotNull('ak01_agreed_at')->exists();
    }

    public function isRuangUjiUnlocked(): bool
    {
        if (! $this->isAk07Unlocked()) {
            return false;
        }

        return ! empty($this->ak07_data);
    }

    public function isAk02Unlocked(): bool
    {
        if (! $this->isRuangUjiUnlocked()) {
            return false;
        }

        // If AK.02 recommendations are already recorded or schedule is closed, keep unlocked
        if ($this->assessments()->whereIn('final_result', ['KOMPETEN', 'BELUM_KOMPETEN'])->exists()) {
            return true;
        }

        $approvedAssessments = $this->assessments()->where('apl_status', 'APPROVED')->get();
        if ($approvedAssessments->isEmpty()) {
            return false;
        }

        // AK.02 only unlocks when all approved candidates have completed all their assigned instruments in Ruang Uji
        foreach ($approvedAssessments as $assessment) {
            if (! $assessment->hasCompletedAllAssignedInstruments()) {
                return false;
            }
        }

        return true;
    }

    public function isReportsUnlocked(): bool
    {
        if ($this->is_closed) {
            return true;
        }

        if (! $this->isAk02Unlocked()) {
            return false;
        }

        // Requires all approved candidates to have AK.02 recommendations recorded
        $approvedAssessments = $this->assessments()->where('apl_status', 'APPROVED');
        if (! $approvedAssessments->exists()) {
            return false;
        }

        return ! $approvedAssessments->whereNotIn('final_result', ['KOMPETEN', 'BELUM_KOMPETEN'])->exists();
    }
}
