<?php

namespace Tests\Unit\Rams;

use App\Domain\Rams\Dealing;
use App\Domain\Rams\Deck;
use PHPUnit\Framework\TestCase;

class DealingTest extends TestCase
{
    public function test_deal_gives_9_cards_to_each_player_and_empties_deck(): void
    {
        $deck = Deck::createStandard36();
        $deck->shuffle(1);

        $result = Dealing::deal($deck, 4, 9);
        $hands = $result['hands'];

        $this->assertCount(4, $hands);
        $this->assertSame(0, $deck->count());

        foreach ($hands as $hand) {
            $this->assertCount(9, $hand);
        }

        $all = [];
        foreach ($hands as $hand) {
            foreach ($hand as $card) {
                $all[] = $card->id();
            }
        }

        $this->assertCount(36, $all);
        $this->assertCount(36, array_unique($all));
    }
}
