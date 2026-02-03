<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Services\Rams\GameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class GameController extends Controller
{
    public function __construct(private readonly GameService $service) {}

    public function store(Request $request): JsonResponse
    {
        $seed = $request->input('seed');
        if ($seed !== null && ! is_int($seed)) {
            return response()->json(['message' => 'seed must be an integer'], 422);
        }

        $game = $this->service->createGame($seed);

        return response()->json($this->service->getState($game), 201);
    }

    public function show(Game $game): JsonResponse
    {
        return response()->json($this->service->getState($game));
    }

    public function exchange(Request $request, Game $game): JsonResponse
    {
        $playerIndex = $request->input('player_index');
        $discardCardIds = $request->input('discard_card_ids', []);

        if (! is_int($playerIndex) || $playerIndex < 0 || $playerIndex > 4) {
            return response()->json(['message' => 'player_index must be an integer 0..4'], 422);
        }

        if (! is_array($discardCardIds)) {
            return response()->json(['message' => 'discard_card_ids must be an array'], 422);
        }

        try {
            $this->service->exchangeCards($game, $playerIndex, $discardCardIds);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true]);
    }

    public function move(Request $request, Game $game): JsonResponse
    {
        $playerIndex = $request->input('player_index');
        $cardId = $request->input('card_id');

        if (! is_int($playerIndex) || $playerIndex < 0 || $playerIndex > 4) {
            return response()->json(['message' => 'player_index must be an integer 0..4'], 422);
        }

        if (! is_string($cardId) || $cardId === '') {
            return response()->json(['message' => 'card_id must be a non-empty string'], 422);
        }

        try {
            $this->service->playCard($game, $playerIndex, $cardId);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true]);
    }

    public function participation(Request $request, Game $game): JsonResponse
    {
        $validated = $request->validate([
            'player_index' => ['required', 'integer', 'min:0', 'max:3'],
            'play' => ['required', 'boolean'],
        ]);

        $this->service->decideParticipation($game, $validated['player_index'], $validated['play']);

        return response()->json(['success' => true]);
    }

    public function declareJacks(Request $request, Game $game): JsonResponse
    {
        $validated = $request->validate([
            'player_index' => ['required', 'integer', 'min:0', 'max:3'],
        ]);

        $playerIndex = $validated['player_index'];
        $this->service->declareJacks($game, $playerIndex);

        return response()->json(['success' => true]);
    }

    public function declarePartiya(Request $request, Game $game): JsonResponse
    {
        // TODO: Implement partiya declaration logic
        return response()->json(['message' => 'Partiya declaration not yet implemented'], 501);
    }
}
