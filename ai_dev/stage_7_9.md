# Stage 7.9: Implementation of "Boys" Rule (Jacks Pairs)

## Goal
Implement the "Boys" rule where a player holding two Jacks of the same color (Red or Black) receives a bonus/penalty reduction of 5 points at the end of the round. The game must also announce when each Jack from the pair is played.

## Changes

### 1. Database & Model
- Created migration `add_boys_state_to_game_rounds_table` to add a `boys_state` JSON column to the `rounds` table.
- Updated `App\Models\GameRound` model to include `boys_state` in `$fillable` and `$casts`.

### 2. Game Logic (`App\Services\Rams\GameService`)
- **Detection**: Added `detectBoys` method called at the transition from `choose_to_play` to `play` phase. It scans active players' hands for Jack pairs (Hearts+Diamonds or Spades+Clubs).
- **Maltzy Integration**: "Boys" are treated as a type of "Maltzy". Each pair increments the player's `maltzy_count`.
- **Scoring**: Leveraged existing `Scoring::applyRoundScoring` which deducts `maltzy_count * 5` from the pile.
    - If a player wins 1 trick and has Boys: `pile - 1 (trick) - 5 (boys) = -6 points`.
    - If a player wins 0 tricks and has Boys: `pile + 5 (penalty) - 5 (boys) = 0 change`.
- **Announcements**: 
    - Updated `broadcastGameUpdate` to support `extraData`.
    - Updated `playCardOnce` to detect when a "Boy" Jack is played.
    - Triggers `boys.announcement` event with messages "first Jack came out" or "second Jack came out".

### 3. Verification
- Created `Tests\Unit\Rams\BoysRuleTest`.
- Verified detection of red/black pairs.
- Verified increment of `maltzy_count`.
- Verified state tracking of played Jacks (1st vs 2nd).

## Outcomes
- The backend now authoritatively handles "Boys" detection and scoring.
- Real-time announcements are sent to the WebSocket server for frontend display.

## AI Trick Completion Pause
- **Removed** `sleep(2)` from `GameService::playCardOnce` in the backend.
- The backend still broadcasts the "full trick" state (all 4 cards played) before clearing it.
- **Frontend Implementation (`web/src/stores/game.js`)**:
    - Added `isDisplayingTrickResult` flag and `pendingUpdates` queue.
    - When `handleStateUpdate` receives a state with a full trick, it:
        1. Displays the full trick.
        2. Sets `isDisplayingTrickResult = true` and starts a 1.5s timeout.
    - While `isDisplayingTrickResult` is true, subsequent updates (like the cleared trick state) are queued in `pendingUpdates`.
    - After the 1.5s timeout, the queue is processed, and the next state is applied, clearing the trick visually.
- This ensures the user sees the final card of the trick for 1.5 seconds before the table is cleared, providing a smooth visual experience without blocking backend threads.
