# Plan — Partiya end-to-end (G-1)

## Backend

### 1. Add `declarePartiya` to `GameService` (`api/app/Services/Rams/GameService.php`)

Add method:
```php
public function declarePartiya(Game $game, int $playerIndex): Game
{
    return DB::transaction(function () use ($game, $playerIndex) {
        $game->refresh();
        if ($game->status !== 'in_progress') throw new RuntimeException('Game is not in progress.');
        if ($game->phase !== 'play') throw new RuntimeException('Partiya can only be declared during play phase.');
        $player = $game->players()->where('seat_index', $playerIndex)->first();
        if (! $player) throw new RuntimeException('Player not found.');
        $round = $game->currentRound();
        if (! $round) throw new RuntimeException('No active round.');

        // Already declared this round
        if (($round->partiya_declared_by ?? [])[$playerIndex] ?? false) {
            throw new RuntimeException('Partiya already declared by this player this round.');
        }

        $tricksSoFar = ($round->taken[$playerIndex] ?? 0);
        if (! Scoring::mustDeclarePartiya($player->pile, $tricksSoFar)) {
            throw new RuntimeException('You do not need to declare Partiya now.');
        }

        // Record declaration on round
        $declared = $round->partiya_declared_by ?? [];
        $declared[$playerIndex] = true;
        $round->partiya_declared_by = $declared;
        $round->save();

        return $game->fresh(['players', 'rounds']);
    });
}
```

### 2. Wire partiya exemption into scoring (`api/app/Services/Rams/GameService.php`, `finishRound`)

Change `finishRound` scoring call:
```php
// Before:
$newPiles = Scoring::applyRoundScoring($taken, $maltzyCounts, $piles, $passedPlayers, 4);

// After:
$partiyaDeclaredBy = $round->partiya_declared_by ?? [];
$newPiles = Scoring::applyRoundScoring($taken, $maltzyCounts, $piles, $passedPlayers, 4, $partiyaDeclaredBy);
```

### 3. Update `Scoring::applyRoundScoring` signature (`api/app/Domain/Rams/Scoring.php`)

```php
public static function applyRoundScoring(
    array $tricksWonByPlayer,
    array $maltzyCountByPlayer,
    array $currentPileByPlayer,
    array $passedPlayers = [],
    int $players = 4,
    array $partiyaDeclaredBy = []
): array {
    // ...
    if ($tricks === 0 && ! in_array($i, $passedPlayers) && empty($partiyaDeclaredBy[$i])) {
        $newPile += 5;
    }
```

### 4. Add `POST /api/games/{id}/declare-partiya` to `GameController` (`api/app/Http/Controllers/GameController.php`)

```php
public function declarePartiya(Request $request, Game $game): JsonResponse
{
    $validated = $request->validate([
        'player_index' => ['required', 'integer', 'min:0', 'max:3'],
    ]);
    try {
        $this->service->declarePartiya($game, $validated['player_index']);
    } catch (RuntimeException $e) {
        return response()->json(['message' => $e->getMessage()], 422);
    }
    return response()->json(['success' => true]);
}
```

### 5. Add `declarePartiya` to `getState` broadcast

The `partiya_declared_by` field is already present in `getState` (line 146 of GameService.php). Nothing more needed here.

### 6. AI declaration (`api/app/Services/Rams/AiService.php`)

Add:
```php
public function shouldDeclarePartiya(array $handCardIds, int $pile, int $tricksSoFar): bool
{
    return Scoring::mustDeclarePartiya($pile, $tricksSoFar);
}
```
Wire into `makeAiMove`'s `play` phase — at the top of the `elseif ($game->phase === 'play')` branch, check if `shouldDeclarePartiya` and call `$this->declarePartiya($game, $currentIndex)` before choosing a card to play. Guard: only declare once per round — the service method already throws if declared.

## Tests

Add to `api/tests/Unit/Rams/ScoringTest.php`:
- `test_partiya_declared_player_exempt_from_zero_trick_penalty` — player with `$tricks[0]=0` AND `partiyaDeclaredBy=[0=>true]` gets no +5.

Add to `api/tests/Feature/RamsGameApiTest.php`:
- `test_declare_partiya_flow` — create game, complete exchange + participation, then `POST /declare-partiya` with a valid player returns 200; subsequent call returns 422 (already declared).
- `test_declare_partiya_rejected_when_not_eligible` — a player whose pile > 5 gets 422.

## Verification

- `cd api && composer test` — green, same assertions + new partiya tests.
- `./vendor/bin/pint --test` — PASS.
