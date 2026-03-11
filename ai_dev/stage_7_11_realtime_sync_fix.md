# Stage 7.11: Realtime Sync and Resume Noise Fixes

## Goal
Stabilize frontend-driven AI turn progression and remove expected browser console noise during session resume checks.

## Changes

### 1. Frontend AI scheduling (`web/`)
- Reworked `src/stores/game.js` so AI turns are scheduled from a single code path.
- Added a per-turn scheduling key based on game, phase, player, and round.
- Cancelled stale delayed AI requests when the server state advances before the timeout fires.
- Removed duplicate `game.update` side effects that were causing repeated `POST /api/games/{id}/ai-play` calls.

### 2. WebSocket listener lifecycle
- Updated `src/websocket.js` to clear old listeners and close the previous socket before reconnecting.
- This prevents multiple `game.update` handlers from accumulating across new games and resumed sessions.

### 3. Resume endpoint behavior (`api/`)
- Changed `GET /api/games/resume` to return `204 No Content` when no active game exists.
- Updated the frontend resume check to treat an empty response as "nothing to resume" without logging an error.

## Outcome
- AI turns no longer spam duplicate requests for the same state transition.
- Browser console stays clean when the user has no resumable game.
- Realtime updates remain active for exchange, participation, and play phases.
