<?php

namespace App\Domain\Rams;

use RuntimeException;

final class TrickRules
{
    /**
     * @param  list<Card>  $hand
     */
    public static function assertLegalPlay(Card $cardToPlay, array $hand, ?Suit $leadingSuit, ?Suit $trumpSuit = null): void
    {
        $inHand = false;
        foreach ($hand as $card) {
            if ($card->equals($cardToPlay)) {
                $inHand = true;
                break;
            }
        }

        if (! $inHand) {
            throw new RuntimeException('Card is not in hand.');
        }

        if ($leadingSuit === null) {
            return;
        }

        $hasLeadingSuit = false;
        $hasTrump = false;
        foreach ($hand as $card) {
            if ($card->suit === $leadingSuit) {
                $hasLeadingSuit = true;
            }
            if ($trumpSuit && $card->suit === $trumpSuit) {
                $hasTrump = true;
            }
        }

        // Debug: Log what we found
        error_log('Debug: leadingSuit='.($leadingSuit?->name ?? 'null').', trumpSuit='.($trumpSuit?->name ?? 'null').', hasLeadingSuit='.($hasLeadingSuit ? 'true' : 'false').', hasTrump='.($hasTrump ? 'true' : 'false'));
        error_log('Debug: cardToPlay suit='.$cardToPlay->suit->name);

        if ($hasLeadingSuit && $cardToPlay->suit !== $leadingSuit) {
            throw new RuntimeException('Must follow suit when possible.');
        }
    }

    /**
     * @param  list<array{player:int, card:Card}>  $plays
     */
    public static function leadingSuit(array $plays): ?Suit
    {
        if (count($plays) === 0) {
            return null;
        }

        return $plays[0]['card']->suit;
    }

    /**
     * @param  list<array{player:int, card:Card}>  $plays
     */
    public static function assertTurnOrder(array $plays, int $leaderPlayerIndex, int $players = 4): void
    {
        for ($i = 0; $i < count($plays); $i++) {
            $expected = ($leaderPlayerIndex + $i) % $players;
            $actual = $plays[$i]['player'];

            if ($actual !== $expected) {
                throw new RuntimeException("Invalid turn order: expected player {$expected}, got {$actual}.");
            }
        }
    }

    /**
     * @param  list<array{player:int, card:Card}>  $plays
     */
    public static function winnerPlayerIndex(array $plays, ?Suit $trumpSuit = null): int
    {
        if (count($plays) === 0) {
            throw new RuntimeException('Trick must contain at least 1 play.');
        }

        $leadingSuit = self::leadingSuit($plays);
        if ($leadingSuit === null) {
            throw new RuntimeException('Leading suit cannot be determined.');
        }

        $best = null;
        foreach ($plays as $play) {
            $isTrump = $trumpSuit && $play['card']->suit === $trumpSuit;
            $isLeading = $play['card']->suit === $leadingSuit;

            // Trump beats any non-trump
            if ($best !== null) {
                $bestIsTrump = $trumpSuit && $best['card']->suit === $trumpSuit;
                if ($isTrump && ! $bestIsTrump) {
                    $best = $play;

                    continue;
                }
                if (! $isTrump && $bestIsTrump) {
                    continue;
                }
            }

            // Within same suit type, higher rank wins
            if ($isLeading || ($trumpSuit && $isTrump)) {
                if ($best === null || $play['card']->rank->value > $best['card']->rank->value) {
                    $best = $play;
                }
            }
        }

        if ($best === null) {
            throw new RuntimeException('No winning card found in trick.');
        }

        return $best['player'];
    }
}
