# Stage 7.6: Five Same Suit Rule Implementation

This stage implements the "Five Same Suit" (Flash/Flush) rule, which triggers an immediate round win.

## 1. Rule Definition
- **Condition**: A player holds 5 cards of the same suit.
- **Timing**: Checked immediately **after dealing** and **after each exchange**.
- **Effect**:
    - The round ends immediately.
    - The player with the flush receives **-5 points** to their pile.
    - All other players receive **0 points** (no penalty).
    - A new round starts automatically (unless the game ends).

## 2. Implementation
- **Backend (`GameService.php`)**:
    - Added `checkAndApplyFiveSameSuit` helper method.
    - Updated `createRound` to invoke this check after dealing hands.
    - Updated `exchangeCardsOnce` to invoke this check after a player updates their hand.

## 3. Impact
Players can now win a round instantly during the exchange phase if they successfully collect a flush, adding a new strategic layer to the exchange process.
