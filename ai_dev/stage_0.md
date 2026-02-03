# Stage 0 – Preparation & Analysis (Result)

## Scope & Non-Goals (for Stage 0)

- This stage produces analysis + decisions only.
- No implementation (Laravel/Vue) yet.
- Authoritative rules source: `ai_dev/game_rules.md`.

## Game Summary (Implementation-Ready)

### Core parameters

- Players: 4
- Deck: 36 cards (ranks 6..A), 4 suits
- Trump: none
- Goal: minimize total penalty score

### Round structure

Each round is:

1. Shuffle deck
2. Deal 9 cards to each player (clockwise); deck becomes empty
3. Bidding phase (each player bids once)
4. Playing phase (9 tricks, 4 cards each)
5. Round scoring and add to total
6. Dealer advances clockwise, next round begins

### Card ordering

Ascending order:

`6 < 7 < 8 < 9 < 10 < J < Q < K < A`

### Bidding

- Bid is an integer 0..9
- Bidding order: starts from player left of the dealer, then clockwise
- Each player bids exactly once per round
- Constraint: sum of all 4 bids must not equal 9
  - If after the first three bids the current sum forces the last bidder to avoid a single value, the last bidder must choose any other legal value 0..9.

### Trick play

- First trick leader: player left of the dealer
- A trick:
  - 4 turns, one card per player
  - Leading suit is the suit of the first card played in the trick
  - Follow-suit rule: players must follow leading suit if possible; otherwise any card
- Trick winner:
  - Highest rank card among cards of the leading suit wins
  - Winner collects the trick and leads the next trick

### Round scoring

For each player:

- `taken_tricks` = number of tricks won in the round
- `bid` = player’s bid for the round
- Round penalty = `abs(taken_tricks - bid)`
- Add round penalty to total score

Lower total score is better.

### End of game

- Game ends when any player reaches or exceeds 100 points.
- Winner is the player with the lowest score.
- If multiple players share the same lowest score, `game_rules.md` suggests:
  - Winner is the one who reached that score later, OR
  - Implementation-defined.

## Backend Authority & Determinism Decisions

### Backend is the single authority

- All rule validation occurs server-side.
- Frontend must not “assume” legality; it should reflect server validation results.

### Determinism requirements

- Game outcomes must be reproducible given the same:
  - initial RNG seed (or recorded shuffle order)
  - player decisions
- Persist all inputs that affect state transitions:
  - deck order (or seed)
  - bids
  - played cards (moves)

## Edge Cases & Validation Checklist

### Bidding edge cases

- Ensure each bid is in 0..9.
- Ensure exactly one bid per player per round.
- Enforce “sum of bids != 9”:
  - If last bidder submits the forbidden value that makes sum = 9, reject with validation error.
  - If implementing AI bidding later, AI must also obey this constraint.

### Trick / move validation

Reject as illegal:

- Playing out of turn
- Playing a card not in the player’s current hand
- Not following suit when the player has at least one card of the leading suit

Derived rules to validate:

- On first play of a trick, any card is allowed (leading suit is established by that card)
- On non-first plays of a trick:
  - if player holds any card of leading suit, only those are legal
  - else any card is legal

### Trick winner edge cases

- There is no trump, so winner is determined only among leading-suit cards.
- Ranks are strictly ordered; no ties inside a suit.

### Round boundaries

- Exactly 9 tricks per round.
- At round end:
  - all hands must be empty
  - round scoring must run once
  - dealer rotates clockwise

### Game end / winner tie-break

Open implementation decision (needs to be made in Stage 1/2):

- If multiple players have the same lowest score at game end, choose one:
  - Option A (recommended): winner is the player who crossed the 100 threshold last / reached final low score later (requires tracking timestamps/round index)
  - Option B: declare a tie

Recommendation: implement Option A by storing per-player “score history per round” and compare the round index when the final score was first reached.

## Data Model Notes (for upcoming stages)

From `ai_dev/README.md` architecture intent:

- Frontend: Vue 3 + Pinia
- Backend: Laravel REST API
- Single-player mode: 1 human + 3 AI

Domain concepts to model:

- Game
- Player (human/ai, seat position)
- Round (dealer position, bids, trick index)
- Trick (leader, leading suit, 4 moves)
- Move (player, card, order)
- Card (suit, rank; immutable identity)

## Stage 0 Completion

Deliverables required by `ai_dev/dev_stages.md` are present in `ai_dev/`:

- `README.md`
- `game_rules.md` (acts as GAME_RULES)
- `dev_stages.md`
- This document: `stage_0.md`
