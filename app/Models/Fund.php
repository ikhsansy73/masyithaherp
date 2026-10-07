<?php

namespace App\Models;

use App\Enums\FundType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fund extends Model
{
    /** @use HasFactory<\Database\Factories\FundFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => FundType::class,
            'is_active' => 'boolean',
        ];
    }
}
