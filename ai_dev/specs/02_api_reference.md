# 02 — Rams API Reference (Verified)

> Status: verified against `api/routes/api.php`, `GameController`, `AiController`,
> `GameService::getState()`, `web/src/api/client.js`, `web/src/websocket.js`,
> `websocket-server.js` at commit `e3c491c`.
> Contract errors/debt are listed in [`03_gap_analysis.md`](03_gap_analysis.md).

## 0. Conventions

- Base URL: dev `http://localhost:8000` (Vite proxies `/api/*` in dev); prod served
  same-origin (Nginx 8002).
- Content-Type: `application/json` on both directions.
- **Session header**: every request may carry `X-Player-Session-ID` (UUID generated
  in `client.js` and persisted in `localStorage`). It is required by
  `GET /api/games/resume`; used to mark the human player when creating a game.
- Errors: rule violations return `422` with `{ "message": "<human readable>" }`.
  Unexpected AI errors return `500 { "error": ... }`.
- **Commands never return new state** — the client receives state exclusively via
  WebSocket `game.update` events (event-driven architecture,
  `stage_5_8.md`). `POST /api/games` and `GET /api/games/{id}` are the only
  endpoints returning a full state payload.
- Card ids are strings `"{SUIT}-{RANK}"`: `H-14` = Ace of Hearts,
  `S-11` = Jack of Spades. Suits `C`lubs `D`iamonds `H`earts `S`pades; ranks
  `6..14` (6,7,8,9,10,J=11,Q=12,K=13,A=14).

## 1. Endpoints

### 1.1 `POST /api/games` — create a new game

Request body (optional):
```json
{ "seed": 123 }
```
- `seed` must be an integer; omitted → random. Enables deterministic shuffles
  (reproduces the same deal).

Response: `201` — full state (schema below) with `game.phase = "exchange"`,
4 players (seat 0 human, seats 1–3 AI), all piles `20`, round 1, dealer 0.

### 1.2 `GET /api/games/resume` — find the human's active game

- Requires `X-Player-Session-ID`.
- `200` — full state of the latest `in_progress` game whose human player has this
  session id.
- `204 No Content` — no active game (frontend treats this as "nothing to resume",
  no error).
- `400 { "message": "Session ID required" }` — header missing.

### 1.3 `GET /api/games/{game}` — full state

- `200` — full state (schema below). Route-model-bound `{game}` is the numeric id.

### 1.4 `POST /api/games/{game}/exchange` — exchange cards

Request:
```json
{ "player_index": 0, "discard_card_ids": ["S-6", "H-7"] }
```
- `player_index`: integer 0..3 (controller validates 0..4 — see G-6).
- Must be the current player's turn during `exchange`; discards must be in hand and
  ≤ remaining deck count.
- `200 { "success": true }`, or `422 { "message": ... }`.
- Broadcasts `exchange.completed` (dealer's exchange advances phase to
  `choose_to_play`).

### 1.5 `POST /api/games/{game}/participation` — play or pass the round

Request:
```json
{ "player_index": 0, "play": true }
```
- `player_index`: integer 0..3, `play`: boolean. Validated via FormRequest rules.
- Must be the current player's turn during `choose_to_play`.
- `200 { "success": true }` or `422`.

### 1.6 `POST /api/games/{game}/move` — play a card

Request:
```json
{ "player_index": 0, "card_id": "H-14" }
```
- `player_index`: integer 0..3 (controller validates 0..4 — see G-6).
- Must be the current player's turn during `play`; card must be in hand; follow-suit
  enforced; playing out of hand/turn/suit → `422`.
- `200 { "success": true }` or `422`.
- Broadcasts `card.played` after each play; round end is handled internally (`play`
  phase persists across tricks; the store holds the final trick on screen 1.5 s).

### 1.7 `POST /api/games/{game}/declare-jacks` — legacy Jacks declaration

Request: `{ "player_index": 0 }`
- Current behavior: sets the player's pile to **5** and increments
  `maltzy_count` during `play` phase — **without validating that the player holds
  two Jacks of the same color** (see G-3). This endpoint is a legacy remnant; the
  canonical Jacks mechanic is round-end deduction (D-8).
- `200 { "success": true }` or `422`.

### 1.8 `POST /api/games/{game}/declare-partiya` — partiya declaration

- **Not implemented** — returns `501 { "message": "Partiya declaration not yet
  implemented" }` (G-1).

### 1.9 `POST /api/games/{game}/ai-play` — trigger a single AI step

- No body. `AiController::playTurn`:
  - `400 { "error": "It is the human player's turn" }` if seat 0 is active.
  - Otherwise executes **one** AI action for the active seat (exchange /
    participation / play) and `200 { "success": true }`.
  - Internal errors → `500 { "error": msg }`.
- The frontend calls this after a 1.5 s "thinking" delay whenever the new active
  player is an AI (`game.js::scheduleAiMove`), keyed per
  `game:phase:player:round` to avoid duplicate requests (stage 7.11).

## 2. State schema (`getState`)

```json
{
  "game": {
    "id": 1,
    "status": "in_progress | finished",
    "phase": "exchange | choose_to_play | play",
    "dealer_index": 0,
    "current_player_index": 2,
    "round_number": 1,
    "winner_player_index": null,
    "trump_card_id": "H-12"
  },
  "players": [
    {
      "seat_index": 0,
      "type": "human | ai",
      "score": 0,
      "pile": 20,
      "maltzy_count": 0
    }
  ],
  "round": {
    "number": 1,
    "remaining_deck_count": 15,
    "dealer_index": 0,
    "seed": 123456,
    "hands": { "0": ["H-10", "S-7", "H-7", "S-13", "D-11"], "1": ["..."], "2": ["..."], "3": ["..."] },
    "exchanged": [0, 0, 0, 0],
    "taken": [0, 0, 0, 0],
    "trick_number": 1,
    "current_trick": [ { "player": 0, "card": "H-14" } ],
    "five_same_suit_declared": null,
    "partiya_declared_by": null,
    "passed_players": [2],
    "boys_state": { "0": { "red": { "played": 1 } } }
  },
  "exchangeStatus": "P1 changed 2 cards → P2 exchanging..." | null
}
```

Notes:

- `round.hands` exposes **all four hands** to every client (G-7).
- `exchangeStatus` is a backend-built prose string only present during `exchange`;
  it uses `P{n}` placeholders that the frontend localizes via regex
  (`PhasePanel.vue::formattedExchangeStatus`). Building the message by index
  comparison mis-orders it when `dealer_index ≠ 0` (G-5).
- `boys_state` is populated at the `choose_to_play → play` transition
  (`detectBoys`), tracking per-color Jack pairs and how many of the pair have been
  played.
- `partiya_declared_by` is always `null` today (G-1).

## 3. WebSocket protocol (relay on port 8080)

### Client → server

- Connect `ws://host:8080` (dev) / `wss://host/ws` (prod, behind Nginx).
- Subscribe:
  ```json
  { "event": "pusher:subscribe", "data": { "channel": "game.1" } }
  ```
- Channels are **public** — anyone who knows the game id can subscribe (G-14).

### Server → client

- `{ "event": "pusher_internal:subscription_succeeded", "channel": "game.1", "data": {} }`
- `{ "event": "game.update", "channel": "game.1", "data": <full state> }`
  — the **only** state-carrying event the client consumes. All backend event names
  (`game.created`, `exchange.started`, `exchange.completed`,
  `participation.decided`, `card.played`, `boys.announcement`) are collapsed into
  `game.update` by the relay; extra data (e.g. `message`, `playerIndex` from
  `boys.announcement`) are merged into the state payload.
- After a successful subscribe, the relay fetches
  `GET {API_URL}/api/games/{id}` (API_URL env, default `http://localhost:8000`) and
  broadcasts it back — this is the disconnect/reconnect re-sync path.

### Backend → relay

- `POST http://127.0.0.1:8080/broadcast` (configurable via `WEBSOCKET_URL`, e.g.
  container-compose value):
  ```json
  { "gameId": 1, "event": "card.played", "data": { ...full state... } }
  ```
- Replies `200 { "success": true }` or `400 { "error": "Invalid JSON" }`.
- The broadcast is fire from `GameService::broadcastGameUpdate()` with a 1 s curl
  timeout; failures are logged, not retried, and do not fail the command (G-14).

## 4. Frontend store surface (contract consumers)

| Store member | Reads | Writes |
|---|---|---|
| `game.state` | everything | only via `handleStateUpdate` (WS) or initial fetch |
| `newGame(seed?)` | — | `POST /api/games` |
| `checkResume()` / `confirmResume()` | — | `GET /api/games/resume` |
| `refresh()` | — | `GET /api/games/{id}` |
| `submitExchange()` | `discardCardIds` | `POST /exchange` |
| `submitParticipation(play)` | — | `POST /participation` |
| `declareJacks()` | — | `POST /declare-jacks` |
| `playCard(cardId)` | — | `POST /move` |
| `scheduleAiMove()` | current state | `POST /ai-play` (delayed) |

Keep this mapping stable when touching either side (presentation-layer constraint
from `rams_ui_redesign_instructions_for_gemini_cli.md`).

## 5. Testing the API

```bash
cd api && php artisan serve            # API on :8000
# separate terminal, from repo root:
./start-websocket.sh                   # relay on :8080 (needed for broadcast)

curl -s -X POST localhost:8000/api/games -H 'Content-Type: application/json' \
     -H 'X-Player-Session-ID: demo-1' -d '{"seed":42}'
```
Automated coverage: `api/tests/Feature/RamsGameApiTest.php` (create/state,
exchange+participation flow, follow-suit enforcement).