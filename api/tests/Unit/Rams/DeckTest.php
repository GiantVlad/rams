<?php

namespace Tests\Unit\Rams;

use App\Domain\Rams\Deck;
use PHPUnit\Framework\TestCase;

class DeckTest extends TestCase
{
    public function test_standard_36_deck_has_36_unique_cards(): void
    {
        $deck = Deck::createStandard36();

        $this->assertSame(36, $deck->count());

        $ids = array_map(fn ($c) => $c->id(), $deck->cards());
        $this->assertCount(36, array_unique($ids));
    }

    public function test_shuffle_with_seed_is_deterministic(): void
    {
        $d1 = Deck::createStandard36();
        $d1->shuffle(123);

        $d2 = Deck::createStandard36();
        $d2->shuffle(123);

        $this->assertSame(
            array_map(fn ($c) => $c->id(), $d1->cards()),
            array_map(fn ($c) => $c->id(), $d2->cards()),
        );
    }
}
