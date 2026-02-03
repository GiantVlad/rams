<?php

namespace App\Domain\Rams;

use InvalidArgumentException;
use RuntimeException;

final class BiddingRules
{
    public static function assertBidValue(int $bid): void
    {
        if ($bid < 0 || $bid > 9) {
            throw new InvalidArgumentException('Bid must be between 0 and 9.');
        }
    }

    /**
     * @param  array<int,int>  $bidsByPlayerIndex
     */
    public static function assertCompleteBids(array $bidsByPlayerIndex, int $players = 4): void
    {
        if (count($bidsByPlayerIndex) !== $players) {
            throw new RuntimeException("Expected {$players} bids.");
        }

        ksort($bidsByPlayerIndex);

        for ($i = 0; $i < $players; $i++) {
            if (! array_key_exists($i, $bidsByPlayerIndex)) {
                throw new RuntimeException("Missing bid for player {$i}.");
            }

            self::assertBidValue($bidsByPlayerIndex[$i]);
        }

        if (array_sum($bidsByPlayerIndex) === 9) {
            throw new RuntimeException('Sum of all bids must not equal 9.');
        }
    }

    public static function forbiddenLastBidValue(int $sumFirstThreeBids): ?int
    {
        $forbidden = 9 - $sumFirstThreeBids;

        if ($forbidden < 0 || $forbidden > 9) {
            return null;
        }

        return $forbidden;
    }
}
