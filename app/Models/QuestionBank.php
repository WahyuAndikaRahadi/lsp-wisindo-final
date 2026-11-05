<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class QuestionBank extends Model
{
    use HasFactory;

    protected $table = 'm_question_banks';

    protected $fillable = [
        'scheme_id',
        'competency_unit_id',
        'question_text',
        'question_image',
        'option_a',
        'option_b',
        'option_c',
        'option_d',
        'correct_answer',
    ];

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->question_image) {
            return null;
        }

        return Storage::disk('public')->url($this->question_image);
    }

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(Scheme::class);
    }

    public function competencyUnit(): BelongsTo
    {
        return $this->belongsTo(CompetencyUnit::class);
    }
}
