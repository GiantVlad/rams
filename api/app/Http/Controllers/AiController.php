<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Services\Rams\GameService;
use Illuminate\Http\JsonResponse;

class AiController extends Controller
{
    public function __construct(private readonly GameService $gameService) {}

    /**
     * Trigger AI to make its move
     */
    public function playTurn(Game $game): JsonResponse
    {
        try {
            // Only allow if it's an AI player's turn
            if ($game->current_player_index === 0) {
                return response()->json([
                    'error' => 'It is the human player\'s turn',
                ], 400);
            }

            // Make a single AI move
            $this->gameService->makeAiMove($game);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
