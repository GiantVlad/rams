<?php

namespace App\Services\Rams;

use App\Domain\Rams\Card;
use App\Domain\Rams\CardCodec;
use App\Domain\Rams\Dealing;
use App\Domain\Rams\Deck;
use App\Domain\Rams\Exchange;
use App\Domain\Rams\Scoring;
use App\Domain\Rams\Suit;
use App\Domain\Rams\TrickRules;
use App\Models\Game;
use App\Models\GameRound;
use App\Models\Player;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class GameService
{
    public function __construct(private readonly AiService $ai) {}

    private function broadcastGameUpdate(Game $game, string $event = 'update', ?array $extraData = null): void
    {
        $state = $this->getState($game);
        if ($extraData) {
            $state = array_merge($state, $extraData);
        }

        Log::info("Broadcasting game update: {$event} for game {$game->id}");

        // Send to WebSocket server via HTTP POST
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8080/broadcast');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'gameId' => $game->id,
            'event' => $event,
            'data' => $state,
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1); // Quick timeout

        $result = @curl_exec($ch);
        if ($result === false) {
            Log::error('Failed to broadcast game update: '.curl_error($ch));
        } else {
            Log::info('Broadcast successful: '.$result);
        }
        curl_close($ch);
    }

    public function createGame(?int $seed = null): Game
    {
        $game = DB::transaction(function () use ($seed) {
            $dealerIndex = 0;

            $game = Game::create([
                'status' => 'in_progress',
                'phase' => 'exchange',
                'dealer_index' => $dealerIndex,
                'current_player_index' => ($dealerIndex + 1) % 4,
                'round_number' => 1,
            ]);

            for ($i = 0; $i < 4; $i++) {
                Player::create([
                    'game_id' => $game->id,
                    'seat_index' => $i,
                    'type' => $i === 0 ? 'human' : 'ai',
                    'score' => 0,
                    'pile' => 20,
                    'maltzy_count' => 0,
                ]);
            }

            $this->createRound($game, $seed);

            return $game->fresh(['players', 'rounds']);
        });

        // Don't auto-progress for new games - let the user see the exchange phase
        $game = $game->fresh(['players', 'rounds']);
        $this->broadcastGameUpdate($game, 'game.created');

        return $game;
    }

    public function getState(Game $game): array
    {
        $game->loadMissing(['players', 'rounds']);
        $round = $game->currentRound();

        if (! $round) {
            throw new RuntimeException('Game has no active round.');
        }

        return [
            'game' => [
                'id' => $game->id,
                'status' => $game->status,
                'phase' => $game->phase,
                'dealer_index' => $game->dealer_index,
                'current_player_index' => $game->current_player_index,
                'round_number' => $game->round_number,
                'winner_player_index' => $game->winner_player_index,
                'trump_card_id' => $game->trump_card_id,
            ],
            'players' => $game->players
                ->sortBy('seat_index')
                ->values()
                ->map(fn (Player $p) => [
                    'seat_index' => $p->seat_index,
                    'type' => $p->type,
                    'score' => $p->score,
                    'pile' => $p->pile,
                    'maltzy_count' => $p->maltzy_count,
                ])
                ->all(),
            'round' => [
                'number' => $round->number,
                'remaining_deck_count' => count($round->remaining_deck ?? []),
                'dealer_index' => $round->dealer_index,
                'seed' => $round->seed,
                'hands' => $round->hands,
                'exchanged' => $round->exchanged,
                'taken' => $round->taken,
                'trick_number' => $round->trick_number,
                'current_trick' => $round->current_trick,
                'five_same_suit_declared' => $round->five_same_suit_declared,
                'partiya_declared_by' => $round->partiya_declared_by,
                'passed_players' => $round->passed_players ?? [],
            ],
            'exchangeStatus' => $game->phase === 'exchange' ? $this->getExchangeStatus($game, $round) : null,
        ];
    }

    private function getExchangeStatus(Game $game, GameRound $round): ?string
    {
        $currentPlayer = $game->current_player_index;
        $exchanged = $round->exchanged ?? [0, 0, 0, 0];

        // Get dealer index to determine who has exchanged
        $dealerIndex = $game->dealer_index;
        $exchangeOrder = [];

        // Build exchange order: starts after dealer
        for ($i = 1; $i <= 4; $i++) {
            $playerIndex = ($dealerIndex + $i) % 4;
            $exchangeOrder[] = $playerIndex;
        }

        // Build status message showing what happened so far
        $statusParts = [];
        foreach ($exchangeOrder as $i) {
            if ($i < $currentPlayer) {
                // This player has already exchanged
                $statusParts[] = "P{$i} changed {$exchanged[$i]} cards";
            } elseif ($i === $currentPlayer) {
                // This player is currently exchanging
                if (empty($statusParts)) {
                    return "P{$i} exchanging...";
                } else {
                    return implode(' → ', $statusParts)." → P{$i} exchanging...";
                }
            }
        }

        // If we get here, all players have exchanged
        return implode(' → ', array_map(fn ($i) => "P{$i} changed {$exchanged[$i]} cards", $exchangeOrder));
    }

    public function exchangeCards(Game $game, int $playerIndex, array $discardCardIds): Game
    {
        $updated = $this->exchangeCardsOnce($game, $playerIndex, $discardCardIds);

        $this->broadcastGameUpdate($updated, 'exchange.completed');

        return $updated;
    }

    private function exchangeCardsOnce(Game $game, int $playerIndex, array $discardCardIds): Game
    {
        return DB::transaction(function () use ($game, $playerIndex, $discardCardIds) {
            $game->refresh();

            if ($game->status !== 'in_progress') {
                throw new RuntimeException('Game is not in progress.');
            }

            if ($game->phase !== 'exchange') {
                throw new RuntimeException('Game is not in exchange phase.');
            }

            if ($game->current_player_index !== $playerIndex) {
                throw new RuntimeException('Not your turn.');
            }

            $round = $game->currentRound();
            if (! $round) {
                throw new RuntimeException('Game has no active round.');
            }

            // Check if remaining deck is empty - if so, complete exchange phase
            $remainingDeckCards = $round->remaining_deck ?? [];
            if (empty($remainingDeckCards)) {
                // No cards left for exchange, move to choose_to_play phase
                $game->phase = 'choose_to_play';
                $game->current_player_index = ($game->dealer_index + 1) % 4;
                $game->save();

                return $game->fresh(['players', 'rounds']);
            }

            $hands = $round->hands;
            if (! is_array($hands)) {
                throw new RuntimeException('Invalid hands state.');
            }

            $handCardIds = $hands[$playerIndex] ?? [];
            $remainingDeckCount = count($round->remaining_deck ?? []);
            Exchange::validate($playerIndex, $handCardIds, $discardCardIds, $remainingDeckCount);

            // Record exchange count
            $exchanged = $round->exchanged;
            if (! is_array($exchanged)) {
                $exchanged = [0, 0, 0, 0];
            }
            $exchanged[$playerIndex] = count($discardCardIds);
            $round->exchanged = $exchanged;

            // Remove discarded cards from hand
            $discardSet = array_flip($discardCardIds);
            $newHand = array_values(array_filter($handCardIds, fn ($id) => ! isset($discardSet[$id])));

            error_log("Exchange Debug - Player {$playerIndex}:");
            error_log('  Original hand: '.json_encode($handCardIds));
            error_log('  Discarding: '.json_encode($discardCardIds));
            error_log('  After discard: '.json_encode($newHand));

            // Use the stored remaining deck for exchanges
            $remainingDeckCards = $round->remaining_deck ?? [];

            error_log('  Cards in remaining deck: '.count($remainingDeckCards));

            // Draw new cards to replace discarded ones
            for ($i = 0; $i < count($discardCardIds); $i++) {
                if (! empty($remainingDeckCards)) {
                    $newCardId = array_shift($remainingDeckCards);
                    $newHand[] = $newCardId;
                    error_log('  Drew card: '.$newCardId);
                }
            }

            // Update the remaining deck in the round
            $round->remaining_deck = $remainingDeckCards;

            error_log('  Final hand: '.json_encode($newHand));

            // Update the hand in the hands array
            $hands[$playerIndex] = $newHand;
            $round->hands = $hands;
            $round->save();

            // Check for Five Same Suit immediate win
            if ($this->checkAndApplyFiveSameSuit($game, $round)) {
                return $game->fresh(['players', 'rounds']);
            }

            // Advance turn
            $nextPlayer = ($playerIndex + 1) % 4;
            if ($playerIndex === $game->dealer_index) {
                // Dealer just exchanged; move to choose_to_play phase
                $game->phase = 'choose_to_play';
                $game->current_player_index = ($game->dealer_index + 1) % 4;
            } else {
                $game->current_player_index = $nextPlayer;
            }

            $game->save();

            return $game->fresh(['players', 'rounds']);
        });
    }

    public function decideParticipation(Game $game, int $playerIndex, bool $play): Game
    {
        $updated = $this->decideParticipationOnce($game, $playerIndex, $play);
        $this->broadcastGameUpdate($updated, 'participation.decided');

        return $updated;
    }

    private function decideParticipationOnce(Game $game, int $playerIndex, bool $play): Game
    {
        return DB::transaction(function () use ($game, $playerIndex, $play) {
            $game->refresh();

            if ($game->phase !== 'choose_to_play') {
                throw new RuntimeException('Game is not in declaration phase.');
            }

            if ($game->current_player_index !== $playerIndex) {
                throw new RuntimeException('Not your turn.');
            }

            $round = $game->currentRound();
            $passed = $round->passed_players ?? [];
            if (! $play) {
                $passed[] = $playerIndex;
            }
            $round->passed_players = $passed;
            $round->save();

            $nextPlayer = ($playerIndex + 1) % 4;

            if ($playerIndex === $game->dealer_index) {
                // Dealer decided. Phase ends.
                $activeCount = 4 - count($passed);

                if ($activeCount <= 1) {
                    $winner = null;
                    if ($activeCount === 1) {
                        for ($i = 0; $i < 4; $i++) {
                            if (! in_array($i, $passed)) {
                                $winner = $i;
                                break;
                            }
                        }
                    } elseif ($activeCount === 0) {
                        $winner = $game->dealer_index;
                    }

                    if ($winner !== null) {
                        $players = $game->players()->get()->keyBy('seat_index');
                        $players[$winner]->pile = max(0, $players[$winner]->pile - 5);
                        $players[$winner]->save();

                        $this->startNextRoundOrFinish($game);
                    } else {
                        // Should not happen if logic is correct
                        $this->startNextRoundOrFinish($game);
                    }
                } else {
                    $game->phase = 'play';
                    $game->current_player_index = $this->getNextActivePlayer($game->dealer_index, $passed);

                    // Detect Boys (Two Jacks same color)
                    $this->detectBoys($game, $round);
                }
            } else {
                $game->current_player_index = $nextPlayer;
            }

            $game->save();

            return $game->fresh(['players', 'rounds']);
        });
    }

    private function getNextActivePlayer(int $currentIndex, array $passedPlayers): int
    {
        for ($i = 1; $i <= 4; $i++) {
            $next = ($currentIndex + $i) % 4;
            if (! in_array($next, $passedPlayers)) {
                return $next;
            }
        }

        return $currentIndex;
    }

    private function startNextRoundOrFinish(Game $game): void
    {
        $players = $game->players;
        $piles = $players->pluck('pile', 'seat_index')->all();

        if (Scoring::isGameEnd($piles)) {
            $game->status = 'finished';
            $game->winner_player_index = Scoring::winnerPlayerIndex($piles);
            $game->save();
        } else {
            $game->dealer_index = ($game->dealer_index + 1) % 4;
            $game->round_number = $game->round_number + 1;
            $game->phase = 'exchange';
            $game->current_player_index = ($game->dealer_index + 1) % 4;
            $game->save();
            $this->createRound($game);
        }
    }

    public function declareJacks(Game $game, int $playerIndex): Game
    {
        return DB::transaction(function () use ($game, $playerIndex) {
            $game->refresh();

            if ($game->status !== 'in_progress') {
                throw new RuntimeException('Game is not in progress.');
            }

            if ($game->phase !== 'play') {
                throw new RuntimeException('Jacks can only be declared during play phase.');
            }

            $player = $game->players()->where('seat_index', $playerIndex)->first();
            if (! $player) {
                throw new RuntimeException('Player not found.');
            }

            // Reduce pile to 5 and increment jacks count
            $player->pile = 5;
            $player->maltzy_count = ($player->maltzy_count ?? 0) + 1;
            $player->save();

            return $game->fresh(['players', 'rounds']);
        });
    }

    public function playCard(Game $game, int $playerIndex, string $cardId): Game
    {
        $updated = $this->playCardOnce($game, $playerIndex, $cardId);

        $this->broadcastGameUpdate($updated, 'card.played');

        return $updated;
    }

    private function playCardOnce(Game $game, int $playerIndex, string $cardId): Game
    {
        return DB::transaction(function () use ($game, $playerIndex, $cardId) {
            $game->refresh();

            if ($game->status !== 'in_progress') {
                throw new RuntimeException('Game is not in progress.');
            }

            if ($game->phase !== 'play') {
                throw new RuntimeException('Game is not in play phase.');
            }

            if ($game->current_player_index !== $playerIndex) {
                throw new RuntimeException('Not your turn.');
            }

            $round = $game->currentRound();
            if (! $round) {
                throw new RuntimeException('Game has no active round.');
            }

            $hands = $round->hands;
            if (! is_array($hands)) {
                throw new RuntimeException('Invalid hands state.');
            }

            $handCardIds = $hands[$playerIndex] ?? [];

            // Check if player has any cards left
            if (empty($handCardIds)) {
                // If player has no cards, the round should be over
                $this->finishRound($game);

                return $game->fresh(['players', 'rounds']);
            }

            $handCards = array_map(fn (string $id) => CardCodec::decodeId($id), $handCardIds);

            $cardToPlay = null;
            foreach ($handCards as $card) {
                if (CardCodec::encodeId($card) === $cardId) {
                    $cardToPlay = $card;
                    break;
                }
            }
            if (! $cardToPlay) {
                throw new RuntimeException('Card not in hand.');
            }

            // Check Boys Announcement
            $boysState = $round->boys_state;
            if ($boysState && isset($boysState[$playerIndex])) {
                if ($cardToPlay->rank === \App\Domain\Rams\Rank::Jack) {
                    $color = match ($cardToPlay->suit) {
                        \App\Domain\Rams\Suit::Hearts, \App\Domain\Rams\Suit::Diamonds => 'red',
                        \App\Domain\Rams\Suit::Spades, \App\Domain\Rams\Suit::Clubs => 'black',
                    };

                    if (isset($boysState[$playerIndex][$color])) {
                        $boysState[$playerIndex][$color]['played']++;
                        $count = $boysState[$playerIndex][$color]['played'];
                        $round->boys_state = $boysState;

                        $message = $count === 1 ? 'first Jack came out' : 'second Jack came out';
                        $this->broadcastGameUpdate($game, 'boys.announcement', [
                            'message' => $message,
                            'playerIndex' => $playerIndex,
                        ]);
                    }
                }
            }

            $currentTrick = $round->current_trick ?? [];
            $leadingSuit = $this->leadingSuitFromTrick($currentTrick);
            $trumpSuit = $game->trump_card_id ? CardCodec::decodeSuit($game->trump_card_id) : null;

            TrickRules::assertLegalPlay($cardToPlay, $handCards, $leadingSuit, $trumpSuit);

            // Add card to trick
            $currentTrick[] = ['player' => $playerIndex, 'card' => $cardId];
            $round->current_trick = $currentTrick;

            // Remove card from hand
            $hands[$playerIndex] = array_values(array_filter($handCardIds, fn ($id) => $id !== $cardId));
            $round->hands = $hands;

            $passedCount = count($round->passed_players ?? []);
            $activeCount = 4 - $passedCount;

            // Check if trick is complete
            if (count($currentTrick) === $activeCount) {
                // Save round state with full trick so players can see the last card
                $round->save();

                // If AI made the last move, broadcast so frontend sees the card
                $player = Player::where('game_id', $game->id)->where('seat_index', $playerIndex)->first();
                if ($player && $player->type === 'ai') {
                    $this->broadcastGameUpdate($game, 'card.played');
                }

                $plays = [];
                foreach ($currentTrick as $play) {
                    $plays[] = ['player' => $play['player'], 'card' => CardCodec::decodeId($play['card'])];
                }
                $winner = TrickRules::winnerPlayerIndex($plays, $trumpSuit);

                $taken = $round->taken ?? [0, 0, 0, 0];
                $taken[$winner] = ($taken[$winner] ?? 0) + 1;
                $round->taken = $taken;

                $round->current_trick = [];

                // Check if round is over (5 tricks)
                $totalTricks = array_sum($taken);
                if ($totalTricks >= 5) {
                    // Save the round state before finishing
                    $round->save();
                    $this->finishRound($game);
                } else {
                    $round->trick_number = $round->trick_number + 1;
                    $game->current_player_index = $winner;
                }
            } else {
                // Next player in trick
                $game->current_player_index = $this->getNextActivePlayer($playerIndex, $round->passed_players ?? []);
            }

            $round->save();
            $game->save();

            return $game->fresh(['players', 'rounds']);
        });
    }

    private function finishRound(Game $game): void
    {
        $round = $game->currentRound();
        if (! $round) {
            throw new RuntimeException('No active round to finish.');
        }

        $taken = $round->taken;
        if (! is_array($taken)) {
            $taken = [0, 0, 0, 0, 0];
        }

        $passedPlayers = $round->passed_players ?? [];

        $players = $game->players()->get()->keyBy('seat_index');
        $piles = [];
        $maltzyCounts = [];
        foreach ($players as $seat => $player) {
            $piles[$seat] = $player->pile ?? 20;
            $maltzyCounts[$seat] = $player->maltzy_count ?? 0;
        }

        $newPiles = Scoring::applyRoundScoring($taken, $maltzyCounts, $piles, $passedPlayers, 4);

        foreach ($newPiles as $seat => $newPile) {
            $player = $players->get($seat);
            if (! $player) {
                throw new RuntimeException("Missing player for seat {$seat}.");
            }
            $player->pile = $newPile;
            $player->save();
        }

        foreach ($players as $player) {
            $player->maltzy_count = 0;
            $player->save();
        }

        $this->startNextRoundOrFinish($game);
    }

    public function makeAiMove(Game $game): Game
    {
        $currentIndex = $game->current_player_index;

        // Only make one move, not progress through all AI turns
        if ($game->phase === 'exchange') {
            Log::info("AI Player {$currentIndex} starting exchange");
            // Broadcast that AI is starting to exchange
            $this->broadcastGameUpdate($game, 'exchange.started');

            // Get AI's card selection for exchange
            $round = $game->currentRound();
            $remainingDeckCards = $round->remaining_deck ?? [];

            // If no cards left in deck, AI can't exchange
            if (empty($remainingDeckCards)) {
                $discardCardIds = [];
            } else {
                $handCardIds = $round->hands[$currentIndex] ?? [];
                $discardCardIds = $this->ai->chooseDiscardCards($handCardIds, $game->trump_card_id);
            }

            $game = $this->exchangeCardsOnce($game, $currentIndex, $discardCardIds);
            Log::info("AI Player {$currentIndex} exchanged ".($game->currentRound()->exchanged[$currentIndex] ?? 0).' cards');
            // Broadcast that AI completed exchange
            $this->broadcastGameUpdate($game, 'exchange.completed');
        } elseif ($game->phase === 'choose_to_play') {
            $round = $game->currentRound();
            $handCardIds = $round->hands[$currentIndex] ?? [];
            $shouldPlay = $this->ai->chooseToPlay($handCardIds, $game->trump_card_id);

            $game = $this->decideParticipationOnce($game, $currentIndex, $shouldPlay);
            $this->broadcastGameUpdate($game, 'participation.decided');
        } elseif ($game->phase === 'play') {
            $round = $game->currentRound();
            if (! $round) {
                return $game;
            }
            $hands = $round->hands;
            $hand = $hands[$currentIndex] ?? [];
            if (! is_array($hand)) {
                return $game;
            }
            $cardId = $this->ai->chooseCardIdToPlay($hand, (array) $round->current_trick, $game->trump_card_id);
            $game = $this->playCardOnce($game, $currentIndex, $cardId);
            // Broadcast card played
            $this->broadcastGameUpdate($game, 'card.played');
        }

        return $game->fresh(['players', 'rounds']);
    }

    private function createRound(Game $game, ?int $seed = null): GameRound
    {
        $seed = $seed ?? random_int(1, PHP_INT_MAX);

        $deck = Deck::createStandard36();
        $deck->shuffle($seed);

        $deal = Dealing::deal($deck, 4, 5);

        $hands = [];
        foreach ($deal['hands'] as $playerIndex => $handCards) {
            $hands[$playerIndex] = array_map(fn (Card $c) => CardCodec::encodeId($c), $handCards);
        }

        // Reveal trump card: take the next card after dealing
        $trumpCard = $deck->draw();
        $trumpCardId = $trumpCard ? CardCodec::encodeId($trumpCard) : null;
        $game->trump_card_id = $trumpCardId;
        $game->save();

        // Store remaining deck cards for exchange phase
        $remainingDeckCards = [];
        try {
            while ($card = $deck->draw()) {
                $remainingDeckCards[] = CardCodec::encodeId($card);
            }
        } catch (RuntimeException $e) {
            // Deck is empty, which is expected
        }

        $round = GameRound::create([
            'game_id' => $game->id,
            'number' => $game->round_number,
            'dealer_index' => $game->dealer_index,
            'seed' => $seed,
            'hands' => $hands,
            'exchanged' => [0, 0, 0, 0],
            'taken' => [0, 0, 0, 0],
            'trick_number' => 1,
            'current_trick' => [],
            'five_same_suit_declared' => null,
            'partiya_declared_by' => null,
            'remaining_deck' => $remainingDeckCards,
        ]);

        $this->checkAndApplyFiveSameSuit($game, $round);

        return $round;
    }

    private function leadingSuitFromTrick(array $currentTrick): ?Suit
    {
        if (count($currentTrick) === 0) {
            return null;
        }

        $first = $currentTrick[0] ?? null;
        if (! is_array($first) || ! isset($first['card']) || ! is_string($first['card'])) {
            return null;
        }

        return CardCodec::decodeSuit($first['card']);
    }

    private function checkAndApplyFiveSameSuit(Game $game, GameRound $round): bool
    {
        $hands = $round->hands;
        if (! is_array($hands)) {
            return false;
        }

        // Check 4 players
        $winnerIndex = Exchange::priorityFiveSameSuit($hands, $game->dealer_index, 4);

        if ($winnerIndex !== null) {
            Log::info("Five Same Suit detected for player {$winnerIndex}");

            // Mark as declared
            $round->five_same_suit_declared = [$winnerIndex];
            $round->save();

            // Apply scoring: Winner -5, Others 0
            $players = $game->players()->get()->keyBy('seat_index');
            $pilesArray = [];

            foreach ($players as $seat => $player) {
                if ($seat === $winnerIndex) {
                    $player->pile = max(0, $player->pile - 5);
                }
                $player->save();
                $pilesArray[$seat] = $player->pile;
            }

            // Check Game End
            if (Scoring::isGameEnd($pilesArray)) {
                $game->status = 'finished';
                $game->winner_player_index = Scoring::winnerPlayerIndex($pilesArray);
                $game->save();
            } else {
                // Next Round
                $game->dealer_index = ($game->dealer_index + 1) % 4;
                $game->round_number = $game->round_number + 1;
                $game->phase = 'exchange';
                $game->current_player_index = ($game->dealer_index + 1) % 4;
                $game->save();

                $this->createRound($game);
            }

            return true;
        }

        return false;
    }

    private function detectBoys(Game $game, GameRound $round): void
    {
        $hands = $round->hands;
        $passedPlayers = $round->passed_players ?? [];
        $boysState = [];
        $players = $game->players()->get()->keyBy('seat_index');

        foreach ($hands as $playerIndex => $handCardIds) {
            if (in_array($playerIndex, $passedPlayers)) {
                continue;
            }

            $cards = array_map(fn ($id) => CardCodec::decodeId($id), $handCardIds);

            // Check for Red Jacks (H, D)
            $hasJackH = false;
            $hasJackD = false;
            // Check for Black Jacks (S, C)
            $hasJackS = false;
            $hasJackC = false;

            foreach ($cards as $card) {
                if ($card->rank === \App\Domain\Rams\Rank::Jack) {
                    if ($card->suit === \App\Domain\Rams\Suit::Hearts) {
                        $hasJackH = true;
                    }
                    if ($card->suit === \App\Domain\Rams\Suit::Diamonds) {
                        $hasJackD = true;
                    }
                    if ($card->suit === \App\Domain\Rams\Suit::Spades) {
                        $hasJackS = true;
                    }
                    if ($card->suit === \App\Domain\Rams\Suit::Clubs) {
                        $hasJackC = true;
                    }
                }
            }

            $playerBoys = [];
            $maltzyToAdd = 0;

            if ($hasJackH && $hasJackD) {
                $playerBoys['red'] = ['played' => 0];
                $maltzyToAdd++;
            }
            if ($hasJackS && $hasJackC) {
                $playerBoys['black'] = ['played' => 0];
                $maltzyToAdd++;
            }

            if (! empty($playerBoys)) {
                $boysState[$playerIndex] = $playerBoys;

                // Update player maltzy count
                $player = $players[$playerIndex];
                $player->maltzy_count = ($player->maltzy_count ?? 0) + $maltzyToAdd;
                $player->save();
            }
        }

        if (! empty($boysState)) {
            $round->boys_state = $boysState;
            $round->save();
        }
    }
}
