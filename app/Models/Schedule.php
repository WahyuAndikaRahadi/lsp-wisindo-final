<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

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
        'mapa01_data',
        'ak04_briefed_at',
        'ak05_report',
        'ak06_review',
        'is_closed',
        'closed_at',
        'reopened_at',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'closed_at' => 'datetime',
            'reopened_at' => 'datetime',
            'is_closed' => 'boolean',
            'ak01_data' => 'array',
            'ak07_data' => 'array',
            'mapa01_data' => 'array',
            'ak04_briefed_at' => 'datetime',
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
        return $this->belongsToMany(User::class, 't_schedule_assessors', 'schedule_id', 'assessor_id')->orderBy('full_name', 'asc');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function appeals(): HasManyThrough
    {
        return $this->hasManyThrough(AssessmentAppeal::class, Assessment::class);
    }

    public function ak03Items(): HasManyThrough
    {
        return $this->hasManyThrough(AssessmentAk03Item::class, Assessment::class);
    }

    /**
     * Get query for assessments assigned to the specified assessor (or logged-in assessor).
     * If no assessor is specified and current user is not an assessor, returns all assessments.
     */
    public function assessorAssessmentsQuery(?User $assessor = null)
    {
        $user = $assessor ?? (auth()->check() && auth()->user()->role === 'ASESOR' ? auth()->user() : null);
        $query = $this->assessments()
            ->where('apl_status', '!=', 'REJECTED')
            ->where('admin_status', '!=', 'REJECTED');

        if (! $user || $user->role !== 'ASESOR') {
            return $query;
        }

        $isSingleAssessor = $this->relationLoaded('assessors')
            ? ($this->assessors->count() === 1 && $this->assessors->first()?->id === $user->id)
            : ($this->assessors()->count() === 1 && $this->assessors()->where('users.id', $user->id)->exists());

        return $query->where(function ($q) use ($user, $isSingleAssessor) {
            $q->where('assessor_id', $user->id);
            if ($isSingleAssessor) {
                $q->orWhereNull('assessor_id');
            }
        });
    }

    /**
     * Get collection of assessments assigned to the specified assessor (or logged-in assessor).
     */
    public function assessorAssessments(?User $assessor = null)
    {
        $user = $assessor ?? (auth()->check() && auth()->user()->role === 'ASESOR' ? auth()->user() : null);

        $assessments = $this->assessments->filter(function ($a) {
            return $a->apl_status !== 'REJECTED' && $a->admin_status !== 'REJECTED';
        });

        if (! $user || $user->role !== 'ASESOR') {
            return $assessments->values();
        }

        $isSingleAssessor = $this->relationLoaded('assessors')
            ? ($this->assessors->count() === 1 && $this->assessors->first()?->id === $user->id)
            : ($this->assessors()->count() === 1 && $this->assessors()->where('users.id', $user->id)->exists());

        return $assessments->filter(function ($a) use ($user, $isSingleAssessor) {
            return (int) $a->assessor_id === (int) $user->id || (empty($a->assessor_id) && $isSingleAssessor);
        })->values();
    }

    /**
     * Determine if the schedule is closed / finished for a specific assessor.
     * Returns true if the global schedule is closed, or if this assessor's candidate
     * pool is completely finished and this assessor has submitted their closure reports (FR.AK.05 & FR.AK.06).
     */
    public function isClosedForAssessor(?User $assessor = null): bool
    {
        if ($this->is_closed) {
            return true;
        }

        // When schedule has been reopened by admin, it is open for edits by all assigned assessors
        if (! empty($this->reopened_at)) {
            return false;
        }

        $assessor ??= (auth()->check() && auth()->user()->role === 'ASESOR' ? auth()->user() : null);
        if (! $assessor || $assessor->role !== 'ASESOR') {
            return (bool) $this->is_closed;
        }

        $ak05 = is_array($this->ak05_report) ? $this->ak05_report : (json_decode($this->ak05_report ?? '[]', true) ?: []);
        $assessorId = $assessor->id;

        $hasSubmitted = isset($ak05['by_assessor'][$assessorId])
            || ((int) ($ak05['assessor_id'] ?? 0) === (int) $assessorId && ! empty($ak05['submitted_at']));

        if (! $hasSubmitted) {
            return false;
        }

        $myAssessments = $this->assessorAssessments($assessor);
        if ($myAssessments->isEmpty()) {
            return false;
        }

        $hasUncompleted = $myAssessments->contains(function ($a) {
            return $a->status !== 'COMPLETED' && $a->final_result !== 'TIDAK_HADIR';
        });

        return ! $hasUncompleted;
    }

    /**
     * Determine whether a lifecycle step in the assessment workflow is unlocked for this schedule (or assessor).
     */
    public function isStepUnlocked(string $step, ?User $assessor = null): bool
    {
        if ($this->is_closed || $this->isClosedForAssessor($assessor)) {
            return true;
        }

        return match ($step) {
            'apl' => true,
            'mapa' => $this->isMapaUnlocked($assessor),
            'ak04' => $this->isAk04Unlocked($assessor),
            'ak01' => $this->isAk01Unlocked($assessor),
            'ak07' => $this->isAk07Unlocked($assessor),
            'ruang-uji' => $this->isRuangUjiUnlocked($assessor),
            'ak02' => $this->isAk02Unlocked($assessor),
            'reports' => $this->isReportsUnlocked($assessor),
            default => true,
        };
    }

    public function isMapaUnlocked(?User $assessor = null): bool
    {
        $assessor ??= auth()->user();
        $query = $this->assessorAssessmentsQuery($assessor);

        $hasAssessments = (clone $query)->exists();
        if (! $hasAssessments) {
            return false;
        }

        $hasApproved = (clone $query)->where('apl_status', 'APPROVED')->exists();
        if (! $hasApproved) {
            return false;
        }

        // If MAPA is already filled or AK.01 already initiated, keep it accessible
        if (! empty($this->mapa01_data) || ! empty($this->ak01_data)) {
            return true;
        }

        $hasPendingSubmitted = (clone $query)->where('apl_status', 'SUBMITTED')->exists();

        return ! $hasPendingSubmitted;
    }

    public function isAk04Unlocked(?User $assessor = null): bool
    {
        $assessor ??= auth()->user();
        if (! $this->isMapaUnlocked($assessor)) {
            return false;
        }

        // If briefed or AK.01 already filled/signed, keep it accessible
        if (! empty($this->ak04_briefed_at) || ! empty($this->ak01_data)) {
            return true;
        }

        // Requires MAPA 01 to be filled
        $query = $this->assessorAssessmentsQuery($assessor);
        $hasCandidateMapa = (clone $query)->whereNotNull('mapa01_data')->exists();

        return ! empty($this->mapa01_data) || $hasCandidateMapa;
    }

    public function isAk01Unlocked(?User $assessor = null): bool
    {
        $assessor ??= auth()->user();
        $query = $this->assessorAssessmentsQuery($assessor);

        $hasAssessments = (clone $query)->exists();
        if (! $hasAssessments) {
            return false;
        }

        $hasApproved = (clone $query)->where('apl_status', 'APPROVED')->exists();
        if (! $hasApproved) {
            return false;
        }

        $isAssessor = $assessor && $assessor->role === 'ASESOR';
        $isSingleAssessor = $isAssessor && $this->assessors()->count() === 1 && $this->assessors()->first()?->id === $assessor->id;

        // If AK.01 was already saved/signed for this assessor's pool, keep it accessible/editable
        $hasSignedCandidate = (clone $query)->where(function ($q) {
            $q->whereNotNull('ak01_agreed_at')->orWhereNotNull('assessor_signature');
        })->exists();

        if ($hasSignedCandidate || (! $isAssessor && ! empty($this->ak01_data)) || ($isSingleAssessor && ! empty($this->ak01_data))) {
            return true;
        }

        // Otherwise, requires all submitted APLs for this assessor's candidate pool to be reviewed (none pending in SUBMITTED status)
        $hasPendingSubmitted = (clone $query)->where('apl_status', 'SUBMITTED')->exists();

        return ! $hasPendingSubmitted;
    }

    public function isAk07Unlocked(?User $assessor = null): bool
    {
        $assessor ??= auth()->user();
        if (! $this->isAk01Unlocked($assessor)) {
            return false;
        }

        $query = $this->assessorAssessmentsQuery($assessor);
        $isAssessor = $assessor && $assessor->role === 'ASESOR';
        $isSingleAssessor = $isAssessor && $this->assessors()->count() === 1 && $this->assessors()->first()?->id === $assessor->id;

        // Requires AK.01 to be completed and saved for this assessor's candidate(s)
        if ($isAssessor && ! $isSingleAssessor) {
            return (clone $query)->where(function ($q) {
                $q->whereNotNull('ak01_agreed_at')->orWhereNotNull('assessor_signature');
            })->exists();
        }

        return ! empty($this->ak01_data)
            || (clone $query)->whereNotNull('ak01_agreed_at')->exists()
            || (clone $query)->whereNotNull('assessor_signature')->exists();
    }

    public function isRuangUjiUnlocked(?User $assessor = null): bool
    {
        $assessor ??= auth()->user();
        if (! $this->isAk07Unlocked($assessor)) {
            return false;
        }

        $query = $this->assessorAssessmentsQuery($assessor);
        $candidateIds = (clone $query)->pluck('id');
        $isAssessor = $assessor && $assessor->role === 'ASESOR';
        $isSingleAssessor = $isAssessor && $this->assessors()->count() === 1 && $this->assessors()->first()?->id === $assessor->id;

        if ($isAssessor && ! $isSingleAssessor) {
            return AssessmentAk07Item::whereIn('assessment_id', $candidateIds)->exists();
        }

        return ! empty($this->ak07_data)
            || AssessmentAk07Item::whereIn('assessment_id', $candidateIds)->exists();
    }

    public function isAk02Unlocked(?User $assessor = null): bool
    {
        $assessor ??= auth()->user();
        $query = $this->assessorAssessmentsQuery($assessor);

        // If AK.02 recommendations are already recorded or schedule is closed, keep unlocked
        if ((clone $query)->whereIn('final_result', ['KOMPETEN', 'BELUM_KOMPETEN'])->exists()) {
            return true;
        }

        if (! $this->isRuangUjiUnlocked($assessor)) {
            return false;
        }

        $approvedAssessments = (clone $query)->where('apl_status', 'APPROVED')->get();
        if ($approvedAssessments->isEmpty()) {
            return false;
        }

        // AK.02 only unlocks when all approved candidates for this assessor have completed all their assigned instruments in Ruang Uji
        foreach ($approvedAssessments as $assessment) {
            if (! $assessment->hasCompletedAllAssignedInstruments()) {
                return false;
            }
        }

        return true;
    }

    public function isReportsUnlocked(?User $assessor = null): bool
    {
        if ($this->is_closed) {
            return true;
        }

        $assessor ??= auth()->user();
        if (! $this->isAk02Unlocked($assessor)) {
            return false;
        }

        $query = $this->assessorAssessmentsQuery($assessor);

        // Requires all approved candidates for this assessor to have AK.02 recommendations recorded
        $approvedAssessments = (clone $query)->where('apl_status', 'APPROVED');
        if (! (clone $approvedAssessments)->exists()) {
            return false;
        }

        return ! (clone $approvedAssessments)->whereNotIn('final_result', ['KOMPETEN', 'BELUM_KOMPETEN'])->exists();
    }
}
