# Stage 7.7: "Pass Round" Feature

This stage implements the ability for players to "Fold" or "Pass" after the exchange phase, opting out of the trick-taking round to avoid penalties.

## 1. Game Flow Update
- **New Phase**: `choose_to_play` (Participation Phase) inserted between `exchange` and `play`.
- **Sequence**: After the dealer finishes exchanging, the game enters `choose_to_play`.
- **Action**: Starting from the player left of dealer, each player decides to **Play** or **Pass**.

## 2. Rules & Scoring
- **Pass (Fold)**:
    - The player does not participate in tricks.
    - Their pile score remains unchanged (no +5 penalty for taking 0 tricks).
    - If all players pass (or only 1 remains), the round ends immediately.
- **Play**:
    - The player participates in tricks.
    - Standard scoring applies (potential for winning tricks or penalty for 0 tricks).

## 3. Implementation
- **Database**: Added `passed_players` JSON column to `rounds` table.
- **Backend**:
    - `GameService::decideParticipation`: Handles the logic, updates state, and manages transitions.
    - `Scoring`: Updated to exempt passed players from the +5 penalty.
    - `GameService::playCard`: Updated to skip passed players in turn rotation.
- **AI**: Added logic to choose: plays if hand contains at least one Trump or Ace; otherwise passes.
- **UI**:
    - **PhasePanel**: Shows "Play" and "Pass" buttons during the new phase.
    - **PlayerSeat**: Displays a visual "PASS" badge for players who have folded.
