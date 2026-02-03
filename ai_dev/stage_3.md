# Stage 3 – Basic AI Players (Result)

## Goals (from `ai_dev/dev_stages.md`)

- Allow game to progress without human input
- Ensure rule-compliant AI behavior

## What was implemented

### 1) AI service

Created:

- `api/app/Services/Rams/AiService.php`

Capabilities:

- **Bidding**: `chooseBid(currentBids, playerIndex)`
  - Deterministic minimal strategy: choose the smallest legal bid (0..9)
  - Avoids the invalid case where the final sum of all bids equals **9**
- **Move selection**: `chooseCardIdToPlay(handCardIds, currentTrick)`
  - Follows suit if possible
  - Otherwise plays the lowest-rank card

### 2) AI autoplay integration

Updated:

- `api/app/Services/Rams/GameService.php`

Changes:

- Added `progressUntilHumanTurn(Game $game)`
  - Executes AI turns in a loop until:
    - it becomes the human player’s turn, or
    - the game finishes
  - Works in both phases:
    - `bidding` (AI bids)
    - `playing` (AI plays)
  - Includes a guard (`<= 250` steps) to prevent infinite loops

- Refactored `placeBid()` and `playCard()` to call internal “single-step” methods:
  - `placeBidOnce()`
  - `playCardOnce()`

This avoids recursion while still ensuring that every human action triggers AI progress.

Behavioral result:

- After `POST /api/games`, AI immediately places bids for seats 1..3, leaving the human to bid.
- After the human bids, AI plays automatically until it is the human’s move.

## Tests

Updated:

- `api/tests/Feature/RamsGameApiTest.php`

Verifies:

- Creating a game auto-bids AI seats and returns state with `current_player_index = 0`
- Turn enforcement is still correct
- Follow-suit enforcement for the human move (after AI has started the trick)

Test run:

- `php artisan test` passes.

## Deliverables status

- **AI service**: done (`AiService`)
- **AI test scenarios**: done (updated feature tests)
