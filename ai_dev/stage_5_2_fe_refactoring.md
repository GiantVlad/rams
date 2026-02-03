# Stage 5.2 – Frontend Refactor to 5-Player Rams Variant

## Context

Following the backend refactor (Stage 5.1), the frontend Vue.js app has been updated to match the new 5-player Rams variant rules:
- 5 players instead of 4
- Trump card display
- Exchange phase (replaces bidding)
- Pile and maltzy display (instead of score)
- Card discard selection UI

## What Was Done

### 1) Pinia Store (`web/src/stores/game.js`)

**State changes**
- Replaced `bidValue` with `discardCardIds` (array)
- Added `justFinishedRound` flag (already present)

**Getters added/updated**
- `exchanged`: round.exchanged array
- `fiveSameSuitDeclared`: round.five_same_suit_declared
- `partiyaDeclaredBy`: round.partiya_declared_by
- `trumpCardId`: game.trump_card_id
- Removed `bids` getter

**Actions updated**
- `newGame()`: resets `discardCardIds`
- `refresh()`: resets `discardCardIds`
- `submitBid()` → `submitExchange()`: sends `discard_card_ids` to `/api/games/{id}/exchange`
- `playCard()`: resets `discardCardIds` after play
- Added `toggleDiscard(cardId)`: toggle a card in the discard selection

### 2) Vue Component (`web/src/App.vue`)

**Template changes**
- Players panel: shows “Pile: X | Maltzy: Y” instead of score
- Added Trump panel: displays trump card or “—” if none
- Exchange panel:
  - Shows selected discard count
  - Submit Exchange button (only when human turn)
  - Waiting/complete states
- Removed Bids panel and bidding UI
- Hand buttons:
  - In exchange phase: toggle discard (red highlight)
  - In play phase: play card (same as before)
- Updated hints for exchange and play phases
- Phase checks updated from 'playing' to 'play'

**Script changes**
- Added `canDiscardCard()` helper for exchange phase
- Updated `canPlayCard()` to check phase 'play'

**CSS additions**
- `.trump` and `.trumpCard`: centered display for trump
- `.exchange`: flex column layout
- `.cardBtn.discard`: red background/border for selected discard cards

## How the New Frontend Works

### Exchange Phase
1. User sees “Select cards to discard, then submit.”
2. Clicking cards toggles red highlight (discard selection)
3. “Selected to discard: N cards” updates
4. Click “Submit Exchange” to send discard list to backend
5. After exchange, phase switches to play

### Play Phase
- Same as before: click a card to play it
- Trump card is displayed in the Trump panel
- Trick shows up to 5 cards (one per player)

### Player Info
- Each player shows “Pile: X | Maltzy: Y”
- Pile starts at 20 and decreases as tricks are won
- Maltzy count resets each round (backend logic)

## What Works Now

- Creating a game shows 5 players and trump
- Exchange phase: card selection and submission
- Play phase: card play with trump awareness
- Round transitions and game end detection
- Visual feedback for discard selection

## Known Limitations / TODOs

- **Maltzy/Partiya UI**: Not exposed; backend only tracks fields
- **Card exchange drawing**: Backend only removes discarded cards; does not yet draw new ones from deck
- **Five same suit declaration**: Not exposed in UI
- **Responsive design**: May need tweaks for 5-player layout on small screens
- **Animations**: None added for phase transitions

## How to Run (Frontend)

```bash
cd web
npm install
npm run dev
```

Ensure the backend is running (see Stage 5.1) and the API client points to it.

## Files Changed

- `web/src/stores/game.js`
- `web/src/App.vue`

## Next Steps

- Implement maltzy/partiya declaration UI (buttons/announcements)
- Implement full card exchange logic (draw from deck) in backend
- Add UI for five same suit declaration
- Update tests to cover new frontend behavior
- Update documentation (README, game_rules)
