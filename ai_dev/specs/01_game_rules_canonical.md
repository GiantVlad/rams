# 01 — Rams Game Rules (Canonical)

> **Purpose**: single source of truth for the implemented Rams variant. Reconciles
> [`ai_dev/game_rules.md`](../game_rules.md) with the verified behavior of the code
> (`api/app/Domain/Rams/*`, `api/app/Services/Rams/GameService.php`).
>
> **Reading guide**: sections marked **[implemented]** behave as described today.
> Sections marked **[gap]** are documented rules that are **not** implemented; each
> links to a prioritized entry in [`03_gap_analysis.md`](03_gap_analysis.md). Rules
> where the documents conflict are resolved by an explicit **Decision (D-n)** below.
> Open product questions are listed in §9.

## 1. Parameters

| Parameter | Value |
|---|---|
| Players | 4 (seat 0 = human "You", seats 1–3 = AI Mike/William/Sarah) |
| Deck | 36 cards: suits ♠ ♥ ♦ ♣, ranks 6 7 8 9 10 J Q K A (values 6..14) |
| Cards per hand | 5 |
| Trump | 1 card revealed by the dealer after dealing |
| Initial pile | 20 points per player |
| Goal | Reduce pile to 0; lowest pile at game end wins |
| Game end | Any pile reaches 0 (checked after scoring) |
| Round structure | deal → exchange → choose_to_play (play/pass) → trick play → scoring → next round |
| Tricks per round | **5** (see D-1) |

## 2. Round flow (implemented)

```
        ┌──────────────┐
        │  new round   │  dealer rotates clockwise each round
        └──────┬───────┘
               ▼
        ┌──────────────┐   shuffle (seeded), deal 5 cards/player,
        │    deal      │   reveal trump card, 15 cards → remaining_deck
        └──────┬───────┘
               ▼
        ┌──────────────┐   five-same-suit check (auto-apply, D-4)
        │  exchange    │   players left-of-dealer first, dealer last:
        └──────┬───────┘   0–5 discards replaced from remaining_deck
               ▼
        ┌──────────────────┐  each seat decides Play or Pass (left of
        │ choose_to_play   │  dealer first, dealer last)
        └────────┬─────────┘
                 ▼
        ┌──────────────┐    5 tricks × up to 4 active plays;
        │     play     │    follow suit; trump optional when void (D-2);
        └──────┬───────┘    winner leads next trick
               ▼
        ┌──────────────┐    pile − tricks; +5 zero-trick penalty (passers
        │   scoring    │    exempt); −5 × Jacks pairs; clamp ≥ 0
        └──────┬───────┘
               ▼
     game end? ── yes ──► status=finished, winner = lowest pile (D-3 tie-break)
        │ no
        ▼
     next round (dealer +1)
```

## 3. Deal [implemented]

- `Deck::createStandard36()` → shuffled with a stored `seed`
  (`Random\Engine\Mt19937`, deterministic and reproducible).
- `Dealing::deal($deck, 4, 5)` deals round-robin to seats 0..3.
- The next deck card becomes `games.trump_card_id`; its **suit** is the trump suit.
- The remaining 15 cards are stored as `rounds.remaining_deck` for the exchange
  phase.
- Deck math: 20 dealt + 1 trump + 15 remaining = 36.

**Five same suit** (after deal and after every exchange, auto-applied — D-4):
if a player's 5-card hand is a single suit, the round ends immediately, that player's
pile is reduced by 5, everyone else is unchanged, and a new round starts (unless the
game ends). Priority when several players qualify: closest **left of the dealer**
wins. Implemented in `GameService::checkAndApplyFiveSameSuit()`
([gap] details in G-9).

## 4. Exchange phase [implemented]

- Turn order: the player **left of the dealer** first, proceeding clockwise; the
  **dealer exchanges last** (`GameService::exchangeCardsOnce`).
- On a player's turn they pick **0–5 cards from their hand**. Validated by
  `Exchange::validate()`:
  - discarded cards must be in the hand;
  - discard count must not exceed the cards remaining in the deck.
- Discards are removed from the hand and the same number of cards are drawn from
  `remaining_deck` (first-in = first-out; deck state is persisted each turnover).
- Records the exchanged count per player in `rounds.exchanged`.
- **Refuse exchange** is just "discard 0 cards" [implemented].
- After the dealer's exchange the phase becomes `choose_to_play`.
- AI exchange policy (`AiService::chooseDiscardCards`): never discards trump or
  Aces; discards lowest non-trump cards first, up to 3 cards, keeping ≥ 2 cards.
- **[gap]** `game_rules.md` §2.2: *"Pass completely → discard all cards and skip
  round (not dealer)"* — **not implemented** (see D-6). The current "pass" lives in
  the `choose_to_play` phase and does not discard cards.
- **[gap]** `game_rules.md` §2.2: *dealer may discard 1 card to take the revealed
  trump* — **not implemented** (G-10).

## 5. Choose to play (participation) [implemented]

- Added by the "Pass Round" feature (`stage_7_7_pass_feature.md`); phase string is
  `choose_to_play`.
- Turn order: player left of dealer first, dealer decides last.
- **Play**: participate in trick play; standard scoring applies.
- **Pass (fold)**: the player keeps their hand, takes no part in tricks, and is
  exempt from the zero-trick penalty (their pile is untouched at scoring).
  AI passes iff the hand has **no trump and no Ace**
  (`AiService::chooseToPlay`).
- If after all decisions **≤ 1 player remains active**: the round ends without
  trick play. A single remaining active player receives −5; if everyone passed, the
  dealer is treated as winner (−5). `rounds.passed_players` records folds; a PASS
  badge is shown in the UI.
- The active trick size is computed dynamically: `4 − count(passed_players)`
  (fix from `stage_7_8_pass_fix.md`).

## 6. Trick play [implemented]

- First trick is led by the player left of the dealer; after that, the **winner of
  the previous trick leads**.
- Each trick has one play per active player (dynamic size, §5).
- **Follow suit**: when the trick has a leading suit and the player holds at least
  one card of it, they must play that suit (`TrickRules::assertLegalPlay`).
- **Trump is optional when void** (D-2): a player without the led suit may play a
  trump or any other card — no obligation to trump.
- Playing out of turn / a card not in hand / failing to follow suit is rejected by
  the backend with `422 {message}`.
- **Trick winner**: the highest-rank card of the led suit, unless a trump was
  played, in which case the highest trump wins (`TrickRules::winnerPlayerIndex`;
  trumps sort by rank within trump).
- **5 tricks per round** (D-1). `rounds.trick_number` tracks the current trick;
  round end is detected when `array_sum(rounds.taken) >= 5`, backed by a
  finish-if-hand-empty fallback in `playCardOnce` when the last card is played
  (cleanup noted in G-8).
- The full (final) trick is broadcast and the frontend holds it on screen for
  1.5 s before clearing (`stage_7_9.md`).

### Jacks ("Boys") announcements [gap in UI, scoring implemented]

- When a player holds **two Jacks of the same color** (♥+♦ red, ♠+♣ black) at the
  start of play, it is detected (`GameService::detectBoys`), recorded in
  `rounds.boys_state`, and the player's `maltzy_count` is incremented **once per
  pair**.
- `game_rules.md` §6 requires the game to indicate *"first Jack came out"* and
  *"second Jack came out"* when the pair's Jacks are played. The backend broadcasts
  a `boys.announcement` message with the state; the **frontend never renders it**
  (G-4). The UI also contains a legacy `humanHasJacks` getter that searches for two
  Jacks of the *same suit* — impossible in a 36-card deck — so the "Declare Jacks"
  panel never appears (G-2). The `declare-jacks` endpoint (pile → 5) is a legacy
  remnant of `stage_5_3.md` and does not match the current scoring model (G-3).

## 7. Scoring [implemented]

Applied in `GameService::finishRound()` via `Scoring::applyRoundScoring()`:

| Rule | Math | Notes |
|---|---|---|
| Tricks won | `pile − tricks_won` | 1 point per trick |
| Zero-trick penalty | `+5` | only if the player participated and won 0 tricks; passers exempt |
| Jacks pair (per pair) | `−5` | `pile − 5 × maltzy_count`; offsets the zero-trick penalty (0 tricks + 1 pair ⇒ no change) |
| Clamp | `≥ 0` | pile never goes negative |

- `players.maltzy_count` is reset to 0 at the end of every round.
- **Game end**: after scoring, any pile `≤ 0` ends the game
  (`Scoring::isGameEnd`).
- **Winner**: lowest pile (`Scoring::winnerPlayerIndex`); on ties, the player who
  reached that pile **last** wins — the service does not currently persist the
  per-round reaching data needed for this tie-break (G-11), so the tie-break is
  effectively undefined today.

### Partiya [gap]

`game_rules.md` §7: when a player's pile is **≤ 5** at round start, they must
announce **"partiya"** upon winning the exact number of tricks that brings the pile
to 0 (example: pile 3 → announce after the 3rd trick). Failing to announce costs
**+5**.

- `Scoring::mustDeclarePartiya()` implements a simplified predicate
  (`pile ≤ 5 && pile === remaining tricks in round`) but is **never called**.
- The route/controller is a `501` stub; `rounds.partiya_declared_by` is never set;
  there is no UI. **Not implemented** (G-1).

## 8. Decisions (conflict resolutions)

| Id | Conflict | Resolution | Rationale |
|---|---|---|---|
| **D-1** | Tricks per round: `stage_5_4.md` says 4; `game_rules.md` §4 says 5 | **5 tricks** | Code (`array_sum($taken) >= 5`), `game_rules.md`, and deck math (5 cards × 4 players = 5 tricks × 4 plays) agree; `stage_5_4.md` is inaccurate |
| **D-2** | Must-trump-when-void: root `README.md` says mandatory; `game_rules.md` §4 says optional | **Optional** | Code (`TrickRules::assertLegalPlay` only enforces follow-suit), `game_rules.md`, `ai_dev/README.md`, and RulesPopup agree |
| **D-3** | Winner tie-break: `stage_5_4`/`stage_7_5` mention first-in-list/lowest-index; `stage_0.md`/`Scoring` doc say reached-last | **Reached-last** (design); record reaching round per player (G-11) | `Scoring::winnerPlayerIndex` implements reached-last when data is provided; matching classic Rams |
| **D-4** | Five-same-suit timing: `game_rules.md` §3 says "announce before first move"; code auto-applies after deal and after each exchange | **Auto-apply after deal and each exchange** (current) | `stage_7_6_rule_fix.md` documents auto-apply; announcement UX is a refinement (G-9) |
| **D-5** | Five-same-suit all-trump case: `game_rules.md` §3 says others get +5; `stage_7_6.md` says others get 0; code gives others 0 | **Others 0** (current); implement all-trump +5 as an enhancement decision | Code and recent stage doc agree; original rule recorded as future option (G-9) |
| **D-6** | "Pass completely → discard all + skip round (not dealer)" from `game_rules.md` §2.2 vs participation pass (keep hand, sit out tricks) | **Participation pass is canonical** (7.7+ design) | Introduced deliberately by `stage_7_7_pass_feature.md`; the old discard-all pass is historical |
| **D-7** | Jacks vs Maltzy vs Boys naming | **Jacks** is the user-facing term (pairs by color); "Boys" = implementation code name; `maltzy_count` column kept for schema stability | See `05_glossary.md` (G-12) |
| **D-8** | `declare-jacks` endpoint semantics (pile → 5) vs round-end deduction (−5 per pair) | **Round-end deduction is canonical**; endpoint is legacy | Current `Scoring` + `detectBoys` implement deduction; endpoint contradicts it and lacks validation (G-3) |

## 9. Open decisions for future development

These need a product answer before implementation; recommended defaults are given.

1. **Partiya mechanics** (G-1): exact trigger (predicate from `mustDeclarePartiya`?
   pile ≤ 5 and remaining tricks in hand equal pile?), when the announcement window
   opens/closes, and the failure penalty math (recommend: when a ≤-5-pile player
   wins enough tricks to reach 0 without declaring, apply `+5` to their pile after
   scoring: effectively no zero-trick penalty exemption).
2. **All-trump flush** (G-9): implement `game_rules.md` §3 "others +5" for an
   all-trump 5-same-suit hand? Recommended: yes, to honor the documented rules.
3. **Dealer trump pickup** (G-10): allow the dealer to swap 1 hand card for the
   revealed trump after their exchange; if the discard's suit differs from the
   trump's, does the trump suit change? Recommended: keep the trump suit fixed; the
   revealed card becomes the discarded card only if the suits match, otherwise the
   trump card is simply added to the hand and the discard leaves the game.
4. **Hidden hands** (G-7): `getState` currently returns every player's hand. Before
   multiplayer, the API must return only the requesting player's hand. Single-player
   today, so the human sees AI hands in the payload (they are not rendered).
5. **Exchange-order status string** (G-5): replace index comparison with
   exchange-order position.
6. **AI sophistication** (roadmap P3): partiya/jacks declarations for AI, lead
   strategy, risk awareness (see `dev_stages.md` "Advanced AI").
7. **Round winner display**: `stage_7_5.md` says lowest-index wins ties for the
   *round* message; `roundWinner` in the store already returns the first max —
   confirm this is desired vs. the game-end tie rule D-3.

## 10. Test coverage status

- Covered: deck construction/shuffle seed, dealing, trick follow-suit and winner
  rules, scoring incl. malty counts and zero-trick penalty, five-same-suit detection
  priority (unit), solution-example API flow (feature), AI discard/trick decisions.
- Not covered (see G-18, G-19): participation/pass edge cases, dynamic trick size with
  passers, boys announcements, game-end tie-breaks, partiya, five-same-suit
  all-trump case, WS broadcast payloads, session resume flows.
- Suite status at time of writing: **28 passed (96 assertions)** via
  `cd api && composer test` (SQLite in-memory).

---
*Related documents: [`game_rules.md`](../game_rules.md) (source rules),
[`stage_0.md`](../stage_0.md) (obsolete variant, historical),
[`stage_5_1_refactoring.md`](../stage_5_1_refactoring.md) (5-player refactor),
[`stage_5_4.md`](../stage_5_4.md) (4-player reduction), [`stage_7_6_rule_fix.md`](../stage_7_6_rule_fix.md)
(five same suit), [`stage_7_7_pass_feature.md`](../stage_7_7_pass_feature.md) (pass),
[`stage_7_9.md`](../stage_7_9.md) (Boys rule).*