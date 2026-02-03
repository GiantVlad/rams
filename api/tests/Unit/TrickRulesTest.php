<?php

namespace Tests\Unit;

use App\Domain\Rams\Card;
use App\Domain\Rams\Rank;
use App\Domain\Rams\Suit;
use App\Domain\Rams\TrickRules;
use PHPUnit\Framework\TestCase;

class TrickRulesTest extends TestCase
{
    public function test_can_slough_if_cannot_follow_suit_and_has_trump()
    {
        // Hand: 7♦, 7♣ (Trump)
        // Lead: ♥
        // Player plays 7♦ (Slough)
        // Should NOT throw exception
        
        $hand = [
            new Card(Suit::Diamonds, Rank::Seven),
            new Card(Suit::Clubs, Rank::Seven),
        ];
        $cardToPlay = new Card(Suit::Diamonds, Rank::Seven);
        $leadingSuit = Suit::Hearts;
        $trumpSuit = Suit::Clubs;

        $exception = null;
        try {
            TrickRules::assertLegalPlay($cardToPlay, $hand, $leadingSuit, $trumpSuit);
        } catch (\Exception $e) {
            $exception = $e;
        }

        $this->assertNull($exception, 'Should allow sloughing even if holding trump');
    }

    public function test_must_follow_suit_if_possible()
    {
        // Hand: 7♥, 7♦
        // Lead: ♥
        // Player plays 7♦
        // Should throw exception
        
        $hand = [
            new Card(Suit::Hearts, Rank::Seven),
            new Card(Suit::Diamonds, Rank::Seven),
        ];
        $cardToPlay = new Card(Suit::Diamonds, Rank::Seven);
        $leadingSuit = Suit::Hearts;
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Must follow suit when possible');
        
        TrickRules::assertLegalPlay($cardToPlay, $hand, $leadingSuit);
    }
}
