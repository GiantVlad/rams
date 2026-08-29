<?php

namespace Tests\Unit\Rams;

use App\Domain\Rams\Scoring;
use PHPUnit\Framework\TestCase;

class ScoringTest extends TestCase
{
    public function test_apply_round_scoring_reduces_pile_by_tricks(): void
    {
        $tricks = [0 => 3, 1 => 1, 2 => 1, 3 => 0];
        $maltzy = [0 => 0, 1 => 0, 2 => 0, 3 => 0];
        $piles = [0 => 20, 1 => 20, 2 => 20, 3 => 20];
        $passed = [];

        $newPiles = Scoring::applyRoundScoring($tricks, $maltzy, $piles, $passed);

        $this->assertEquals(17, $newPiles[0]); // 20 - 3
        $this->assertEquals(19, $newPiles[1]); // 20 - 1
        $this->assertEquals(19, $newPiles[2]); // 20 - 1
        $this->assertEquals(25, $newPiles[3]); // 20 - 0 + 5 (penalty for 0 tricks)
    }

    public function test_apply_round_scoring_passed_players_no_penalty(): void
    {
        $tricks = [0 => 5, 1 => 0, 2 => 0, 3 => 0];
        $maltzy = [0 => 0, 1 => 0, 2 => 0, 3 => 0];
        $piles = [0 => 20, 1 => 20, 2 => 20, 3 => 20];
        $passed = [1, 2, 3];

        $newPiles = Scoring::applyRoundScoring($tricks, $maltzy, $piles, $passed);

        $this->assertEquals(15, $newPiles[0]);
        $this->assertEquals(20, $newPiles[1]); // No penalty for passed
        $this->assertEquals(20, $newPiles[2]);
        $this->assertEquals(20, $newPiles[3]);
    }

    public function test_maltzy_reduces_pile(): void
    {
        $tricks = [0 => 1, 1 => 1, 2 => 1, 3 => 2];
        $maltzy = [0 => 1, 1 => 0, 2 => 0, 3 => 0];
        $piles = [0 => 20, 1 => 20, 2 => 20, 3 => 20];

        $newPiles = Scoring::applyRoundScoring($tricks, $maltzy, $piles);

        $this->assertEquals(14, $newPiles[0]); // 20 - 1 (trick) - 5 (maltzy)
    }

    public function test_must_declare_partiya(): void
    {
        $this->assertTrue(Scoring::mustDeclarePartiya(5, 0, 5));
        $this->assertTrue(Scoring::mustDeclarePartiya(3, 2, 5));
        $this->assertFalse(Scoring::mustDeclarePartiya(6, 0, 5));
        $this->assertFalse(Scoring::mustDeclarePartiya(4, 0, 5));
    }

    public function test_apply_round_scoring_rejects_tricks_out_of_range(): void
    {
        $tricks = [0 => 6, 1 => 1, 2 => 1, 3 => 0];
        $maltzy = [0 => 0, 1 => 0, 2 => 0, 3 => 0];
        $piles = [0 => 20, 1 => 20, 2 => 20, 3 => 20];
        $passed = [];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Tricks won must be between 0 and 5 for player 0.');

        Scoring::applyRoundScoring($tricks, $maltzy, $piles, $passed);
    }

    public function test_is_game_end(): void
    {
        $this->assertTrue(Scoring::isGameEnd([0, 10, 15, 20]));
        $this->assertFalse(Scoring::isGameEnd([1, 10, 15, 20]));
    }

    public function test_partiya_declared_player_exempt_from_zero_trick_penalty(): void
    {
        $tricks = [0 => 0, 1 => 1, 2 => 1, 3 => 2];
        $maltzy = [0 => 0, 1 => 0, 2 => 0, 3 => 0];
        $piles = [0 => 20, 1 => 20, 2 => 20, 3 => 20];

        // Without declaration: 0 tricks => +5 penalty => 25
        $without = Scoring::applyRoundScoring($tricks, $maltzy, $piles);
        $this->assertEquals(25, $without[0]);

        // With declaration: no penalty => 20
        $with = Scoring::applyRoundScoring($tricks, $maltzy, $piles, [], 4, [0 => true]);
        $this->assertEquals(20, $with[0]);
    }

    public function test_must_declare_partiya_when_eligible(): void
    {
        // pile=5, tricksSoFar=0, remaining=5 => must declare
        $this->assertTrue(Scoring::mustDeclarePartiya(5, 0));
        // pile=3, tricksSoFar=2, remaining=3 => must declare
        $this->assertTrue(Scoring::mustDeclarePartiya(3, 2));
        // pile=6 => no
        $this->assertFalse(Scoring::mustDeclarePartiya(6, 0));
        // pile=5, tricksSoFar=1, remaining=4 => no (5 != 4)
        $this->assertFalse(Scoring::mustDeclarePartiya(5, 1));
    }
}
