<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Scheme extends Model
{
    use HasFactory;

    protected $table = 'm_schemes';

    protected $attributes = [
        'scheme_type' => 'KLASTER',
    ];

    protected $fillable = [
        'code',
        'name',
        'scheme_type',
        'version',
        'description',
        'passing_grade_theory',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'passing_grade_theory' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function getCategoryAttribute(): string
    {
        return 'Pariwisata & Perhotelan';
    }

    public function competencyUnits(): HasMany
    {
        return $this->hasMany(CompetencyUnit::class);
    }

    public function units(): HasMany
    {
        return $this->competencyUnits();
    }

    public function jobGroups(): HasMany
    {
        return $this->hasMany(JobGroup::class)->orderBy('sequence')->orderBy('id');
    }

    public function questionBanks(): HasMany
    {
        return $this->hasMany(QuestionBank::class);
    }

    public function interviewQuestions(): HasMany
    {
        return $this->hasMany(InterviewQuestion::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function committees(): HasMany
    {
        return $this->hasMany(SchemeCommittee::class)->orderBy('sequence')->orderBy('id');
    }

    public function penyusun(): HasMany
    {
        return $this->hasMany(SchemeCommittee::class)->where('role', 'PENYUSUN')->orderBy('sequence')->orderBy('id');
    }

    public function validators(): HasMany
    {
        return $this->hasMany(SchemeCommittee::class)->where('role', 'VALIDATOR')->orderBy('sequence')->orderBy('id');
    }

    public function getPenyusunListAttribute(): array
    {
        return $this->penyusun->map(fn (SchemeCommittee $item) => [
            'id' => $item->id,
            'name' => $item->name,
            'no_met' => $item->no_met,
            'signature' => $item->signature,
            'sequence' => $item->sequence,
        ])->values()->all();
    }

    public function getValidatorListAttribute(): array
    {
        return $this->validators->map(fn (SchemeCommittee $item) => [
            'id' => $item->id,
            'name' => $item->name,
            'no_met' => $item->no_met,
            'signature' => $item->signature,
            'sequence' => $item->sequence,
        ])->values()->all();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
