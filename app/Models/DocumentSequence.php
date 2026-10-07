<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentSequence extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentSequenceFactory> */
    use HasFactory;

    protected $fillable = [
        'key',
        'period',
        'prefix',
        'next_number',
    ];

    protected function casts(): array
    {
        return [
            'next_number' => 'integer',
        ];
    }
}
