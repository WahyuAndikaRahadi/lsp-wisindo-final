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
        'option_a_image',
        'option_b',
        'option_b_image',
        'option_c',
        'option_c_image',
        'option_d',
        'option_d_image',
        'correct_answer',
    ];

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->question_image) {
            return null;
        }

        return Storage::disk('public')->url($this->question_image);
    }

    public function getOptionImageUrl(string $key): ?string
    {
        $col = 'option_'.strtolower($key).'_image';
        if (empty($this->$col)) {
            return null;
        }

        return Storage::disk('public')->url($this->$col);
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
