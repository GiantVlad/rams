# Stage 5 – Full Game Loop (Result)

## Goals (from `ai_dev/dev_stages.md`)

- Allow full games from start to finish
- Ensure state consistency

## What was implemented

### 1) Decision: keep round-end/game-end logic in the backend

- The Laravel backend already handles:
  - Round transitions (after 9 tricks)
  - Scoring and score accumulation
  - Game end detection (`status = 'finished'` when any score >= 100)
  - Winner selection (`winner_player_index`)

Therefore, the frontend only needs to detect and display these transitions.

### 2) Frontend store updates (Pinia)

Updated:

- `web/src/stores/game.js`

Additions:

- New state flag: `justFinishedRound` (stores the round number that just finished)
- New getters:
  - `gameStatus`
  - `winnerPlayerIndex`
  - `roundNumber`
- In `playCard()`:
  - Detect when `round.number` increases → set `justFinishedRound` to the previous round number so the UI can show a transient “Round X finished” message.
- In `newGame()`, `refresh()`, `submitBid()`, `playCard()`:
  - Clear `justFinishedRound` on any user action to avoid stale messages.
- New action: `dismissRoundResult()` to clear the transient message.

### 3) UI updates

Updated:

- `web/src/App.vue`

Additions:

- **Round-finished notification** (blue banner):
  - Shows when `justFinishedRound !== null`
  - Clickable to dismiss
- **Game-over screen** (green banner):
  - Shows when `gameStatus === 'finished'`
  - Displays winner (e.g., “Winner: P2”)
  - Includes a “Start New Game” button
- **Status line update**:
  - When game is finished, shows winner in the header line
- **New styles**:
  - `.info`, `.infoTitle`, `.infoBody`
  - `.finished`, `.finishedTitle`, `.finishedBody`

### 4) Restart behavior

- The existing “New Game” button works at any time (including after a game ends).
- After a game ends, the UI shows a dedicated “Start New Game” button for clarity.

## How to run (development)

Same as Stage 4:

### Backend

In one terminal:

- `php artisan serve` (in `api/`)

### Frontend

In another terminal:

- `npm run dev` (in `web/`)

Open:

- `http://localhost:5173`

## Verification

- Play through several rounds; you should see:
  - Round-finished notifications after each round ends
  - Scores update after each round
  - Game-over screen when any player reaches 100 points
  - Ability to restart at any time

## Deliverables status

- **End-to-end playable game**: done (full loop from start to finish)
- **Stable game loop**: done (round transitions, game end detection, restart)

## Notes

- No backend refactor was required; the existing API already provided the needed data.
- All rule logic remains backend-authoritative per the original design.
