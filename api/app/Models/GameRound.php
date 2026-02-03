<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameRound extends Model
{
    protected $table = 'rounds';

    protected $fillable = [
        'game_id',
        'number',
        'dealer_index',
        'seed',
        'hands',
        'exchanged',
        'taken',
        'trick_number',
        'current_trick',
        'five_same_suit_declared',
        'partiya_declared_by',
        'remaining_deck',
        'passed_players',
        'boys_state',
    ];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'dealer_index' => 'integer',
            'seed' => 'integer',
            'hands' => 'array',
            'exchanged' => 'array',
            'taken' => 'array',
            'trick_number' => 'integer',
            'current_trick' => 'array',
            'five_same_suit_declared' => 'array',
            'partiya_declared_by' => 'array',
            'remaining_deck' => 'array',
            'passed_players' => 'array',
            'boys_state' => 'array',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
