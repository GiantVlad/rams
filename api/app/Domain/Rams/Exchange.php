<?php

namespace App\Domain\Rams;

use RuntimeException;

final class Exchange
{
    /**
     * Validate an exchange request.
     *
     * @param  list<string>  $handCardIds
     * @param  list<string>  $discardCardIds
     *
     * @throws RuntimeException
     */
    public static function validate(int $playerIndex, array $handCardIds, array $discardCardIds, int $deckRemaining): void
    {
        $discardCount = count($discardCardIds);

        if ($discardCount < 0 || $discardCount > 5) {
            throw new RuntimeException('You may discard between 0 and 5 cards.');
        }

        // Ensure all discarded cards are in the player's hand
        $handSet = array_flip($handCardIds);
        foreach ($discardCardIds as $cardId) {
            if (! isset($handSet[$cardId])) {
                throw new RuntimeException('You can only discard cards that are in your hand.');
            }
        }

        // Ensure enough cards remain in deck for the exchange (dealer only)
        // Allow 0 card exchanges even if deck is empty
        if ($discardCount > 0 && $deckRemaining < $discardCount) {
            throw new RuntimeException('Not enough cards left in the deck to exchange this many.');
        }
    }

    /**
     * Check if a player has five cards of the same suit after deal.
     *
     * @param  list<string>  $handCardIds
     * @return string|null The suit symbol if five same suit, null otherwise
     */
    public static function hasFiveSameSuit(array $handCardIds): ?string
    {
        if (count($handCardIds) !== 5) {
            return null;
        }

        $suits = [];
        foreach ($handCardIds as $cardId) {
            $suit = CardCodec::decodeSuit($cardId);
            $suits[$suit->value] = ($suits[$suit->value] ?? 0) + 1;
        }

        foreach ($suits as $suit => $count) {
            if ($count === 5) {
                return $suit;
            }
        }

        return null;
    }

    /**
     * Determine which player (if any) should declare five same suit, by priority closest to left of dealer.
     *
     * @param  array<int, list<string>>  $hands
     * @return int|null Player index who should declare, or null
     */
    public static function priorityFiveSameSuit(array $hands, int $dealerIndex, int $playerCount = 5): ?int
    {
        for ($i = 1; $i <= $playerCount; $i++) {
            $playerIndex = ($dealerIndex + $i) % $playerCount;
            $hand = $hands[$playerIndex] ?? [];
            if (self::hasFiveSameSuit($hand)) {
                return $playerIndex;
            }
        }

        return null;
    }
}
