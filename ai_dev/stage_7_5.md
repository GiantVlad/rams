# Stage 7.5: UI Polish & Rule Consistency

This stage focuses on refining the visual presentation of game elements and ensuring a consistent user experience during round transitions.

## 1. Visual Refinements
- **Card Design**:
    - **Enlarged Symbols**: Rank and suit icons were significantly enlarged for better readability.
    - **Corner Alignment**: Icons are now strictly positioned in the top-left and bottom-right corners using absolute positioning, mimicking a realistic card layout.
    - **Central Background**: The central suit watermark was enlarged to 48px.
- **Player Names**:
    - Increased font size and weight for player names in `PlayerSeat.vue` and `TrickArea.vue`.
    - Improved contrast for player labels in the central trick area.

## 2. Rule Adjustments (Tie Breaking)
- **Round Winner**: Updated the logic to handle ties in trick counts. If multiple players have the same maximum number of tricks, the player with the lowest seat index (first in the list) is now declared the winner. This ensures the "Round Finished" message always displays a clear winner.

## 3. Bug Fixes
- **AI Deadlock**: Fixed an issue where an AI player winning a trick would get "stuck" because the processing flag was not reset. The flag is now unconditionally reset upon receiving a new game state from the server.
