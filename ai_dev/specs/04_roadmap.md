# 04 — Future Development Roadmap

> Purpose: phased plan for future Rams development. Every phase closes specific
> entries from [`03_gap_analysis.md`](03_gap_analysis.md) and links to existing
> stage documents for context. Roughly ordered: correctness first, then
> fairness/robustness, then multiplayer (the documented north star), then polish and
> ops.
>
> Each phase states: **Scope**, **Acceptance criteria**, **Tests**, **Related docs**.

---

## Phase 0 — Rule correctness (close 🔴 and rule-surface gaps)

Resolves: **G-1, G-2, G-3, G-4, G-5, G-6, G-8, G-9 (decisions), G-10 (decision),
G-12 (naming only), G-13, G-15, G-17, G-20 (declarations)**.

1. **Decide open rules first** (spec 01 §9): partiya trigger & penalty,
   all-trump flush +5, dealer trump pickup, declare-jacks fate.
2. **Partiya end-to-end** (G-1): implement declaration validation + penalty in
   `GameService::finishRound`, wire `rounds.partiya_declared_by`, announce via
   broadcast, add AI declaration to `AiService` (`chooseToDeclarePartiya`),
   surface in `PhasePanel`, extend RulesPopup (G-17).
3. **Jacks/Boys UI** (G-2, G-4): fix `humanHasJacks` to same-**color** pairs (or
   read `round.boys_state`); render declared pairs and "first/second Jack came out"
   announcements; keep round-end deduction canonical; retire or strictly validate
   `declare-jacks` (G-3).
4. **Backend hygiene**: fix `getExchangeStatus` ordering (G-5), `player_index`
   0..3 (G-6), direct `finishRound` after the 5th trick (G-8), enable Scoring bounds
   (G-15), remove dead `BiddingRules` + dedupe TrickRules tests (G-13).
5. **Five same suit / dealer trump pickup** per decisions (G-9, G-10) with AI
   support (G-20).

**Acceptance**: a complete game can be played with all documented rules enforced;
declarations trigger at the right moments and are visible; no stale "empty-hand"
turn broadcasts; suite green.

**Tests**: new feature tests per gap (partiya, jacks validation, five-same-suit
all-trump, dealer pickup, 5-trick round transition), plus unit tests. Manual play
script in `web/` notes per `AGENTS.md` (no frontend runner yet — see P1).

**Related docs**: `game_rules.md` §3/§6/§7, `stage_5_3.md`, `stage_7_6_rule_fix.md`,
`stage_7_9.md`, `stage_7_7_pass_feature.md`.

---

## Phase 1 — Fairness & robustness

Resolves: **G-7, G-11, G-18, G-19**; hardens WS flow (G-14 partial).

1. **Hidden hands** (G-7): `getState` redacts non-requesting hands (server-side;
   seat 0 today, per-session seats later). Update `02_api_reference.md` schema note.
2. **Winner tie-break data** (G-11): persist per-round pile history (or
   "round reached") and feed `Scoring::winnerPlayerIndex`.
3. **Test expansion** (G-18): feature coverage for pass edge cases (≤1 active
   players, dynamic trick size), five-same-suit flows, Boys announcements,
   tie-breaks, resume endpoint, WS broadcast payloads, AI participation.
4. **Frontend tests** (G-19): add Vitest; unit-test store pure logic
   (`getAiMoveKey`, `handleStateUpdate` queue, `humanHasJacks` replacements);
   keep Playwright for E2E smoke.
5. **WS hygiene** (G-14 partial): heartbeat/ping, state epoch or version so stale
   broadcasts are dropped; re-sync on subscribe already exists — verify with a
   reconnect E2E spec.

**Acceptance**: API payloads never leak opponents' cards; tie games resolve
deterministically; reconnect E2E passes; store logic unit-tested.

**Tests**: feature + Vitest + Playwright (`tests/e2e/`).

**Related docs**: `stage_5_5.md` (WS), `stage_7_10.md` (session resume),
`stage_7_11_realtime_sync_fix.md` (sync).

---

## Phase 2 — Multiplayer

Resolves: **G-7 (per-session), G-14 (auth)**; new capabilities per
`ai_dev/dev_stages.md` "Optional Future Stages" and `ai_dev/README.md` future list.

1. **Sessions & identity**: replace opaque `X-Player-Session-ID` with real player
   accounts or durable guest identity; `players.type` gains per-game human seats.
2. **Lobby & matchmaking**: game states `waiting_for_players` → `in_progress`
   (design mapping in `rams_game_design_plan.md`), invite/join flow, seats 0–3 up
   to 4 humans; AI fills empty seats.
3. **Authorized realtime**: private channel auth (token/IAM) in the relay; per-seat
   hand delivery through `getState` redaction (from P1); event-sourced state or
   versioned payloads to survive dropped messages (G-14).
4. **Concurrency**: turn ownership moves to per-request validation of the acting
   player identity (no more blind `seat_index` trust); DB row locking or optimistic
   concurrency in `GameService` transactions.

**Acceptance**: 2–4 humans + AI can join a game via a lobby, play an entire match
with hands visible only to owners, and reconnect mid-game without state loss.

**Tests**: feature tests for auth/roles, join flows, concurrent-move rejection; E2E
two-browser session; relay auth unit tests (Node).

**Related docs**: `ai_dev/README.md` (multiplayer goal), `dev_stages.md` optional
stages, `rams_game_design_plan.md` (lobby/waiting UI), `stage_5_5.md` (WS).

---

## Phase 3 — Product & UX polish

Resolves: **G-17 (content), G-20 (strategy)**; builds on
`rams_ui_redesign_instructions_for_gemini_cli.md` (largely implemented in stage 7.1)
and `rams_game_design_plan.md`.

1. **Game log / announcements**: persistent feed for partiya, Jacks first/second,
   round results (replaces transient overlays).
2. **Animations**: card slide to trick, trick-winner highlight, score ticker —
   short, informational only (per redesign brief §13).
3. **Mobile layout**: responsive seats/felt (basic media query exists; needs a
   real pass).
4. **Advanced AI** (`dev_stages.md`): lead strategy, trump management, Jacks &
   partiya decisions integrated, risk evaluation vs. pile.
5. **Audio** (optional, muted by default).

**Acceptance**: playable on phone-width viewports; feature parity between AI and
rules; game log clearly narrates all special events.

**Tests**: Playwright responsive specs; AiServiceTest strategy cases; manual
verification notes.

**Related docs**: `rams_ui_redesign_instructions_for_gemini_cli.md`,
`rams_game_design_plan.md`, `dev_stages.md` §6/optional, `stage_7_1.md`–`stage_7_5.md`.

---

## Phase 4 — Operations & deployment hardening

Resolves: **G-14 (ops)**; builds on existing `docker/`, `compose.yml`,
`config_nginx.sh`, `setup_ssl.sh`, `remote_deploy.sh`.

1. **WSS/TLS in the relay path** (Nginx already terminates TLS; verify `/ws`
   upgrade through the proxy, `WEBSOCKET_URL` consistency).
2. **Monitoring**: health endpoint for the relay, heartbeat metrics, request
   logging for broadcasts, basic alerts.
3. **Persistence for realtime** (optional): Redis pub/sub or a queue so broadcasts
   survive relay restarts; horizontal scale-out plan.
4. **CI**: extend `.github/workflows/quality.yml` with frontend build + Vitest +
   Playwright smoke; keep pint/composer audit.

**Acceptance**: production `wss://` works through the existing Nginx setup; relay
restart does not silently lose state (re-sync or queue); CI gates all four
components.

**Tests**: deployment smoke (`check-prod.js`, E2E against prod URL); k6/locust
optional.

**Related docs**: `compose.yml`, `config_nginx.sh`, `setup_ssl.sh`,
`remote_deploy.sh`, `stage_5_5.md` (scalability notes), `.github/workflows/quality.yml`.

---

## Sequencing guidance

- P0 unblocks everything else (rules are the product).
- P1 and P2 share the hand-privacy work (P1 introduces server-side redaction; P2
  extends it to identities).
- P3 is independent and can be interleaved; P4 is opportunistic.
- Each phase keeps the backend authoritative and the frontend purely state-driven —
  the two invariants stated in `ai_dev/README.md` and
  `rams_ui_redesign_instructions_for_gemini_cli.md`.

## Definition of done (per phase)

- All linked `G-n` items closed or explicitly deferred with a reason.
- Backend tests extended and green (`cd api && composer test`).
- Frontend changes verified per `AGENTS.md` (manual notes; Playwright for flows;
  Vitest where added).
- `ai_dev/specs/*` updated to reflect the new verified reality (rules/API/gaps).