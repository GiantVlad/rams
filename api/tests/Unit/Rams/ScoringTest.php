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

    public function test_is_game_end(): void
    {
        $this->assertTrue(Scoring::isGameEnd([0, 10, 15, 20]));
        $this->assertFalse(Scoring::isGameEnd([1, 10, 15, 20]));
    }
}