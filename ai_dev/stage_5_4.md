# Stage 5.4 – Reduce to 4 Players

## Context

The game has been reduced from 5 to 4 players (1 human + 3 AI) to match the updated game rules. This required changes across backend models, domain logic, service layer, API validation, and frontend display.

## What Was Done

### 1) Backend Domain Logic

**Dealing (`app/Domain/Rams/Dealing.php`)**
- Changed default parameters: `players = 4`, `cardsPerPlayer = 5`
- Now deals 20 cards total (4 players × 5 cards)

**TrickRules (`app/Domain/Rams/TrickRules.php`)**
- Updated `assertTurnOrder()` default to 4 players
- Updated `winnerPlayerIndex()` to expect exactly 4 plays
- Error messages updated to reflect 4 plays

**Scoring (`app/Domain/Rams/Scoring.php`)**
- Updated `applyRoundScoring()` default to 4 players
- Changed validation to expect 4 trick counts
- Updated trick range validation: 0-4 tricks
- Updated `mustDeclarePartiya()` default to 4 tricks per round

### 2) GameService (`app/Services/Rams/GameService.php`)

**Player Creation**
- Reduced player loop from 5 to 4 iterations
- Updated modulo operations from `% 5` to `% 4` for turn order
- Current player initialization: `($dealerIndex + 1) % 4`

**Arrays Initialization**
- All player arrays reduced to 4 elements:
  - `$exchanged = [0, 0, 0, 0]`
  - `$taken = [0, 0, 0, 0]`
- In `createRound()`: both arrays initialized with 4 elements

**Trick Completion**
- Changed from 5 to 4 cards per trick
- `if (count($currentTrick) === 4)`
- Round ends after 4 tricks total

**Turn Progression**
- All player index calculations use `% 4`
- Exchange phase: `($playerIndex + 1) % 4`
- Play phase: `($playerIndex + 1) % 4`
- Dealer rotation: `($game->dealer_index + 1) % 4`

**Scoring Call**
- Updated to use 4 players: `Scoring::applyRoundScoring($taken, $maltzyCounts, $piles, 4)`

### 3) API Validation (`app/Http/Controllers/GameController.php`)

**Player Index Validation**
- Updated all `player_index` validation from `max:4` to `max:3`
- Applies to: `exchange()`, `move()`, `declareJacks()`

### 4) Frontend UI (`web/src/App.vue`)

**Round Points Display**
- Added total tricks counter when 4 players present
- Shows sum of all tricks taken in the round

## Game Flow Changes

### Before (5 players)
- 5 cards dealt to each player (20 total)
- 5 cards per trick
- 5 tricks per round
- Turn order: 0→1→2→3→4→0...

### After (4 players)
- 5 cards dealt to each player (20 total)
- 4 cards per trick
- 4 tricks per round
- Turn order: 0→1→2→3→0...

## Verification Points

- ✅ Game creates exactly 4 players (1 human, 3 AI)
- ✅ Each player receives 5 cards
- ✅ Tricks complete after 4 cards played
- ✅ Rounds end after 4 tricks (total deductions = 4 points)
- ✅ Turn order wraps correctly after player 3
- ✅ Dealer rotates through 4 players
- ✅ Frontend displays 4 players with pile/maltzy info
- ✅ Round points show total of 4 tricks

## Files Changed

- `api/app/Domain/Rams/Dealing.php`
- `api/app/Domain/Rams/TrickRules.php`
- `api/app/Domain/Rams/Scoring.php`
- `api/app/Services/Rams/GameService.php`
- `api/app/Http/Controllers/GameController.php`
- `web/src/App.vue`

## Notes

- The trump card mechanism remains unchanged
- Exchange phase logic works the same for 4 players
- Jacks (Maltzy) declarations unchanged
- Partiya declarations unchanged (still stubbed)
- Deck still has 36 cards with 16 remaining after deal and trump
