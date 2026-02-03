# Stage 7.2: UI Player Personalization

In this stage, the generic player identifiers (P0, P1, etc.) were replaced with personalized names to improve the user experience and immersion.

## 1. Name Mapping
A new getter `getPlayerName` was added to the Pinia store to centralize the naming convention:
- **Seat 0**: "You"
- **Seat 1**: "Mike" (AI)
- **Seat 2**: "William" (AI)
- **Seat 3**: "Sarah" (AI)

## 2. UI Implementation
The following components were updated to utilize the new naming system:
- **`App.vue`**: Updated the status bar (Game Turn info) and the Round Finished notification.
- **`PlayerSeat.vue`**: Replaced generic labels with names and updated avatars to show names' initials.
- **`ScoreBoard.vue`**: Updated the player list to show personalized names.
- **`TrickArea.vue`**: Updated spatial labels in the central trick area.
- **`PhasePanel.vue`**: Updated the Game Winner display.

## 3. Scope
- **UI-Only**: These changes are purely cosmetic and reside in the frontend.
- **Backend Consistency**: The backend continues to use `player_index` (0-3) for all logic and API communication, ensuring no breaks in system integrity.
