# Stage 5.1 – Refactor to 5-Player Rams Variant (Result)

## Context

The user updated `game_rules.md` from a 4-player classic Rams variant to a 5-player variant with new mechanics:
- Trump card
- Card exchange phase (no bidding)
- Pile-based scoring (start at 20, reduce by tricks won)
- Penalties (+5 for zero tricks)
- Maltzy (two Jacks of same color: –5 pile each)
- Partiya declaration (when ≤5 pile and exact tricks to reach 0)
- Game ends when any player’s pile reaches 0

## Scope of Refactor

- **Backend only**: Models, domain logic, API, AI, and GameService
- **Frontend**: Not updated in this stage (still assumes 4-player bidding)

## What Was Done

### 1) Database Models & Migrations

**Files updated**
- `api/database/migrations/2026_01_22_000100_create_games_table.php`
  - Added `trump_card_id` (string, nullable)
- `api/database/migrations/2026_01_22_000110_create_players_table.php`
  - Added `pile` (unsignedInteger, default 20)
  - Added `maltzy_count` (unsignedTinyInteger, default 0)
- `api/database/migrations/2026_01_22_000120_create_rounds_table.php`
  - Replaced `bids` with `exchanged` (json)
  - Added `five_same_suit_declared` (json, nullable)
  - Added `partiya_declared_by` (json, nullable)
- `api/app/Models/Game.php`
  - Added `trump_card_id` to fillable
- `api/app/Models/Player.php`
  - Added `pile`, `maltzy_count` to fillable and casts
- `api/app/Models/GameRound.php`
  - Updated fillable and casts for new fields

### 2) Domain Logic

**New file**
- `api/app/Domain/Rams/Exchange.php`
  - `validate()`: ensure discards are in hand and within limits
  - `hasFiveSameSuit()`: detect five cards of same suit
  - `priorityFiveSameSuit()`: determine which player should declare by priority

**Updated files**
- `api/app/Domain/Rams/Dealing.php`
  - Changed defaults to 5 players, 5 cards each
- `api/app/Domain/Rams/TrickRules.php`
  - `assertLegalPlay()`: added trump parameter and must-play-trump rule
  - `assertTurnOrder()`: updated to 5 players
  - `winnerPlayerIndex()`: added trump support, 5 plays per trick
- `api/app/Domain/Rams/Scoring.php` (completely rewritten)
  - `applyRoundScoring()`: pile reduction, zero-trick penalty, maltzy deductions
  - `mustDeclarePartiya()`: detect when partiya is required
  - `isGameEnd()`: check if any pile reached 0
  - `winnerPlayerIndex()`: lowest pile wins; tie-break by who reached it last

### 3) GameService

**File**: `api/app/Services/Rams/GameService.php` (fully rewritten)

Key changes:
- `createGame()`: creates 5 players with pile=20; phase starts at 'exchange'
- `createRound()`: deals 5 cards each; reveals trump card; checks five same suit
- `exchangeCards()`/`exchangeCardsOnce()`: handle card exchange phase; advance to play after dealer
- `playCard()`/`playCardOnce()`: use trump in validation; 5 tricks per round; detect round end
- `finishRound()`: apply new scoring; detect game end; advance dealer
- `progressUntilHumanTurn()`: AI autoplay for exchange and play phases
- `getState()`: includes trump_card_id, pile, maltzy_count, exchanged, five_same_suit_declared, partiya_declared_by

### 4) API Endpoints

**Files**
- `api/routes/api.php`
  - Replaced `POST /games/{game}/bid` with `POST /games/{game}/exchange`
  - Added `POST /games/{game}/declare-maltzy` (stub)
  - Added `POST /games/{game}/declare-partiya` (stub)
- `api/app/Http/Controllers/GameController.php`
  - Replaced `bid()` with `exchange()` (accepts player_index and discard_card_ids)
  - Updated player_index validation to 0..4
  - Added stub methods `declareMaltzy()` and `declarePartiya()` (501 responses)

### 5) AI Service

**File**: `api/app/Services/Rams/AiService.php` (refactored)

- Removed bidding logic
- Added `chooseDiscardCards()`: currently returns [] (no exchange)
- Updated `chooseCardIdToPlay()`:
  - Accepts optional `trumpCardId`
  - Respects trump and follow-suit rules
  - Prefers low non-trump cards; plays lowest trump if necessary
- Removed unused helpers (`normalizeBids`, `wouldViolateSumNine`)

## What Works Now

- Creating a game creates 5 players with pile=20
- Exchange phase: players can discard cards (AI currently discards none)
- Play phase: tricks respect trump and 5-player rules
- Round-end scoring: pile updates, penalties, maltzy deductions
- Game end detection when any pile reaches 0
- AI autoplay through exchange and play phases

## Known Limitations / TODOs

- **Card exchange**: only removes discarded cards; does not yet draw new ones from deck (simplified)
- **Maltzy/Partiya**: backend tracks fields but no logic to apply effects or declarations
- **Frontend**: still assumes 4-player bidding; needs UI updates for 5 players, trump, exchange, maltzy/partiya
- **Tests**: not updated for new rules
- **Documentation**: not yet updated

## How to Run (Backend)

```bash
cd api
php artisan migrate:fresh --seed
php artisan serve
```

Create a game via POST `/api/games` and use the new `/exchange` and `/move` endpoints.

## Next Steps

- Update frontend UI for 5 players, trump display, exchange controls, maltzy/partiya announcements
- Implement full card exchange logic (draw from deck)
- Implement maltzy/partiya declaration logic in GameService and API
- Update tests to cover new rules
- Update documentation (README, game_rules, API docs)
