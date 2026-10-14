<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tuk extends Model
{
    use HasFactory;

    protected $table = 'm_tuks';

    protected $fillable = [
        'name',
        'address',
        'phone',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function admins(): HasMany
    {
        return $this->hasMany(User::class, 'tuk_id');
    }
}
