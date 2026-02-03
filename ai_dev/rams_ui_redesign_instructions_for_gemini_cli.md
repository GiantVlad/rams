# Rams Card Game — UI Redesign Instructions (for gemini-cli)

This document is a **clear, prescriptive brief** for `gemini-cli` to redesign the Rams game UI **without changing game logic**.

The goal is to evolve the current functional UI into a **game-like, table-centered interface** while preserving all backend-driven behavior.

---

## 1. Scope & Constraints (IMPORTANT)

- ❌ Do NOT change game logic
- ❌ Do NOT change backend API contracts
- ❌ Do NOT infer or recalculate rules on frontend
- ✅ UI must remain fully state-driven
- ✅ All interactions must map 1:1 to existing backend actions

This is a **presentation-layer-only** redesign.

---

## 2. Target Platform

- Primary: **Web (desktop-first)**
- Responsive support is allowed but **not required** in first iteration

---

## 3. Core Design Goals

1. Make the interface feel like a **card game**, not an admin panel
2. Emphasize **spatial layout** over text lists
3. Make the following instantly obvious:
   - Whose turn it is
   - What phase the game is in
   - What actions are available
4. Reduce text density; replace with visual structure where possible

---

## 4. Layout Strategy

### 4.1 Overall Structure

Use a **three-column layout**:

- **Left column** — Players & scores
- **Center column** — Game table (primary focus)
- **Right column** — Phase-specific actions (exchange, info)

The center column must visually dominate.

---

## 5. Game Table Concept

The center column represents a **virtual card table**.

### Table container:
- Rounded rectangle
- Subtle felt-like or neutral dark background
- All in-play elements live inside this container

### Inside the table:
- **Top center**: Trump card (visually emphasized)
- **Center**: Current trick area
- **Bottom**: Local player hand
- **Sides / top edge**: Other players (simplified seats)

---

## 6. Card Component Requirements

Each card component must support the following visual states:

- `default`
- `hover`
- `selected` (exchange phase)
- `disabled` (not playable)

Rules:
- Only valid cards are interactive
- Disabled cards must be visually obvious (desaturated)
- Hover state should slightly lift the card

---

## 7. Trump Card Rules

- Trump card must be **larger** than normal cards
- Visually distinct (glow, border, or badge)
- Never confused with hand cards

---

## 8. Current Trick Area

- Always visible, even when empty
- Empty state should show **placeholders**, not text like "(empty)"
- When cards are played:
  - Animate card movement into the trick area
  - Highlight trick winner briefly

---

## 9. Turn Awareness

The player should never ask: "Is it my turn?"

Use at least two of:
- Highlight active player seat
- "Your turn" indicator near hand
- Subtle timer or pulse effect

---

## 10. Left Column — Players & Scores

- Show all players
- Clearly distinguish:
  - Local player
  - Active player
- De-emphasize inactive AI players

Round info:
- Tricks won
- Jacks (if applicable)

Avoid excessive text repetition.

---

## 11. Right Column — Phase Actions

This column is **state-dependent**:

### Exchange phase:
- Cards left in deck
- Cards selected to discard
- Primary action button: `Submit Exchange`

### Non-exchange phases:
- Replace controls with passive info or hide the column

---

## 12. Header Area

- Game name
- Game ID
- Phase + turn info

Move debug-like buttons (`Refresh`, `New Game`) away from gameplay focus.

---

## 13. Animations & Motion

Guidelines:
- Animations must explain game logic
- Duration: short, snappy
- No decorative or cinematic effects

Examples:
- Card slides to trick
- Score increments visually
- Trick resolution highlight

---

## 14. Implementation Guidance

- Build UI as composable components:
  - `GameTable`
  - `PlayerSeat`
  - `Card`
  - `TrickArea`
  - `ScoreBoard`
  - `PhasePanel`

- UI must react **only** to backend events
- No derived game logic on frontend

---

## 15. Deliverables Expected from gemini-cli

1. Updated layout structure
2. Card component with required states
3. Table-centered game screen
4. No changes to data models or API calls

---

## 16. Open Questions (ANSWER BEFORE IMPLEMENTATION)

Before proceeding, clarify the following:

1. **Framework**: React, Vue, or other?
2. **Styling approach**:
   - CSS modules
   - Tailwind
   - Styled-components
3. **Visual style preference**:
   - Minimal flat
   - Subtle realistic (felt, depth)
4. **Animations**:
   - CSS only
   - Animation library (which?)

Implementation should NOT start until these are answered.

