<?php

namespace App\Domain\Rams;

final readonly class Card
{
    public function __construct(
        public Suit $suit,
        public Rank $rank,
    ) {}

    public function equals(Card $other): bool
    {
        return $this->suit === $other->suit && $this->rank === $other->rank;
    }

    public function id(): string
    {
        return $this->suit->value.'-'.$this->rank->value;
    }
}
