<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RamsGameApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_game_and_get_state(): void
    {
        $create = $this->postJson('/api/games', ['seed' => 123]);
        $create->assertStatus(201);

        $gameId = $create->json('game.id');
        $this->assertNotNull($gameId);

        $state = $this->getJson("/api/games/{$gameId}");
        $state->assertOk();
        $state->assertJsonPath('game.phase', 'bidding');
        $state->assertJsonPath('game.current_player_index', 0);
        $state->assertJsonCount(4, 'players');
        $state->assertJsonCount(4, 'round.hands');
        $state->assertJsonPath('round.trick_number', 1);

        // AI should have auto-bid for seats 1..3.
        $this->assertNotNull($state->json('round.bids.1'));
        $this->assertNotNull($state->json('round.bids.2'));
        $this->assertNotNull($state->json('round.bids.3'));
        $this->assertNull($state->json('round.bids.0'));
    }

    public function test_bidding_enforces_turn_and_sum_not_9(): void
    {
        $create = $this->postJson('/api/games', ['seed' => 123]);
        $gameId = $create->json('game.id');

        // AI auto-bids for seats 1..3, so human (0) is the current bidder.
        $badTurn = $this->postJson("/api/games/{$gameId}/bid", ['player_index' => 1, 'bid' => 2]);
        $badTurn->assertStatus(422);

        // With AI bids in place, choose a value that would make the sum 9 and ensure it's rejected.
        $sum9 = $this->postJson("/api/games/{$gameId}/bid", ['player_index' => 0, 'bid' => 9]);
        $sum9->assertStatus(422);

        // Valid alternative bid
        $ok = $this->postJson("/api/games/{$gameId}/bid", ['player_index' => 0, 'bid' => 1]);
        $ok->assertOk();
        $ok->assertJsonPath('game.phase', 'playing');
    }

    public function test_playing_enforces_follow_suit(): void
    {
        $create = $this->postJson('/api/games', ['seed' => 123]);
        $gameId = $create->json('game.id');

        // Only human needs to bid; AI already did. After this bid, AI will start playing until it's human's turn.
        $this->postJson("/api/games/{$gameId}/bid", ['player_index' => 0, 'bid' => 1])->assertOk();

        $state = $this->getJson("/api/games/{$gameId}");
        $state->assertOk();
        $this->assertSame('playing', $state->json('game.phase'));

        // AI should have played 3 cards (players 1..3). Now it is human's turn (0).
        $this->assertSame(0, $state->json('game.current_player_index'));
        $this->assertCount(3, $state->json('round.current_trick'));

        $leadingCardId = $state->json('round.current_trick.0.card');
        $leadingSuit = explode('-', $leadingCardId)[0];

        $hand0 = $state->json('round.hands.0');
        $this->assertIsArray($hand0);

        $nonLeading = null;
        $hasLeading = false;
        foreach ($hand0 as $id) {
            $suit = explode('-', $id)[0];
            if ($suit === $leadingSuit) {
                $hasLeading = true;
            } else {
                $nonLeading = $nonLeading ?? $id;
            }
        }

        if ($hasLeading && $nonLeading !== null) {
            $bad = $this->postJson("/api/games/{$gameId}/move", ['player_index' => 0, 'card_id' => $nonLeading]);
            $bad->assertStatus(422);
        } else {
            $okId = $hand0[0];
            $ok = $this->postJson("/api/games/{$gameId}/move", ['player_index' => 0, 'card_id' => $okId]);
            $ok->assertOk();
        }
    }
}
