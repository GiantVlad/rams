# Plan B — P0 Rule Correctness

Goal: close G-1, G-2, G-3, G-4, G-5, G-6, G-7, G-8, G-9, G-10. All items are rule-correctness defects; together they make the game fully playable per `ai_dev/specs/01_game_rules_canonical.md`. Each sub-task is independently shippable, but the pass works best as a sequence because G-2/G-4/G-3 depend on each other and G-8 touches the trick-end path.

---

## B-1. Partiya end-to-end (G-1) — medium

Backend:
- Implement `GameService::declarePartiya(Game, playerIndex)`: validate the player holds enough tricks remaining to reach pile 0 (`mustDeclarePartiya` predicate is already written; wire it in). On success, set `rounds.partiya_declared_by[$playerIndex]` and record the declaration. If the player fails to declare when required, apply the +5 penalty after scoring (`Scoring::applyRoundScoring` — add a `partiya_declared_by` parameter so it can exempt declared players).
- Add `POST /api/games/{id}/declare-partiya` in `GameController` (currently a 501 stub). Validate `player_index` 0..3 and require the game to be in `play` phase.
- Update `Scoring::applyRoundScoring` signature to accept `$partiyaDeclaredBy` (default `[]`) and skip the +5 zero-trick penalty for declared players.

AI:
- Add `AiService::shouldDeclarePartiya(hand, pile, tricksRemaining)` — returns true when the hand's tricks remaining equals the pile (per `mustDeclarePartiya`). Wire it into `makeAiMove`'s `play` phase: before playing, if `shouldDeclarePartiya` and the player hasn't declared yet, call `declarePartiya` instead of playing.

Frontend:
- Add a `declarePartiya()` action in `game.js` calling `POST /declare-partiya`.
- In `PhasePanel.vue`, when `game.isHumanTurn && phase === 'play' && pile <= 5 && !partiyaAlreadyDeclared`, show a "Declare Partiya" button.
- The backend broadcast already sends full state — nothing special needed except the button.

Tests: feature test covering declaration + the +5 exemption; AI declaration scenario.

---

## B-2. Jacks/Boys UI fix (G-2) — small

The `humanHasJacks` getter searches `suitCounts[suit] >= 2` (same suit), which is impossible in a 36-card deck.

- Rewrite the getter to mirror `GameService::detectBoys`: count J♥+J♦ (red) and J♠+J♣ (black) separately and return the color string if either pair is present, else null.
- Add a `partiyaAlreadyDeclared` / `jacksAlreadyDeclared` check: `humanHasJacks` should only return a color if the player hasn't already declared that pair this round (derive from `round.boys_state[$seat]`).
- Fix `PhasePanel.vue` button text: "Declare Jacks (-5 Pile)" — keep (the round-end deduction path is canonical).

Tests: Vitest-style unit test of the getter against hand arrays; manual UI check.

---

## B-3. `declare-jacks` endpoint validation (G-3) — small

The current `declareJacks` sets pile→5 without checking the hand.

- In `GameService::declareJacks`: validate the player's hand contains two Jacks of the same color (reuse `detectBoys` logic or add `Exchange::hasBoysPair($hand)`). Reject with `422` if no pair.
- Keep pile→5 as the legacy "instant declare" path (documented as such) — OR switch it to be a no-op now that `detectBoys` drives the canonical round-end deduction. Recommended: keep pile→5 as an explicit declaration action but gate it on hand possession; the round-end deduction remains the automatic path.
- Update `PhasePanel.vue`: disable the "Declare Jacks" button when `!humanHasJacks`.

Tests: feature test asserting 422 when the hand has no same-color Jack pair.

---

## B-4. Boys announcements render (G-4) — small

Backend already broadcasts `boys.announcement` extra data; frontend ignores it.

- In `game.js`, add a `boysAnnouncement` state field (string) and a `dismissBoysAnnouncement()` action.
- In `handleStateUpdate`, when the payload contains a `boys.announcement` message (or when the `boys_state` changes for the current player), set `boysAnnouncement`.
- In `App.vue` or a new `BoysAnnouncement.vue` component, render a toast (matching the round-finished banner style) with the message ("first Jack came out" / "second Jack came out"). Auto-dismiss after 3 s or on click.
- Wire `boys.announcement` into the WS listener (it's currently merged into `game.update`; the `boys_state` change already triggers `handleStateUpdate`, so just check `state.round.boys_state` deltas — simpler than adding a separate event).

Tests: E2E assertion that the toasts appear when a pair's Jack is played.

---

## B-5. Five-same-suit alignment (G-9) — medium

Per `ai_dev/specs/01_game_rules_canonical.md` §9.2: decide whether to honor `game_rules.md`'s all-trump +5 others case.

- If the decision is "yes, add +5 others when the flush is all-trump": add a `isAllTrump` flag in `checkAndApplyFiveSameSuit` and apply `+5` to all non-winner piles. Update `Scoring::applyRoundScoring` or add a dedicated path in `finishRound` for the five-same-suit scoring (currently it does winner −5, others 0 inline).
- If the decision is "no, keep current behavior": document the deviation in `ai_dev/specs/01_game_rules_canonical.md` as a resolved decision (D-5 already says "others 0 (current)").
- Announce in UI: when five-same-suit fires, broadcast a human-readable message (the backend already includes `five_same_suit_declared` in state; the frontend can derive the message).

Tests: feature/unit tests for both the flush and all-trump-flush paths.

---

## B-6. Dealer trump pickup (G-10) — medium

Per `ai_dev/specs/01_game_rules_canonical.md` §9.3: after the dealer finishes their exchange, allow them to swap 1 hand card for the revealed trump.

- Add a new phase or a post-exchange step: when `playerIndex === dealer_index` and the dealer just finished exchanging, present a "Take trump" option in the UI (or auto-apply per the product decision).
- If taken: remove one card from the dealer's hand (the one they choose, or the lowest if auto) and replace it with the trump card; the trump suit stays fixed.
- If not taken (or the decision is to never implement): document as a future feature in `ai_dev/plans/01_rule_correctness.md`.

Tests: feature test verifying the dealer's hand after the optional pickup.

---

## B-7. Exchange status ordering (G-5) — tiny

`GameService::getExchangeStatus()` builds segments by iterating exchange order but comparing raw seat indices, which mis-orders when `dealer_index ≠ 0`.

- Change `getExchangeStatus()` to iterate the `$exchangeOrder` array and produce one segment per player in that order, deriving status from each player's actual exchange state.
- Alternatively, emit structured data (`{playerIndex, status, count}`) instead of a prose string and let the frontend localize. Recommended: the former (minimal change).

Tests: unit test for each dealer index (0, 1, 2, 3) confirming the message order matches exchange order.

---

## B-8. Hidden hands in `getState` (G-7) — small

`getState` returns `round.hands` for all four players. Before multiplayer, redact non-requesting hands.

- Add a `$requesterSeat` parameter (or read from session/auth) to `getState`. For now, since this is single-player, redact all hands except seat 0 (the human) — satisfying the fairness requirement for the public API without adding auth.
- Alternatively, expose a separate `getStateForSeat(seat)` and have the controller call it with `seat_index: 0` for the human and `null` for spectators. Recommended: the latter (cleaner, sets up the multi-seat path).
- The controller's `show`, `exchange`, `move`, `participation`, `declare-jacks`, `declare-partiya` routes should call `getStateForSeat($requesterSeat)`.

Tests: feature test asserting the response hands only contain seat 0's cards.

---

## B-9. Round-end via 5th trick, not empty-hand fallback (G-8) — small

`playCardOnce` calls `finishRound` when a player's hand empties, but the round should end after the 5th trick resolves cleanly.

- In `playCardOnce`, after awarding the 5th trick (`$totalTricks >= 5`), call `finishRound` directly and return. Remove the "empty hand ⇒ finishRound" fallback, or keep it as a safety net but ensure it's never reached in normal play (add a warning log if hit).
- Align `trick_number` semantics: it's informational; ensure it's 1–5 and reset to 0 after `finishRound`.

Tests: feature test that a complete 5-trick round transitions to a fresh round without an empty-hand turn; ensure `trick_number` resets.

---

## B-10. Winner tie-break data (G-11) — medium

`Scoring::winnerPlayerIndex` accepts `$roundWhenPileReached` but the service never builds it.

- In `finishRound`, record per-player "round index when pile first reached its final value" (a `roundWhenPileReached` array). Store it on the `Game` model (new column `pile_reach_rounds` json) or pass it through `startNextRoundOrFinish`.
- Feed it into `Scoring::winnerPlayerIndex` in `startNextRoundOrFinish`.
- The existing `stage_0.md` decision (D-3) and `Scoring` doc already describe this; this task just implements it.

Tests: unit tests for tie scenarios where two players reach the same pile in different rounds.

---

## Sequencing

Do B-1 and B-2 together (partiya + Jacks UI share the declaration flow).
Do B-3, B-4 after B-2 (they depend on the Jacks detection).
Do B-7 (tiny) and B-9 (small) anytime — they're low-effort correctness fixes.
Do B-5, B-6 after the product decision (spec 01 §9).
Do B-8 (hidden hands) and B-10 (tie-break) as the fairness hardening pass.

## Verification

- `cd api && composer test` — green; new tests for partiya, Jacks validation, five-same-suit, tie-breaks, round-end.
- `./vendor/bin/pint --test` — PASS.
- Manual: play a full game end-to-end with all declared rules firing.