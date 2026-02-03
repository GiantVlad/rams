<?php

namespace Tests\Unit\Rams;

use App\Domain\Rams\BiddingRules;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class BiddingRulesTest extends TestCase
{
    public function test_forbidden_last_bid_value(): void
    {
        $this->assertSame(2, BiddingRules::forbiddenLastBidValue(7));
        $this->assertSame(0, BiddingRules::forbiddenLastBidValue(9));
        $this->assertNull(BiddingRules::forbiddenLastBidValue(10));
        $this->assertNull(BiddingRules::forbiddenLastBidValue(-1));
    }

    public function test_sum_of_all_bids_must_not_equal_9(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Sum of all bids must not equal 9');

        BiddingRules::assertCompleteBids([
            0 => 2,
            1 => 2,
            2 => 2,
            3 => 3,
        ]);
    }

    public function test_valid_bids_pass(): void
    {
        BiddingRules::assertCompleteBids([
            0 => 2,
            1 => 2,
            2 => 2,
            3 => 4,
        ]);

        $this->assertTrue(true);
    }
}
