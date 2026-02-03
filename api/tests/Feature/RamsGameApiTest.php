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
        $state->assertJsonPath('game.phase', 'exchange');
        $state->assertJsonPath('game.current_player_index', 1); // Dealer is 0, so next is 1
        $state->assertJsonCount(4, 'players');
        $state->assertJsonCount(4, 'round.hands');
    }

    public function test_exchange_and_participation_flow(): void
    {
        $create = $this->postJson('/api/games', ['seed' => 123]);
        $gameId = $create->json('game.id');

        // Human is player 0. Dealer is 0. 
        // Order: 1 -> 2 -> 3 -> 0
        
        // Player 1 exchange
        $this->postJson("/api/games/{$gameId}/exchange", [
            'player_index' => 1,
            'discard_card_ids' => []
        ])->assertOk();

        // Player 2 exchange
        $this->postJson("/api/games/{$gameId}/exchange", [
            'player_index' => 2,
            'discard_card_ids' => []
        ])->assertOk();

        // Player 3 exchange
        $this->postJson("/api/games/{$gameId}/exchange", [
            'player_index' => 3,
            'discard_card_ids' => []
        ])->assertOk();

        // Player 0 (human) exchange
        $res = $this->postJson("/api/games/{$gameId}/exchange", [
            'player_index' => 0,
            'discard_card_ids' => []
        ]);
        $res->assertOk();

        $state = $this->getJson("/api/games/{$gameId}");
        $this->assertEquals('choose_to_play', $state->json('game.phase'));
        $this->assertEquals(1, $state->json('game.current_player_index'));

        // Participation phase
        $this->postJson("/api/games/{$gameId}/participation", ['player_index' => 1, 'play' => true])->assertOk();
        $this->postJson("/api/games/{$gameId}/participation", ['player_index' => 2, 'play' => true])->assertOk();
        $this->postJson("/api/games/{$gameId}/participation", ['player_index' => 3, 'play' => true])->assertOk();
        $this->postJson("/api/games/{$gameId}/participation", ['player_index' => 0, 'play' => true])->assertOk();

        $state = $this->getJson("/api/games/{$gameId}");
        $this->assertEquals('play', $state->json('game.phase'));
    }

    public function test_playing_enforces_follow_suit(): void
    {
        $create = $this->postJson('/api/games', ['seed' => 123]);
        $gameId = $create->json('game.id');

        // Skip exchange
        for ($i = 0; $i < 4; $i++) {
            $p = (0 + 1 + $i) % 4;
            $this->postJson("/api/games/{$gameId}/exchange", ['player_index' => $p, 'discard_card_ids' => []])->assertOk();
        }

        // Skip participation
        for ($i = 0; $i < 4; $i++) {
            $p = (0 + 1 + $i) % 4;
            $this->postJson("/api/games/{$gameId}/participation", ['player_index' => $p, 'play' => true])->assertOk();
        }

        $state = $this->getJson("/api/games/{$gameId}");
        $this->assertSame('play', $state->json('game.phase'));

        // Player 1 leads
        $currentPlayer = $state->json('game.current_player_index');
        $hand = $state->json("round.hands.{$currentPlayer}");
        $cardToPlay = $hand[0];

        $this->postJson("/api/games/{$gameId}/move", [
            'player_index' => $currentPlayer,
            'card_id' => $cardToPlay
        ])->assertOk();

        $state = $this->getJson("/api/games/{$gameId}");
        $nextPlayer = $state->json('game.current_player_index');
        $this->assertNotEquals($currentPlayer, $nextPlayer);
        $this->assertCount(1, $state->json('round.current_trick'));
    }
}