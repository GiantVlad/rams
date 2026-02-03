<?php

namespace App\Services\Rams;

use App\Domain\Rams\CardCodec;
use App\Domain\Rams\Rank;
use App\Domain\Rams\Suit;

final class AiService
{
    public function chooseDiscardCards(array $handCardIds, ?string $trumpCardId = null): array
    {
        if (empty($handCardIds)) {
            return [];
        }

        $trumpSuit = $trumpCardId ? CardCodec::decodeSuit($trumpCardId) : null;
        $discardCandidates = [];

        foreach ($handCardIds as $cardId) {
            if (! is_string($cardId)) {
                continue;
            }

            $card = CardCodec::decodeId($cardId);

            // Never exchange trump cards
            if ($trumpSuit && $card->suit === $trumpSuit) {
                continue;
            }

            // Never exchange Aces
            if ($card->rank === Rank::Ace) {
                continue;
            }

            $discardCandidates[] = [
                'id' => $cardId,
                'rank' => $card->rank->value,
            ];
        }

        // Sort by rank (lowest first) to exchange worst cards first
        usort($discardCandidates, function ($a, $b) {
            return $a['rank'] <=> $b['rank'];
        });

        // Exchange up to 3 cards (but keep at least 2 cards)
        $maxExchange = min(3, count($discardCandidates), max(0, count($handCardIds) - 2));

        return array_slice(array_column($discardCandidates, 'id'), 0, $maxExchange);
    }

    public function chooseCardIdToPlay(array $handCardIds, array $currentTrick, ?string $trumpCardId = null): string
    {
        $leadingSuit = $this->leadingSuit($currentTrick);
        $trumpSuit = $trumpCardId ? CardCodec::decodeSuit($trumpCardId) : null;

        $candidates = [];
        $hasLeadingSuit = false;
        $hasTrump = false;

        // First pass: identify playable cards and if we have leading suit/trumps
        foreach ($handCardIds as $id) {
            if (! is_string($id)) {
                continue;
            }

            $card = CardCodec::decodeId($id);
            $isTrump = $trumpSuit && $card->suit === $trumpSuit;
            $isLeading = $leadingSuit && $card->suit === $leadingSuit;

            if ($isLeading) {
                $hasLeadingSuit = true;
            }
            if ($isTrump) {
                $hasTrump = true;
            }

            $candidates[] = [
                'id' => $id,
                'card' => $card,
                'rank' => $card->rank->value,
                'isTrump' => $isTrump,
                'isLeading' => $isLeading,
            ];
        }

        // Filter for legal plays
        $legalPlays = array_filter($candidates, function ($c) use ($leadingSuit, $hasLeadingSuit) {
            // If leading suit exists:
            if ($leadingSuit) {
                // Must follow suit if we have it
                if ($hasLeadingSuit) {
                    return $c['isLeading'];
                }
            }

            // Otherwise any card is legal
            return true;
        });

        // If something went wrong and no legal plays (shouldn't happen), revert to all candidates
        if (empty($legalPlays)) {
            $legalPlays = $candidates;
        }

        // Determine current highest card in trick to beat
        $highestCardInTrick = null;
        $highestIsTrump = false;

        foreach ($currentTrick as $play) {
            if (! isset($play['card']) || ! is_string($play['card'])) {
                continue;
            }

            $card = CardCodec::decodeId($play['card']);
            $isTrump = $trumpSuit && $card->suit === $trumpSuit;
            $isLeading = $leadingSuit && $card->suit === $leadingSuit;

            if ($highestCardInTrick === null) {
                $highestCardInTrick = $card;
                $highestIsTrump = $isTrump;

                continue;
            }

            if ($isTrump && ! $highestIsTrump) {
                $highestCardInTrick = $card;
                $highestIsTrump = true;
            } elseif ($isTrump && $highestIsTrump) {
                if ($card->rank->value > $highestCardInTrick->rank->value) {
                    $highestCardInTrick = $card;
                }
            } elseif (! $isTrump && ! $highestIsTrump && $isLeading) {
                if ($card->rank->value > $highestCardInTrick->rank->value) {
                    $highestCardInTrick = $card;
                }
            }
        }

        // Strategy: Try to win cheaply
        $winningPlays = [];
        $losingPlays = [];

        foreach ($legalPlays as $play) {
            $wins = false;
            if ($highestCardInTrick === null) {
                // We are leading the trick. Assume we "win" for now to prioritize high cards?
                // Actually, for leading:
                // 1. Lead Ace (almost guaranteed win)
                // 2. Lead high trump (draw trumps)
                // 3. Lead low card (safe)
                // For now, treat all leads as "winning" candidates and sort them later
                $wins = true;
            } else {
                if ($play['isTrump']) {
                    if (! $highestIsTrump) {
                        $wins = true;
                    } elseif ($play['rank'] > $highestCardInTrick->rank->value) {
                        $wins = true;
                    }
                } elseif ($play['isLeading']) {
                    // Can only win if no trumps played and we are higher
                    if (! $highestIsTrump && $play['rank'] > $highestCardInTrick->rank->value) {
                        $wins = true;
                    }
                }
            }

            if ($wins) {
                $winningPlays[] = $play;
            } else {
                $losingPlays[] = $play;
            }
        }

        // If leading, prioritize Aces, then low cards?
        if ($highestCardInTrick === null) {
            usort($winningPlays, function ($a, $b) {
                // If one is Ace and other isn't, Ace wins priority
                $aIsAce = $a['rank'] === 14;
                $bIsAce = $b['rank'] === 14;
                if ($aIsAce !== $bIsAce) {
                    return $bIsAce <=> $aIsAce;
                } // Ace first

                // If one is Trump and other isn't, prefer Non-Trump to save trumps?
                // Or lead trumps to drain? Let's prefer playing Aces of non-trump first.
                if ($a['isTrump'] !== $b['isTrump']) {
                    return $a['isTrump'] <=> $b['isTrump'];
                } // Non-trump first

                // Otherwise play highest rank? Or lowest?
                // Basic strategy: Play highest to win, or lowest to save.
                // Let's play lowest to be safe if not Ace.
                return $a['rank'] <=> $b['rank'];
            });

            return $winningPlays[0]['id'];
        }

        // Following:
        // Sort winning plays: Lowest rank first (win cheaply)
        usort($winningPlays, function ($a, $b) {
            return $a['rank'] <=> $b['rank'];
        });

        // Sort losing plays: Lowest rank first (discard trash)
        usort($losingPlays, function ($a, $b) {
            return $a['rank'] <=> $b['rank'];
        });

        if (! empty($winningPlays)) {
            return $winningPlays[0]['id'];
        }

        if (! empty($losingPlays)) {
            return $losingPlays[0]['id'];
        }

        // Fallback
        return $legalPlays[0]['id'] ?? $handCardIds[0];
    }

    private function leadingSuit(array $currentTrick): ?Suit
    {
        if (count($currentTrick) === 0) {
            return null;
        }

        $first = $currentTrick[0] ?? null;
        if (! is_array($first) || ! isset($first['card']) || ! is_string($first['card'])) {
            return null;
        }

        return CardCodec::decodeId($first['card'])->suit;
    }

    public function chooseToPlay(array $handCardIds, ?string $trumpCardId = null): bool
    {
        $trumpSuit = $trumpCardId ? CardCodec::decodeSuit($trumpCardId) : null;

        $hasTrump = false;
        $hasAce = false;

        foreach ($handCardIds as $cardId) {
            $card = CardCodec::decodeId($cardId);
            if ($trumpSuit && $card->suit === $trumpSuit) {
                $hasTrump = true;
            }
            if ($card->rank === Rank::Ace) {
                $hasAce = true;
            }
        }

        // Play if hand has at least one Trump or one Ace
        return $hasTrump || $hasAce;
    }
}
