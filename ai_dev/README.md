# Rams Card Game – Web Application

## Overview
This project is a web-based implementation of the **Rams** card game (classic rules), built as a client–server application.

- **Frontend**: Vue 3 + Pinia
- **Backend**: Laravel (REST API)
- **Game Mode (Stage 1)**: Single-player
    - 1 Human user
    - 3 Computer-controlled players (AI)

At the initial stage, the focus is on **game logic correctness and usability**, not on animations or visual effects.

---

## Goals of Stage 1

1. Implement full classic Rams rules
2. Enable a single human player to play against 3 AI players
3. Keep UI minimal and functional
4. Ensure backend-driven game logic for fairness and extensibility
5. Prepare architecture for future multiplayer support

---

## Game Rules (Classic – Summary)

> Full rules reference: https://cards-tysyacha.my1.ru/publ/pravila_igr/rams/2-1-0-35

### Players
- 4 players total
- Standard 36-card deck (6 to Ace)

### Objective
- Avoid collecting penalty points
- The player with the **lowest score** wins after the game ends

### General Flow
1. Shuffle and deal cards
2. Bidding phase
3. Playing tricks
4. Score calculation
5. Next round
6. Game ends when a player reaches the losing threshold

---

## Functional Requirements

### User
- Can start a new game
- Can see their hand
- Can make legal moves only
- Can see played cards and current trick
- Can see scores after each round

### Computer Players (AI)
- Must follow game rules strictly
- Make decisions automatically
- Use basic deterministic or heuristic logic (no ML in stage 1)

---

## System Architecture

```
[ Vue 3 + Pinia ]  <-->  [ Laravel API ]  <-->  [ Database ]
```

### Frontend Responsibilities
- Render game state
- Send user actions to API
- Display validation errors
- Maintain local UI state

### Backend Responsibilities
- Game state management
- Rule validation
- Turn control
- AI decision logic
- Score calculation

---

## Backend (Laravel API)

### Core Concepts

#### Entities
- **Game**
- **Player**
- **Round**
- **Trick**
- **Card**
- **Move**

#### Suggested Models

- `Game`
    - id
    - status (waiting / in_progress / finished)
    - current_player_id
    - round_number

- `Player`
    - id
    - game_id
    - type (human | ai)
    - seat_position (0–3)
    - score

- `Card`
    - suit
    - rank

- `Move`
    - player_id
    - card
    - created_at

---

### API Endpoints (Draft)

#### Game
- `POST /api/games` – create new game
- `GET /api/games/{id}` – get full game state

#### Gameplay
- `POST /api/games/{id}/move` – play a card
- `POST /api/games/{id}/bid` – place a bid

---

## Frontend (Vue 3 + Pinia)

### State Management (Pinia)

**Stores:**
- `useGameStore`
    - gameId
    - players
    - hand
    - currentTrick
    - scores
    - currentTurn

### Views

- Game Start Screen
- Game Table View
- Round Summary Modal (text-only)

---

## UI Principles (Stage 1)

- No animations
- No transitions
- Simple layout
- Text-based indicators
- Minimal colors

### Example Layout

```
+----------------------------------+
| Scores                           |
| You: 10 | AI1: 20 | AI2: 5 | AI3: 15 |
+----------------------------------+
| AI2 played: 9♠                   |
| AI3 played: K♠                   |
| AI1 played: A♠                   |
+----------------------------------+
| Your hand:                       |
| [6♠] [8♠] [Q♥] [10♦]             |
+----------------------------------+
```

---

## AI Logic (Stage 1)

- Always play a valid card
- Follow suit if possible
- Minimal strategy:
    - Play lowest valid card
    - Avoid taking tricks when possible

---

## Validation Rules

- User cannot play out of turn
- User must follow suit
- Invalid moves rejected by API
- Frontend mirrors backend validation

---

## Error Handling

- API returns structured errors
- UI displays error messages inline

---

## Testing Strategy

### Backend
- Unit tests for game rules
- Feature tests for API endpoints

### Frontend
- Manual testing
- Console-based state inspection

---

## Future Enhancements (Out of Scope for Stage 1)

- Multiplayer support
- Real-time gameplay (WebSockets)
- Animations and effects
- Sound
- Advanced AI
- Mobile-first UI

---

## Development Milestones

1. Game rules implementation
2. Game state API
3. Basic AI
4. Minimal UI
5. End-to-end playable game

---

## Definition of Done (Stage 1)

- User can complete a full game
- All rules enforced by backend
- No crashes or undefined states
- Clean API responses

---

*This document serves as the foundational specification for development.*

