<?php

namespace App\Domain\Rams;

use RuntimeException;

final class CardCodec
{
    public static function encode(Card $card): array
    {
        return [
            'suit' => $card->suit->value,
            'rank' => $card->rank->value,
        ];
    }

    public static function decode(array $payload): Card
    {
        $suit = $payload['suit'] ?? null;
        $rank = $payload['rank'] ?? null;

        if (! is_string($suit) || ! is_int($rank)) {
            throw new RuntimeException('Invalid card payload.');
        }

        return new Card(Suit::from($suit), Rank::from($rank));
    }

    public static function encodeId(Card $card): string
    {
        return $card->id();
    }

    public static function decodeId(string $id): Card
    {
        $parts = explode('-', $id);
        if (count($parts) !== 2) {
            throw new RuntimeException('Invalid card id.');
        }

        [$suit, $rank] = $parts;

        if ($rank === '' || ! ctype_digit($rank)) {
            throw new RuntimeException('Invalid card id.');
        }

        return new Card(Suit::from($suit), Rank::from((int) $rank));
    }

    public static function decodeSuit(string $id): Suit
    {
        $parts = explode('-', $id);
        if (count($parts) !== 2) {
            throw new RuntimeException('Invalid card id.');
        }

        [$suit] = $parts;

        return Suit::from($suit);
    }
}
