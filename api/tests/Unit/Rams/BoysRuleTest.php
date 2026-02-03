<?php

namespace Tests\Unit\Rams;

use App\Domain\Rams\Suit;
use App\Models\Game;
use App\Models\GameRound;
use App\Models\Player;
use App\Services\Rams\AiService;
use App\Services\Rams\GameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BoysRuleTest extends TestCase
{
    use RefreshDatabase;

    private GameService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GameService(new AiService);
    }

    public function test_boys_detection_and_announcement()
    {
        // 1. Setup Game and Players
        $game = Game::create([
            'status' => 'in_progress',
            'phase' => 'choose_to_play', // Prepare to transition to play
            'dealer_index' => 0,
            'current_player_index' => 1,
            'round_number' => 1,
            'trump_card_id' => 'H-14', // Ace of Hearts
        ]);

        $players = [];
        for ($i = 0; $i < 4; $i++) {
            $players[$i] = Player::create([
                'game_id' => $game->id,
                'seat_index' => $i,
                'type' => 'ai',
                'score' => 0,
                'pile' => 25,
                'maltzy_count' => 0,
            ]);
        }

        // 2. Setup Round with "Boys" for Player 1 (Red Pair: JH, JD)
        // Player 1 has Jack Hearts (H-11) and Jack Diamonds (D-11)
        $p1Hand = ['H-11', 'D-11', 'S-10', 'C-10', 'H-10'];
        $hands = [
            0 => ['S-14', 'S-13', 'S-12', 'S-9', 'S-8'],
            1 => $p1Hand,
            2 => ['C-14', 'C-13', 'C-12', 'C-9', 'C-8'],
            3 => ['D-14', 'D-13', 'D-12', 'D-9', 'D-8'],
        ];

        $round = GameRound::create([
            'game_id' => $game->id,
            'number' => 1,
            'dealer_index' => 0,
            'seed' => 123,
            'hands' => $hands,
            'exchanged' => [0, 0, 0, 0],
            'taken' => [0, 0, 0, 0],
            'trick_number' => 1,
            'current_trick' => [],
            'passed_players' => [], // Everyone playing
        ]);

        // 3. Transition to 'play' phase (trigger detectBoys)
        // Player 1 (current) decides to play
        $this->service->decideParticipation($game, 1, true);

        // Advance for others
        $this->service->decideParticipation($game, 2, true);
        $this->service->decideParticipation($game, 3, true);
        // Dealer (0) decides -> triggers phase change
        $this->service->decideParticipation($game, 0, true);

        $game->refresh();
        $round->refresh();
        $players[1]->refresh();

        // Check Phase
        $this->assertSame('play', $game->phase);

        // Check Detection
        $this->assertNotNull($round->boys_state);
        $this->assertArrayHasKey(1, $round->boys_state);
        $this->assertArrayHasKey('red', $round->boys_state[1]);
        $this->assertEquals(0, $round->boys_state[1]['red']['played']);

        // Check Maltzy Count (+1 for the pair)
        $this->assertEquals(1, $players[1]->maltzy_count);

        // 4. Play First Jack (H-11)
        // It's Player 1's turn (left of dealer 0)
        $this->assertEquals(1, $game->current_player_index);

        // We need to capture the broadcast but it's hard in unit test without mocking curl.
        // However, we can check side effects in DB (boys_state update).

        $this->service->playCard($game, 1, 'H-11');

        $round->refresh();
        $this->assertEquals(1, $round->boys_state[1]['red']['played']);

        // 5. Play Second Jack (D-11) later
        // Need to cycle through turns to get back to Player 1
        // P2 plays
        $this->service->playCard($game, 2, 'C-14');
        // P3 plays
        $this->service->playCard($game, 3, 'D-14');
        // P0 plays
        $this->service->playCard($game, 0, 'S-14');

        // End of trick 1. Winner determined.
        // H-11 (J-H), C-14, D-14, S-14. Lead is Hearts.
        // Others failed to follow suit?
        // P2 had clubs, played clubs. (H lead -> P2 had no hearts? Let's check hand)
        // P2 hand: C-14, C-13, C-12, C-9, C-8. No hearts. Legal.
        // P3 hand: D-14... No hearts. Legal.
        // P0 hand: S-14... No hearts. Legal.
        // Winner: P1 (H-11) - highest and only trump/lead suit card?
        // Trump is Ace Hearts (H-14).
        // H-11 is trump.
        // Winner is P1.

        $game->refresh();
        $this->assertEquals(1, $game->current_player_index);

        // P1 plays second Jack (D-11)
        $this->service->playCard($game, 1, 'D-11');

        $round->refresh();
        $this->assertEquals(2, $round->boys_state[1]['red']['played']);

        // If we could spy on Log, we'd see "Broadcasting... boys.announcement".
    }
}
