<?php

namespace Tests\Unit\Rams;

use App\Domain\Rams\Card;
use App\Domain\Rams\Rank;
use App\Domain\Rams\Suit;
use App\Domain\Rams\TrickRules;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class TrickRulesTest extends TestCase
{
    public function test_must_follow_suit_when_possible(): void
    {
        $leadingSuit = Suit::Hearts;

        $hand = [
            new Card(Suit::Hearts, Rank::Six),
            new Card(Suit::Spades, Rank::Ace),
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Must follow suit when possible');

        TrickRules::assertLegalPlay(new Card(Suit::Spades, Rank::Ace), $hand, $leadingSuit);
    }

    public function test_can_slough_if_no_leading_suit(): void
    {
        $leadingSuit = Suit::Hearts;

        $hand = [
            new Card(Suit::Spades, Rank::Ace),
            new Card(Suit::Clubs, Rank::Six),
        ];

        TrickRules::assertLegalPlay(new Card(Suit::Spades, Rank::Ace), $hand, $leadingSuit);

        $this->assertTrue(true);
    }

    public function test_trick_winner_is_highest_of_leading_suit(): void
    {
        $plays = [
            ['player' => 0, 'card' => new Card(Suit::Diamonds, Rank::Six)],
            ['player' => 1, 'card' => new Card(Suit::Diamonds, Rank::King)],
            ['player' => 2, 'card' => new Card(Suit::Spades, Rank::Ace)],
            ['player' => 3, 'card' => new Card(Suit::Diamonds, Rank::Seven)],
        ];

        $this->assertSame(1, TrickRules::winnerPlayerIndex($plays));
    }

    public function test_turn_order_validation(): void
    {
        $plays = [
            ['player' => 2, 'card' => new Card(Suit::Diamonds, Rank::Six)],
            ['player' => 3, 'card' => new Card(Suit::Diamonds, Rank::King)],
        ];

        TrickRules::assertTurnOrder($plays, 2);

        $this->assertTrue(true);
    }
}
