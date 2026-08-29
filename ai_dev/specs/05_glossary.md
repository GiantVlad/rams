# 05 — Terminology Glossary (Maltzy / Jacks / Boys)

> Purpose: resolve the naming drift across documentation and code (gap G-12).
> **Canonical user-facing term: "Jacks"** (a pair of Jacks of the same color).
> "Boys" is the code/internal name; `maltzy` is a historical name from the 5-player
> era that survives only in the database column.

## The rule (spec 01 §6)

Two Jacks of the same **color** in a player's hand after the exchange:
- ♥ + ♦ = **red** pair
- ♠ + ♣ = **black** pair

Each pair scores **−5 pile** at round end and offsets the +5 zero-trick penalty.
The game announces "first Jack came out" / "second Jack came out" when each Jack of
the pair is played.

## Name map

| Term | Where it appears | Status |
|---|---|---|
| **Jacks** | `game_rules.md` §6, `web` buttons/panels, `POST /declare-jacks`, `humanHasJacks` getter | ✅ Canonical public term |
| **Boys** | `stage_7_9.md`, `rounds.boys_state`, `detectBoys()` | ✅ Internal code name (kept) |
| **Maltzy** | `players.maltzy_count`, `Scoring` params (`maltzyCountByPlayer`), `stage_5_1.md`/`stage_5_2.md`/`stage_5_3.md` history | ⚠️ Historical; survives only in the DB column — treat as "Jacks declarations this round" |
| **declare-jacks endpoint** | `GameController::declareJacks`, `GameService::declareJacks` | ⚠️ Legacy: pile→5 without validation; see G-3 / decision D-8 |
| **humanHasJacks** | `web/src/stores/game.js` | ⚠️ Buggy (same-suit check); see G-2 |
| **boys.announcement** | broadcast event (`GameService::playCardOnce`) | ⚠️ Broadcast but unrendered; see G-4 |

## Decision (D-7, spec 01 §8)

- New UI text, docs, and APIs: **Jacks**.
- Code identifiers already shipped stay stable for now (schema stability);
  `maltzy_count` is documented as "Jacks count this round".
- A future rename migration (column/field/route) is optional cleanup, not required
  for correctness.

## Related gaps

- G-2 (frontend detection), G-3 (legacy endpoint), G-4 (announcements),
  G-12 (this glossary).

## Historical context

- `stage_5_1_refactoring.md`: introduced "Maltzy" with the 5-player variant
  ("two Jacks of same color: −5 pile each").
- `stage_5_3.md`: renamed to "Jacks" and implemented `pile → 5` on declaration
  (superseded by round-end deduction).
- `stage_7_9.md`: reintroduced the pair mechanic under the name "Boys" with
  `boys_state`, integrating it with the existing maltzy scoring path.