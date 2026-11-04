<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentAppeal extends Model
{
    use HasFactory;

    protected $table = 't_assessment_appeals';

    protected $fillable = [
        'assessment_id',
        'appeal_date',
        'is_process_explained',
        'is_discussed_with_assessor',
        'involves_other_person',
        'other_person_name',
        'appeal_reasons',
        'contested_units',
        'status',
        'decision_notes',
        'decision_date',
        'decided_by',
        'appeals_committee_chair',
        'assessor_notes',
    ];

    protected function casts(): array
    {
        return [
            'appeal_date' => 'date',
            'decision_date' => 'date',
            'is_process_explained' => 'boolean',
            'is_discussed_with_assessor' => 'boolean',
            'involves_other_person' => 'boolean',
            'contested_units' => 'array',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
