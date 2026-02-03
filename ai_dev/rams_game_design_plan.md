# Rams Card Game — Design Plan

This document outlines a **structured plan** for designing the Rams card game UI/UX.

---

## Phase 1 — Define the Design Scope

**Goal:** avoid redesign chaos.

### 1. Platforms
- Web (desktop first)
- Mobile later / responsive
- Native apps (future)

### 2. Game Screens Inventory
- Lobby / waiting for players
- Game table (main screen)
- Round summary
- Game results
- Reconnect / error screens

### 3. Game State → UI Mapping
| Backend State | UI Behavior |
|---------------|-------------|
| waiting_for_players | Table visible, seats empty, “Invite” |
| in_progress | Cards visible, only current player active |
| round_finished | Cards frozen, score animation |
| game_finished | Table dimmed, results modal |

---

## Phase 2 — Visual Language

### 4. Mood & Theme
- Classic card table, minimal modern, or semi-realistic
- Recommendation: Modern card table — clean UI, subtle depth

### 5. Color System
- Background (table / room)
- Cards (white / neutral)
- Primary action (play card)
- Secondary action (pass / info)
- Danger (timeouts, errors)

### 6. Typography
- One font for UI
- One for card values (optional)
- Readable numbers, avoid decorative fonts on cards

---

## Phase 3 — Table Layout

### 7. Core Table Zones
- Center: current trick
- Bottom: your hand
- Sides/top: other players
- Corner: scores / round info
- Edge: system messages / turn indicator

### 8. Card Interaction Rules
- Click to play
- Hover highlight
- Disabled cards greyed out
- Animations: fast, no fluff
- Only valid cards clickable

---

## Phase 4 — UX Details

### 9. Turn Awareness
- Highlight active player
- Subtle timer ring
- “Your turn” micro-text

### 10. Feedback & Animations
- Card slides to center
- Winning trick highlighted
- Score increments visually
- No long cinematic animations

---

## Phase 5 — Build in Layers

### 11. Low-Fidelity First
- Wireframes, grey boxes, no colors
- Validate layout and action visibility

### 12. High-Fidelity After Logic Match
- Colors, shadows, animations, micro-interactions

---

## Phase 6 — Implementation Strategy

### 13. Component Breakdown
- `<GameTable>`
- `<PlayerSeat>`
- `<Card>`
- `<TrickPile>`
- `<ScoreBoard>`
- `<GameLog>`

### 14. Design ↔ Backend Contract
- Map UI elements to backend events
- Hide elements when state dictates
- Actions trigger API calls

---

## Suggested Next Steps
1. Design **Game Table screen**
2. Define **visual style references / mood board**
3. Create **wireframe layout**
4. Map backend events → UI reactions

