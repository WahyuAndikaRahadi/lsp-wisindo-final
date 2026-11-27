<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobGroup extends Model
{
    use HasFactory;

    protected $table = 'm_job_groups';

    protected $fillable = [
        'scheme_id',
        'sequence',
        'name',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('ordered', fn ($q) => $q->orderBy('sequence')->orderBy('id'));
    }

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(Scheme::class);
    }

    public function competencyUnits(): HasMany
    {
        return $this->hasMany(CompetencyUnit::class)
            ->withoutGlobalScope('ordered')
            ->orderBy('job_group_sequence')
            ->orderBy('sequence')
            ->orderBy('id');
    }
}
