<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchemeCommittee extends Model
{
    use HasFactory;

    protected $table = 'm_scheme_committees';

    protected $fillable = [
        'scheme_id',
        'role',
        'name',
        'no_met',
        'signature',
        'sequence',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
        ];
    }

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(Scheme::class);
    }

    public function getHasSignatureAttribute(): bool
    {
        return ! empty($this->signature);
    }
}
