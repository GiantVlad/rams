# Rams Card Game – Development Stages

This document describes a **possible staged roadmap** for developing the Rams card game web application. Each stage is designed to deliver a playable and testable result while keeping complexity under control.

---

## Stage 0 – Preparation & Analysis

### Goals
- Ensure clear understanding of rules and scope
- Prepare technical foundation

### Tasks
- Analyze classic Rams rules in detail
- Identify edge cases (bidding, penalties, ties)
- Define losing condition (score threshold)
- Decide backend-first rule authority
- Create initial documentation

### Deliverables
- `README.md`
- `GAME_RULES.md`
- Development stages document

---

## Stage 1 – Core Game Logic (Backend Only)

### Goals
- Implement deterministic, testable game logic
- No UI or API exposure yet

### Tasks
- Create card deck logic (36 cards)
- Implement shuffle and deal
- Implement trick logic
- Implement rule validation
- Implement score calculation

### Deliverables
- Laravel domain services
- Unit tests for rules

---

## Stage 2 – Game State & API

### Goals
- Expose game logic via REST API
- Enable full game flow via HTTP

### Tasks
- Create `Game`, `Player`, `Round` models
- Persist game state
- Implement turn management
- Implement API endpoints
- Implement error handling

### Deliverables
- REST API
- Feature tests
- API documentation

---

## Stage 3 – Basic AI Players

### Goals
- Allow game to progress without human input
- Ensure rule-compliant AI behavior

### Tasks
- Implement AI move selection
- Follow suit logic
- Minimal bidding strategy
- Avoid illegal moves

### Deliverables
- AI service
- AI test scenarios

---

## Stage 4 – Minimal Frontend UI

### Goals
- Make the game playable by a human user
- Focus on clarity over visuals

### Tasks
- Setup Vue 3 project
- Setup Pinia store
- Create game table layout
- Render cards as text or simple blocks
- Handle user input

### Deliverables
- Playable UI
- Pinia stores
- API integration

---

## Stage 5 – Full Game Loop

### Goals
- Allow full games from start to finish
- Ensure state consistency

### Tasks
- Implement round transitions
- Show round results
- Detect game end
- Restart game

### Deliverables
- End-to-end playable game
- Stable game loop

---

## Stage 6 – Validation & Polishing

### Goals
- Improve stability and correctness
- Handle edge cases gracefully

### Tasks
- Improve backend validations
- Add frontend error feedback
- Handle refresh / reconnect
- Improve logs

### Deliverables
- Hardened application
- Reduced bugs

---

## Stage 7 – Refactoring & Cleanup

### Goals
- Prepare codebase for future extensions

### Tasks
- Refactor domain logic
- Improve naming and structure
- Remove duplication
- Improve documentation

### Deliverables
- Clean architecture
- Updated docs

---

## Optional Future Stages

### Multiplayer Support
- WebSocket integration
- Lobby system
- Player matchmaking

### Advanced AI
- Strategy-based decision making
- Risk evaluation

### UI Enhancements
- Animations
- Card graphics
- Mobile layout

---

## Suggested Development Order

1. Stage 0
2. Stage 1
3. Stage 2
4. Stage 3
5. Stage 4
6. Stage 5
7. Stage 6
8. Stage 7

---

## Success Criteria

- Every stage results in a usable artifact
- Backend remains the source of truth
- Game rules are never duplicated incorrectly

---

*This roadmap is flexible and can be adapted based on feedback or scope changes.*

