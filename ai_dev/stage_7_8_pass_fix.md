# Stage 7.8: Pass Round Logic Fixes

This stage addresses critical bugs introduced by the "Pass Round" feature, where the game would stall during the play phase if any player had folded.

## 1. Issue
- **Trick Resolution**: The game logic hardcoded a requirement for 4 cards to be played before resolving a trick.
- **Result**: If a player passed, the trick would only ever reach 3 cards (for example), preventing the turn from advancing or the trick from closing.
- **Rule Validation**: `TrickRules` explicitly threw an exception if a trick didn't have exactly 4 cards.

## 2. Fixes
- **Dynamic Trick Size**: Updated `GameService::playCardOnce` to calculate the required trick size dynamically (`4 - count(passed_players)`).
- **Flexible Rules**: Updated `TrickRules::winnerPlayerIndex` to accept variable trick sizes (min 1).

## 3. Impact
The game now correctly handles rounds with fewer than 4 active players, allowing tricks to complete and the game to progress naturally even when players have folded.
