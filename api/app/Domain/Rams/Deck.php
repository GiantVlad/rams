<?php

namespace App\Domain\Rams;

use InvalidArgumentException;
use RuntimeException;

final class Deck
{
    /** @var list<Card> */
    private array $cards;

    /**
     * @param  list<Card>  $cards
     */
    private function __construct(array $cards)
    {
        $this->cards = $cards;
    }

    public static function createStandard36(): self
    {
        $cards = [];

        $ranks = [
            Rank::Six,
            Rank::Seven,
            Rank::Eight,
            Rank::Nine,
            Rank::Ten,
            Rank::Jack,
            Rank::Queen,
            Rank::King,
            Rank::Ace,
        ];

        foreach (Suit::cases() as $suit) {
            foreach ($ranks as $rank) {
                $cards[] = new Card($suit, $rank);
            }
        }

        return new self($cards);
    }

    public function count(): int
    {
        return count($this->cards);
    }

    /**
     * @return list<Card>
     */
    public function cards(): array
    {
        return $this->cards;
    }

    public function shuffle(?int $seed = null): void
    {
        if ($seed === null) {
            shuffle($this->cards);

            return;
        }

        if ($seed < 0) {
            throw new InvalidArgumentException('Seed must be a non-negative integer.');
        }

        $engine = new \Random\Engine\Mt19937($seed);
        $randomizer = new \Random\Randomizer($engine);
        $this->cards = $randomizer->shuffleArray($this->cards);
    }

    public function draw(): Card
    {
        $card = array_pop($this->cards);

        if (! $card instanceof Card) {
            throw new RuntimeException('Deck is empty.');
        }

        return $card;
    }

    public function remove(Card $card): void
    {
        $key = array_search($card, $this->cards, true);
        if ($key !== false) {
            array_splice($this->cards, $key, 1);
        }
    }
}
