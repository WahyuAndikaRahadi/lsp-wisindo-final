<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentAk02Item extends Model
{
    use HasFactory;

    protected $table = 't_assessment_ak02_items';

    protected $fillable = [
        'assessment_id',
        'competency_unit_id',
        'methods_json',
        'is_competent',
    ];

    protected function casts(): array
    {
        return [
            'methods_json' => 'array',
            'is_competent' => 'boolean',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function competencyUnit(): BelongsTo
    {
        return $this->belongsTo(CompetencyUnit::class);
    }

    public function getIsObservasiAttribute(): bool
    {
        return ! empty($this->methods_json['observasi_demonstrasi']);
    }

    public function getIsPortofolioAttribute(): bool
    {
        return ! empty($this->methods_json['portofolio']);
    }

    public function getIsPihakKetigaAttribute(): bool
    {
        return ! empty($this->methods_json['pihak_ketiga']);
    }

    public function getIsLisanAttribute(): bool
    {
        return ! empty($this->methods_json['lisan']);
    }

    public function getIsTulisAttribute(): bool
    {
        return ! empty($this->methods_json['tulis']);
    }

    public function getIsProyekAttribute(): bool
    {
        return ! empty($this->methods_json['proyek']);
    }

    public function getIsLainnyaAttribute(): bool
    {
        return ! empty($this->methods_json['lainnya']);
    }
}
