# 00 — Rams Project Overview (Verified Snapshot)

> Status: verified against the repository at commit `e3c491c` (master).
> This document supersedes conflicting statements in older stage documents. Where it
> conflicts, prefer this document; link back to the older document for history.

## 1. What this project is

A web implementation of the **Rams** card game (4-player variant: 1 human + 3 AI),
with real-time updates via a lightweight custom WebSocket relay. See
[`ai_dev/README.md`](../README.md) for the original Stage-1 goals and
[`ai_dev/game_rules.md`](../game_rules.md) for the rule reference this project grew from.

## 2. Repository layout

Monorepo with three runtime areas:

```
rams/
├── api/                        # Laravel 12 backend (PHP ^8.4)
│   ├── app/Domain/Rams/        # Pure game logic (no framework dependencies)
│   ├── app/Services/Rams/      # GameService (orchestration, persistence, broadcast), AiService
│   ├── app/Http/Controllers/   # GameController, AiController
│   ├── app/Models/             # Game, Player, GameRound
│   ├── app/Events/GameUpdate.php  # Event class (reserved for future Reverb)
│   ├── config/, database/migrations/, routes/api.php
│   └── tests/                  # Unit + Feature (PHPUnit)
├── web/                        # Vue 3 + Vite + Pinia SPA
│   ├── src/main.js, src/App.vue
│   ├── src/components/         # GameTable, Card, PlayerSeat, TrickArea, ScoreBoard, PhasePanel, RulesPopup
│   ├── src/stores/game.js      # Pinia store (single frontend state owner)
│   ├── src/api/client.js       # fetch wrapper + session ID
│   └── src/websocket.js        # WebSocket client (event-driven updates)
├── websocket-server.js         # Node.js WebSocket relay (port 8080)
├── start-websocket.sh          # Launcher for the relay
├── docker/, compose.yml, config_nginx.sh, setup_ssl.sh, remote_deploy.sh  # Deployment
├── tests/e2e/                  # Playwright E2E smoke tests
├── ai_dev/                     # Development documentation & stage history
└── AGENTS.md, GEMINI.md, README.md
```

## 3. Runtime architecture & data flow

```
+---------------------------------------------------------------+
| Browser (Vue 3 + Pinia)                                       |
|   - renders state from store only                              |
|   - commands via REST (apiFetch)                              |
|   - state updates ONLY via WS 'game.update' events             |
+----+--------------------------------+-------------------------+
     | REST command       ^ WS state | WS subscribe (pusher:subscribe)
     v                   |           v
+----+-------------------+---+   +------------------------------+
| Laravel API (port 8000)    |   | Node WS relay (port 8080)     |
|  GameService: rule auth,   |   |  HTTP POST /broadcast <-+     |
|  persistence, AI single-   |   |  forwards to subscribed   |   |
|  step actions, cURL        +--->  clients as               |   |
|  broadcast                 |   |  {event:'game.update'}    |   |
+----------------------------+   +--------------------------+---+
```

1. **Command**: the client (human action, or the frontend-driven AI loop calling
   `POST /api/games/{id}/ai-play`) sends one REST command.
2. **Ack**: controllers return `{ "success": true }` (or 422 `{ "message": ... }`
   on rule violation) — **never** the new state.
3. **Process**: `GameService` performs exactly one step inside a DB transaction and
   persists it (single source of truth: the database).
4. **Broadcast**: `GameService::broadcastGameUpdate()` cURLs the relay
   (`WEBSOCKET_URL`, default `http://127.0.0.1:8080`), which pushes the full state to
   clients subscribed to channel `game.{id}`.
5. **Update**: the Pinia store's `handleStateUpdate()` applies the state and
   schedules the next AI step if the active player is an AI (1.5 s "thinking" delay on
   the client; see 04_roadmap P0 for making this a server concern).

Design rationale is documented in `ai_dev/stage_5_7.md` (non-blocking loop),
`ai_dev/stage_5_8.md` (event-driven, WS-only state updates), and
`ai_dev/stage_7_11_realtime_sync_fix.md` (single AI scheduling path + listener lifecycle).

### Resilience mechanisms (current)

- WS reconnect with exponential-ish backoff (max 5 attempts, 3 s interval) +
  automatic re-sync: on subscribe, the relay fetches current state from the API and
  broadcasts it back to the newly connected client.
- Polling fallback every 5 s in the store while a game is open (compares JSON to
  avoid redundant state churn).
- Session-based resume: `X-Player-Session-ID` header + `localStorage`
  `player_session_id`; `GET /api/games/resume` returns the latest in-progress game or
  204.

## 4. Backend components

| Component | File | Responsibility |
|---|---|---|
| `Card`, `Suit`, `Rank` | `app/Domain/Rams/` | Immutable card value objects; Suit = C/D/H/S, Rank = 6..14 (6..A) |
| `Deck` | `app/Domain/Rams/Deck.php` | Standard 36-card deck; seeded shuffle (Mt19937), draw/remove |
| `CardCodec` | `app/Domain/Rams/CardCodec.php` | Card ⇄ `"H-12"` string id, suit decoding |
| `Dealing` | `app/Domain/Rams/Dealing.php` | Distributes cards round-robin; 4 players × 5 cards |
| `TrickRules` | `app/Domain/Rams/TrickRules.php` | Legal-play validation (follow suit), turn order, trick winner (trump-aware) |
| `Exchange` | `app/Domain/Rams/Exchange.php` | Exchange validation; five-same-suit detection & priority |
| `Scoring` | `app/Domain/Rams/Scoring.php` | Round pile math, zero-trick penalty, maltzy deductions, partiya predicate, game-end/winner |
| ~~`BiddingRules`~~ | `app/Domain/Rams/BiddingRules.php` | **Dead code** from the pre-5.1 bidding variant; not referenced by the service (see 03_gap_analysis G-13) |
| `GameService` | `app/Services/Rams/GameService.php` | All game orchestration: create/resume, exchange, participation, play, AI step, scoring, broadcast |
| `AiService` | `app/Services/Rams/AiService.php` | Exchange discard choice, to-play decision, trick card selection (win-cheaply strategy) |
| `GameController` | `app/Http/Controllers/GameController.php` | REST endpoints; 422 on `RuntimeException` |
| `AiController` | `app/Http/Controllers/AiController.php` | `POST /ai-play` single AI step |
| `Game`, `Player`, `GameRound` | `app/Models/` | Eloquent models; `GameRound` maps to table `rounds` |

### Persistence schema (verified)

**games**: `status` ('in_progress'|'finished'), `phase`
('exchange'|'choose_to_play'|'play'), `dealer_index`, `current_player_index`,
`round_number`, `winner_player_index` (nullable), `trump_card_id` (nullable, e.g.
`"H-12"`).

**players**: `game_id`, `seat_index` (0 = human, 1-3 = AI), `type`,
`session_id` (nullable, human only), `score` (unused legacy), `pile` (default 20,
current pile), `maltzy_count` (default 0, incremented per Jacks pair declared this
round).

**rounds** (game_id, unique `[game_id, number]`): `number`, `dealer_index`,
`seed` (shuffle seed for reproducibility), `hands` (json: per-seat card-id arrays),
`exchanged` (json: per-seat count of exchanged cards), `taken` (json: per-seat tricks
won this round), `trick_number`, `current_trick` (json: `[{player, card}]`),
`five_same_suit_declared` (json, nullable), `partiya_declared_by` (json, nullable —
unused), `remaining_deck` (json: card ids available for exchange),
`passed_players` (json: seats that folded), `boys_state` (json: per-seat
`{red|black: {played: N}}` for same-color Jack pairs).

### Domain invariants (verified in code)

- 36-card deck, ranks 6 < 7 < 8 < 9 < 10 < J < Q < K < A (values 6..14).
- Round: deal 5 cards × 4 players = 20; trump = next card; 15 cards remain for
  exchange.
- Exchange turn order: starts left of dealer, dealer last; 0..5 discards replaced
  from `remaining_deck` (capped by deck size); after the dealer, phase →
  `choose_to_play`.
- Participation: each seat left-of-dealer-first decides Play/Pass; if ≤ 1 active
  player, round resolves without trick play (sole active player gets −5); otherwise
  phase → `play`.
- Play: 4 active players → 4 plays per trick, **5 tricks per round** (see
  `01_game_rules_canonical.md` §Decision D-1; `stage_5_4.md`'s "4 tricks" claim is
  inaccurate — the code and `game_rules.md` use 5).
- Trick winner: highest card of led suit, or highest trump; winner leads next trick.
- Scoring: `pile − tricks_won`; `+5` if played but won 0 tricks (passed players
  exempt); `−5 × maltzy_count` (Jacks pairs); clamp at ≥ 0.
- Game end: any pile ≤ 0; winner = lowest pile, tie-break = reached it last
  (documented intent; the served tie-break in `Scoring::winnerPlayerIndex` requires
  round-reaching data that the service does not currently persist — see
  03_gap_analysis G-11).

## 5. Frontend components

| Component | File | Responsibility |
|---|---|---|
| `App.vue` | `src/App.vue` | Shell: header/status, 3-column layout, resume prompt, round-finished overlay |
| `GameTable` | `src/components/GameTable.vue` | Felt table, seats (P0 bottom, P1 left, P2 top, P3 right), trump zone, hand |
| `Card` | `src/components/Card.vue` | Suit/rank rendering, selected/disabled/face-down states |
| `PlayerSeat` | `src/components/PlayerSeat.vue` | Avatar, active/thinking/pass/dealer badges |
| `TrickArea` | `src/components/TrickArea.vue` | 4 card slots around the center |
| `ScoreBoard` | `src/components/ScoreBoard.vue` | Pile, Jacks count, tricks this round |
| `PhasePanel` | `src/components/PhasePanel.vue` | Context actions: exchange / participate / play (Jacks btn) / game over / refresh |
| `RulesPopup` | `src/components/RulesPopup.vue` | "How to play" modal |
| `game.js` | `src/stores/game.js` | State, getters, actions; WS + AI scheduling; resume logic |
| `client.js` | `src/api/client.js` | fetch wrapper, JSON errors, session header |
| `websocket.js` | `src/websocket.js` | WS lifecycle, subscribe, reconnect, `game.update` emit |

Naming: seat 0 = "You", 1 = "Mike", 2 = "William", 3 = "Sarah" (UI only; backend
always uses `seat_index`).

## 6. Development & deployment commands

From `AGENTS.md`:

- `cd api && composer setup` — install backend deps, `.env`, key, migrate, npm
  install, build.
- `cd api && composer dev` — Laravel serve + queue worker + logs + Vite dev.
- `cd api && composer test` — `php artisan config:clear` then full PHPUnit suite
  (SQLite `:memory:`, per `api/phpunit.xml`).
- `cd web && npm run dev` / `npm run build` — standalone frontend.
- root `npm run dev` — WS relay under `nodemon`; `npm run qa:e2e` — Playwright
  (headed, needs full stack running).
- Deployment artifacts: `compose.yml` + `docker/`, `config_nginx.sh`,
  `setup_ssl.sh`, `remote_deploy.sh`, `check-prod.js`, CI in `.github/workflows/quality.yml`
  (pint, composer audit, tests on PHP 8.4 / Node 24).

## 7. Documentation map (existing)

| Document | Covers |
|---|---|
| `ai_dev/README.md` | Stage-1 foundational spec (partially superseded) |
| `ai_dev/game_rules.md` | Rule set the current variant implements (with gaps: partiya, dealer-trump pickup) |
| `ai_dev/rams_game_design_plan.md` | UI/UX design plan (screens, visual language, layout) |
| `ai_dev/dev_stages.md` | Original staged roadmap 0–7 + optional futures |
| `ai_dev/stage_0.md` | Analysis of the **obsolete** 9-card bidding variant (historical) |
| `ai_dev/stage_2.md` … `stage_7_11_*.md` | Per-stage implementation history & decisions |
| `ai_dev/rams_ui_redesign_instructions_for_gemini_cli.md` | Presentation-layer redesign brief (implemented in stage 7.1) |
| `GEMINI.md`, `AGENTS.md`, root `README.md` | High-level project & contribution guides |

Sibling spec documents: `01_game_rules_canonical.md` (rules), `02_api_reference.md`
(contracts), `03_gap_analysis.md` (known gaps), `04_roadmap.md` (future work),
`05_glossary.md` (terminology).