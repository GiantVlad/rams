<?php

namespace Tests\Unit\Rams;

use App\Domain\Rams\Scoring;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ScoringTest extends TestCase
{
    public function test_round_penalty_is_absolute_difference(): void
    {
        $this->assertSame(0, Scoring::roundPenalty(3, 3));
        $this->assertSame(2, Scoring::roundPenalty(1, 3));
        $this->assertSame(2, Scoring::roundPenalty(3, 1));
    }

    public function test_round_penalties_reject_sum_9_bids(): void
    {
        $this->expectException(RuntimeException::class);

        Scoring::roundPenalties(
            [0 => 0, 1 => 0, 2 => 0, 3 => 0],
            [0 => 2, 1 => 2, 2 => 2, 3 => 3],
        );
    }
}
