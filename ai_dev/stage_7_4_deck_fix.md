# Stage 7.4: Deck Count Fix

This stage addresses a bug where the "Cards in deck" counter in the UI was displaying incorrect values (e.g., 16) during the exchange phase because the frontend calculation failed to account for discarded cards.

## 1. Issue
The frontend attempted to calculate the remaining deck size by subtracting visible cards (hands + trump) from 36. This formula was flawed because it didn't subtract the cards that were already exchanged (discarded).

## 2. Fix
- **Backend (`GameService.php`)**: Updated `getState` to include an authoritative `remaining_deck_count` field in the `round` object, derived directly from the `remaining_deck` array in the database.
- **Frontend (`PhasePanel.vue`)**: Updated the `cardsLeft` computed property to prioritize this new backend field.

## 3. Result
The UI now correctly displays the actual number of cards remaining in the deck, decreasing as players exchange cards.
