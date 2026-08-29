# 03 — Gap Analysis & Known Issues (Prioritized)

> Purpose: complete, prioritized list of discrepancies between documentation,
> intended rules, and the verified implementation. Each entry: observed behavior,
> expected, impact, affected files, suggested fix, tests. IDs (`G-n`) are referenced
> from the other spec documents.

Legend: 🔴 High (correctness/cheat/fairness) · 🟠 Medium (robustness/UX) ·
🟢 Low (cleanup/docs).

---

## 🔴 G-1 — Partiya not implemented

- **Observed**: `Scoring::mustDeclarePartiya()` exists but is never called;
  `POST /api/games/{id}/declare-partiya` returns `501`; `rounds.partiya_declared_by`
  never set; no UI/AI declaration.
- **Expected** (`game_rules.md` §7, spec 01 §7): players with pile ≤ 5 must announce
  "partiya" when winning the exact tricks to reach 0; failing → +5.
- **Impact**: an entire documented rule is missing; game can end without the
  announcement ritual.
- **Files**: `api/app/Services/Rams/GameService.php`,
  `api/app/Http/Controllers/GameController.php`, `api/routes/api.php`,
  `api/app/Domain/Rams/Scoring.php`, `web/src/stores/game.js`,
  `web/src/components/PhasePanel.vue`.
- **Suggested fix**: decide trigger (spec 01 §9.1), implement declaration validation
  + penalty in `finishRound`, add AI declaration in `AiService`, surface in UI,
  E2E/feature tests.
- **Tests**: `api/tests/Feature/RamsGameApiTest.php` (+ new Partiya feature test).

## 🔴 G-2 — Frontend Jacks detection is impossible (same-suit bug)

- **Observed**: `game.js` getter `humanHasJacks` counts two Jacks **of the same
  suit** (`suitCounts[suit] >= 2`); a 36-card deck has exactly one Jack per suit, so
  the panel never appears. Also checks `hand.length === 5` (breaks after the first
  play).
- **Expected**: two Jacks of the **same color** (♥+♦ red, ♠+♣ black) — spec 01 §6.
- **Impact**: the whole Jacks UI feature is dead.
- **Files**: `web/src/stores/game.js`, `web/src/components/PhasePanel.vue`,
  `web/src/components/GameTable.vue`.
- **Suggested fix**: mirror `GameService::detectBoys` logic in the getter (or derive
  from `round.boys_state`); render "Boys" pairs and the first/second-Jack
  announcements (with G-4).
- **Tests**: manual checklist; optionally a small Vitest suite (see G-19).

## 🔴 G-3 — `declare-jacks` endpoint: wrong semantics + no validation

- **Observed**: `POST /declare-jacks { player_index }` sets the player's pile to 5
  and increments `maltzy_count` **without checking the hand** — any player can call
  it during `play` and get a pile of 5. Semantics contradict the canonical round-end
  deduction (spec 01 D-8).
- **Expected**: either removal, or a strict re-implementation as a declaration that
  requires holding two same-color Jacks (and becomes obsolete once Boys rule drives
  everything).
- **Impact**: cheat vector; confusing duplicate mechanic.
- **Files**: `api/app/Services/Rams/GameService.php::declareJacks()`,
  `api/app/Http/Controllers/GameController.php`, `web/src/stores/game.js`,
  `web/src/components/PhasePanel.vue`.
- **Suggested fix**: decide (remove or re-scope to match D-8); if kept, validate
  hand contents and per-round once-only.
- **Tests**: feature test asserting rejection without Jacks.

## 🟠 G-4 — Boys/"first & second Jack" announcements never shown

- **Observed**: backend broadcasts `boys.announcement` extra data (`message`,
  `playerIndex`) merged into `game.update`; the frontend ignores it (no rendering,
  no store field).
- **Expected** (`game_rules.md` §6, spec 01 §6): UI shows "first Jack came out" /
  "second Jack came out" when a Jack of a declared pair is played.
- **Impact**: rule's informational requirement invisible.
- **Files**: `web/src/stores/game.js` (`handleStateUpdate`), `PhasePanel.vue` /
  `App.vue` (toast/log).
- **Suggested fix**: add a transient message queue in the store fed by
  `boys_state`/announcement payloads; display like the round-finished banner.
- **Tests**: E2E assertion when a Jack pair resolves.

## 🟠 G-5 — Exchange status string mis-orders players when dealer ≠ 0

- **Observed**: `GameService::getExchangeStatus()` builds the message looping the
  exchange order but compares raw seat indices (`$i < $currentPlayer`), so with
  dealer 2 (order [3,0,1,2]) the "P0 exchanging..." line advances out of order.
- **Expected**: progress reflects exchange order regardless of dealer.
- **Impact**: misleading UX during exchange.
- **Files**: `api/app/Services/Rams/GameService.php::getExchangeStatus()`.
- **Suggested fix**: track positions in `$exchangeOrder`; produce per-step segments
  (also drop the fragile frontend `P(\d+)` regex, or emit structured data).
- **Tests**: unit test for each dealer index.

## 🟠 G-6 — `player_index` validation allows 0..4 (4-player game)

- **Observed**: `exchange`/`move` validations accept `0..4`
  (`> 4` check); `participation`/`declareJacks` use `max:3`. A 4-index passes the
  first two endpoints and fails downstream with confusing messages.
- **Expected**: consistent `0..3`.
- **Impact**: validation inconsistency; confusing 4xx.
- **Files**: `api/app/Http/Controllers/GameController.php`.
- **Suggested fix**: `0..3` everywhere; single validation helper.
- **Tests**: feature test asserting 422 on player_index 4.

## 🔴 G-7 — `getState` exposes every player's hand

- **Observed**: `round.hands` contains all four hands; the client receives AI hands
  in every state payload (JavaScript console shows them).
- **Expected**: only the requester's hand (seat 0 today; per-session seats later).
- **Impact**: information leak; blocks multiplayer fairness.
- **Files**: `api/app/Services/Rams/GameService.php::getState()`.
- **Suggested fix**: redact other hands server-side (single-player: return only
  seat 0; later: per-session seat).
- **Tests**: feature test asserting payload has only seat 0's hand.

## 🟠 G-8 — Round end relies on empty-hand fallback

- **Observed**: `playCardOnce` detects round end via `array_sum($taken) >= 5`,
  but the 5th-trick winner's next play hits the "empty hand ⇒ finishRound" branch
  instead of a clean transition; `trick_number` is displayed/incremented but not
  authoritative for ending.
- **Expected**: after the 5th trick resolves, finish the round immediately and
  broadcast the new round state (frontend keeps its 1.5 s trick-view pause).
- **Impact**: extra state broadcast + an artificial "turn" with an empty hand;
  fragile.
- **Files**: `api/app/Services/Rams/GameService.php::playCardOnce()`.
- **Suggested fix**: after awarding the 5th trick, call `finishRound()` directly
  (keep the "full trick" broadcast before). Align `trick_number` semantics.
- **Tests**: feature test that a full 5-trick round yields a fresh round without an
  empty-hand turn.

## 🟠 G-9 — Five same suit: timing & all-trump case deviate from `game_rules.md`

- **Observed**: auto-applied at deal and per-exchange (D-4) with winner −5, others
  0. `game_rules.md` §3 = "announce before first move" + "others +5 when the flush
  is all-trump".
- **Expected**: documented rule set honored (announcement + all-trump +5) — pending
  spec 01 §9.2 decision.
- **Impact**: rule divergence from source rules.
- **Files**: `api/app/Services/Rams/GameService.php::checkAndApplyFiveSameSuit()`,
  `api/app/Domain/Rams/Exchange.php::priorityFiveSameSuit()`.
- **Suggested fix**: implement all-trump +5 others per decision; add announcement
  step for the human.
- **Tests**: unit tests for flush + all-trump flush scenarios.

## 🟢 G-10 — Dealer trump pickup missing

- **Observed**: no code path lets the dealer swap 1 card for the revealed trump
  after their exchange.
- **Expected** (`game_rules.md` §2.2; spec 01 §9.3): optional dealer action.
- **Impact**: documented tactic absent (strategic depth).
- **Files**: `GameService` (exchange phase end), API route, `PhasePanel.vue`.
- **Suggested fix**: optional `dealer take trump` action at exchange end; decide
  trump-suit semantics (§9.3).
- **Tests**: feature test verifying hand/trump update.

## 🟠 G-11 — Winner tie-break data not persisted

- **Observed**: `Scoring::winnerPlayerIndex($piles, $roundWhenPileReached)` uses the
  reached-last tie-break, but the service never builds `roundWhenPileReached`, so
  ties fall back to `-1` for everyone → first candidate wins (effectively
  lowest-index).
- **Expected** (spec 01 D-3): reached-last rule with real data.
- **Impact**: tie games resolved arbitrarily.
- **Files**: `Scoring.php`, `GameService.php::finishRound()` /
  `startNextRoundOrFinish()` (record round index when a pile first hits its final
  value).
- **Suggested fix**: persist per-player "round when final pile reached" (or a per
  round score history) and pass into the winner function.
- **Tests**: scoring unit tests with tie scenarios.

## 🟢 G-12 — Terminology drift: Maltzy / Jacks / Boys

- **Observed**: column `maltzy_count`, round field `boys_state`, route
  `declare-jacks`, frontend `humanHasJacks`, docs `stage_5_3` (Jacks),
  `stage_7_9` (Boys), `game_rules.md` §6 (Jacks).
- **Impact**: confusion in code review and AI-assisted development.
- **Files**: across the codebase; see [`05_glossary.md`](05_glossary.md).
- **Suggested fix**: adopt "Jacks" as the public term and document code aliases;
  optional rename migration later (schema stability first).
- **Tests**: none required.

## 🟢 G-13 — Dead code & duplicate tests from old variants

- **Observed**: `api/app/Domain/Rams/BiddingRules.php` +
  `api/tests/Unit/Rams/BiddingRulesTest.php` belong to the pre-5.1 bidding variant
  (unused by `GameService`); `api/tests/Unit/TrickRulesTest.php` (legacy) duplicates
  `api/tests/Unit/Rams/TrickRulesTest.php`.
- **Impact**: maintenance confusion; misleading coverage.
- **Files**: as listed.
- **Suggested fix**: delete after confirming no references; consolidate the
  TrickRules tests.
- **Tests**: suite stays green.

## 🟠 G-14 — WebSocket relay limitations

- **Observed** (documented in `stage_5_5.md`): public channels (any client knowing
  the id can subscribe), no auth, no message persistence (missed broadcasts on
  disconnect), single process, no TLS/WSS natively, backend broadcast is
  fire-and-forget (1 s curl timeout, failed broadcasts silently logged).
- **Impact**: correctness risk on reconnect; multiplayer (P2) blocker; prod TLS
  handled by Nginx only.
- **Files**: `websocket-server.js`, `web/src/websocket.js`,
  `GameService::broadcastGameUpdate()`, `docker/`, `config_nginx.sh`.
- **Suggested fix**: roadmap P2/P4 (auth/heartbeat/state-versioning, persistence or
  re-sync-on-subscribe (already partially present), TLS, monitoring). P0/P1: keep,
  but add heartbeat + state epoch to detect stale broadcasts.
- **Tests**: E2E reconnect scenarios.

## 🟢 G-15 — Bounds validation commented out in Scoring

- **Observed**: `Scoring::applyRoundScoring` has the 0..4 tricks check commented out
  (a player may win up to 5 tricks with 5-trick rounds).
- **Expected**: validate `0..5` explicitly (or accept `max tricks = activeCount`).
- **Impact**: silent reliance on caller correctness.
- **Files**: `api/app/Domain/Rams/Scoring.php`.
- **Suggested fix**: enforce `0..#($tricksWonByPlayer)` bounds dynamically.
- **Tests**: scoring unit tests for out-of-range input rejection.

## 🟢 G-16 — Documentation inconsistencies

- **Observed**: `stage_0.md` documents the obsolete 9-card bidding variant;
  `stage_5_4.md` claims 4 tricks/round (code = 5, D-1); root `README.md` still says
  "must play trump if void" (D-2) and describes a 5-player/25-pile era in places;
  RulesPopup omits Jacks & partiya.
- **Impact**: AI agents and new contributors mis-implement rules.
- **Files**: docs as listed + `web/src/components/RulesPopup.vue` (G-17).
- **Suggested fix**: leave historical stage docs untouched (record), rely on
  `ai_dev/specs/*` as canonical; fix root README's stale claims.
- **Tests**: none.

## 🟢 G-17 — RulesPopup content gaps

- **Observed**: "How to Play" modal covers goal/deal/exchange/tricks/scoring but not
  Jacks (Boys), partiya, or the pass/choose-to-play flow.
- **Files**: `web/src/components/RulesPopup.vue`.
- **Suggested fix**: add the missing sections from spec 01 §6–7.
- **Tests**: E2E rules-popup spec (`tests/e2e/rules-popup.spec.js`).

## 🟠 G-18 — Automated test coverage gaps

- **Observed**: feature suite covers create/state, exchange+participation flow,
  follow-suit only; no coverage for pass edge cases (≤1 active), dynamic trick size
  with passers, five-same-suit (auto-apply, all-trump), Boys announcements,
  tie-breaks, resume endpoint, WS broadcast payloads, AI participation decisions.
- **Files**: `api/tests/Feature/RamsGameApiTest.php`, `api/tests/Unit/Rams/*`,
  `tests/e2e/*`.
- **Suggested fix**: expand per roadmap P0/P1 milestones.
- **Tests**: see above.

## 🟢 G-19 — No frontend test runner

- **Observed**: `AGENTS.md` documents manual verification only; store logic (AI
  scheduling keying, trick-view queue) is untested; existing Playwright specs are
  smoke-level (require the full stack).
- **Impact**: UI regressions ship silently (e.g. G-2, G-4).
- **Files**: `web/package.json`, `web/src/stores/game.js`.
- **Suggested fix**: add Vitest + minimal store tests (pure logic:
  `getAiMoveKey`, queue handling, getters); keep Playwright for E2E.
- **Tests**: Vitest suite.

## 🟢 G-20 — AI feature parity (declarations & strategy limits)

- **Observed**: AI never declares Jacks (legacy path) or partiya; never takes the
  trump; participation heuristic = "any trump or ace"; exchange keeps ≥ 2 cards of
  any quality.
- **Impact**: AI cannot exercise the full rule surface; partiya (G-1) needs AI
  support to be playable.
- **Files**: `api/app/Services/Rams/AiService.php` (+ roadmap P3 "Advanced AI").
- **Suggested fix**: implement declarations when respective rules land; later add
  strategy heuristics (lead Aces, trump management, risk).
- **Tests**: `AiServiceTest` extensions.

---

## Summary

| Severity | Count | Items |
|---|---|---|
| 🔴 High | 4 | G-1, G-2, G-3, G-7 |
| 🟠 Medium | 8 | G-4, G-5, G-6, G-8, G-9, G-11, G-14, G-18 |
| 🟢 Low | 8 | G-10, G-12, G-13, G-15, G-16, G-17, G-19, G-20 |

*(Counts are indicative; see roadmap P0 for the resolution order.)*