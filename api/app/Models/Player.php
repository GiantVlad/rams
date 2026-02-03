<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Player extends Model
{
    protected $fillable = [
        'game_id',
        'seat_index',
        'type',
        'score',
        'pile',
        'maltzy_count',
    ];

    protected function casts(): array
    {
        return [
            'seat_index' => 'integer',
            'score' => 'integer',
            'pile' => 'integer',
            'maltzy_count' => 'integer',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
