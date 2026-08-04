<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id',
    'operation',
    'algorithm',
    'status',
    'input_text',
    'output_text',
    'parameters',
    'input_hash',
    'output_hash',
    'recovered_key',
    'score',
    'idioma',
    'duration_ms',
    'error_message',
])]
class CipherOperation extends Model
{
    /** @use HasFactory<\Database\Factories\CipherOperationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'input_text' => 'encrypted',
            'output_text' => 'encrypted',
            'parameters' => 'encrypted:array',
            'recovered_key' => 'encrypted',
            'score' => 'float',
            'duration_ms' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
