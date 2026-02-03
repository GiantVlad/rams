<?php

namespace App\Domain\Rams;

use RuntimeException;

final class Scoring
{
    /**
     * Apply round-end scoring to each player's pile.
     *
     * @param  array<int, int>  $tricksWonByPlayer
     * @param  array<int, int>  $maltzyCountByPlayer
     * @param  array<int, int>  $currentPileByPlayer
     * @return array<int, int> newPileByPlayer
     */
    public static function applyRoundScoring(
        array $tricksWonByPlayer,
        array $maltzyCountByPlayer,
        array $currentPileByPlayer,
        array $passedPlayers = [],
        int $players = 4
    ): array {
        if (count($tricksWonByPlayer) !== $players) {
            throw new RuntimeException("Expected {$players} trick counts.");
        }
        if (count($maltzyCountByPlayer) !== $players) {
            throw new RuntimeException("Expected {$players} maltzy counts.");
        }
        if (count($currentPileByPlayer) !== $players) {
            throw new RuntimeException("Expected {$players} pile values.");
        }

        $newPiles = [];
        for ($i = 0; $i < $players; $i++) {
            $tricks = $tricksWonByPlayer[$i] ?? 0;
            $maltzy = $maltzyCountByPlayer[$i] ?? 0;
            $pile = $currentPileByPlayer[$i] ?? 20;

            if ($tricks < 0 || $tricks > 4) {
                // throw new RuntimeException("Tricks won must be between 0 and 4 for player {$i}.");
                // Allow >4 if we handle it elsewhere? No, tricks per round is limited.
                // But passed players have 0.
            }

            // Reduce pile by tricks won
            $newPile = $pile - $tricks;

            // Penalty for playing but winning no tricks
            if ($tricks === 0 && ! in_array($i, $passedPlayers)) {
                $newPile += 5;
            }

            // Deduct 5 points per maltzy declared
            $newPile -= $maltzy * 5;

            // Pile cannot go below 0
            $newPiles[$i] = max(0, $newPile);
        }

        return $newPiles;
    }

    /**
     * Determine if a player must declare partiya (≤5 pile and exact tricks to reach 0).
     */
    public static function mustDeclarePartiya(int $currentPile, int $tricksSoFar, int $maxTricksInRound = 5): bool
    {
        if ($currentPile > 5) {
            return false;
        }
        $remainingTricks = $maxTricksInRound - $tricksSoFar;

        return $currentPile === $remainingTricks;
    }

    /**
     * Check if the game has ended (any player's pile reached 0).
     *
     * @param  array<int, int>  $pileByPlayer
     */
    public static function isGameEnd(array $pileByPlayer): bool
    {
        foreach ($pileByPlayer as $pile) {
            if ($pile <= 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine the winner: player with the lowest pile; tie-break by who reached it last (requires round tracking).
     *
     * @param  array<int, int>  $pileByPlayer
     * @param  array<int, int>  $roundWhenPileReached  // optional: round index when each player reached 0
     */
    public static function winnerPlayerIndex(array $pileByPlayer, array $roundWhenPileReached = []): int
    {
        $minPile = min($pileByPlayer);
        $candidates = array_keys($pileByPlayer, $minPile);

        if (count($candidates) === 1) {
            return $candidates[0];
        }

        // Tie-break: player who reached this pile later (higher round index)
        $maxRound = -1;
        $winner = $candidates[0];
        foreach ($candidates as $i) {
            $round = $roundWhenPileReached[$i] ?? -1;
            if ($round > $maxRound) {
                $maxRound = $round;
                $winner = $i;
            }
        }

        return $winner;
    }
}
