<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    protected $fillable = [
        'status',
        'dealer_index',
        'current_player_index',
        'round_number',
        'phase',
        'trump_card_id',
    ];

    protected function casts(): array
    {
        return [
            'dealer_index' => 'integer',
            'current_player_index' => 'integer',
            'round_number' => 'integer',
        ];
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(GameRound::class);
    }

    public function currentRound(): ?GameRound
    {
        return $this->rounds()->where('number', $this->round_number)->first();
    }
}
