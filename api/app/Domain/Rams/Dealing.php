<?php

namespace App\Domain\Rams;

use RuntimeException;

final class Dealing
{
    /**
     * @return array{hands: list<list<Card>>}
     */
    public static function deal(Deck $deck, int $players = 4, int $cardsPerPlayer = 5): array
    {
        if ($players <= 0) {
            throw new RuntimeException('Players count must be positive.');
        }

        if ($cardsPerPlayer <= 0) {
            throw new RuntimeException('Cards per player must be positive.');
        }

        $total = $players * $cardsPerPlayer;

        if ($deck->count() < $total) {
            throw new RuntimeException("Deck must contain at least {$total} cards to deal.");
        }

        $hands = array_fill(0, $players, []);

        for ($i = 0; $i < $total; $i++) {
            $playerIndex = $i % $players;
            $hands[$playerIndex][] = $deck->draw();
        }

        // Note: Deck may have remaining cards (e.g., for trump/exchange)
        return ['hands' => $hands];
    }
}
