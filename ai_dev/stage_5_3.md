# Stage 5.2.3 – Jacks (formerly Maltzy) Feature Implementation

## Context

Following the game rules update (section 6: Jacks), the “Maltzy” feature has been renamed to “Jacks” and fully implemented. This includes:
- UI detection when a player holds two Jacks of the same suit
- Declaration mechanism to reduce pile to 5
- Backend API, controller, and service support
- Consistent naming throughout the codebase

## What Was Done

### 1) Frontend Store (`web/src/stores/game.js`)

**Renamed**
- `humanHasMaltzy` → `humanHasJacks`
- `declareMaltzy()` → `declareJacks()`
- API endpoint: `/declare-maltzy` → `/declare-jacks`

**Logic**
- Detects two Jacks of same suit in hand
- Returns suit name if found (e.g., 'H', 'D', 'C', 'S')

### 2) Vue Component (`web/src/App.vue`)

**Template updates**
- `maltzyPanel` → `jacksPanel`
- `maltzyInfo` → `jacksInfo`
- Button text: “Declare Maltzy” → “Declare Jacks (pile → 5)”
- Panel title: “Maltzy Available!” → “Jacks Available!”

**CSS updates**
- `.maltzyPanel` → `.jacksPanel`
- `.maltzyInfo` → `.jacksInfo`
- Same visual styling (red/yellow gradient)

**Behavior**
- Shows panel only during play phase when it’s human’s turn
- Displays suit of the two Jacks
- Button triggers declaration

### 3) Backend API

**Routes (`api/routes/api.php`)**
- `POST /games/{game}/declare-maltzy` → `POST /games/{game}/declare-jacks`

**Controller (`app/Http/Controllers/GameController.php`)**
- `declareMaltzy()` → `declareJacks()`
- Same validation and error handling
- Calls service method

**Service (`app/Services/Rams/GameService.php`)**
- `declareMaltzy()` → `declareJacks()`
- Reduces player’s pile to 5
- Increments `maltzy_count` (column retained for tracking)
- Validates game state and phase

### 4) Database

**No schema changes**
- `maltzy_count` column retained but now tracks Jacks declarations
- Existing data remains compatible

## Rules Compliance

According to `game_rules.md` section 6:

- ✅ **Definition**: Two Jacks of the same color = Jacks
- ✅ **Game indicates**: UI shows “Jacks Available!” panel
- ⚠️ **“first Jack” / “second Jack”**: Not yet implemented during play
- ✅ **After round**: 5 points deducted from pile (implemented as pile → 5)
- ✅ **Offsets penalty**: Reduces pile to 5, countering +5 penalty for zero tricks

## How It Works

1. **Detection**: When human’s hand contains two Jacks of same suit, `humanHasJacks` returns the suit
2. **UI**: Red/yellow panel appears with “Declare Jacks (pile → 5)” button
3. **Declaration**: Clicking button calls `/declare-jacks` endpoint
4. **Backend**: Reduces player’s pile to 5, increments `maltzy_count`
5. **Effect**: Player’s pile is now 5 instead of previous value

## Current Limitations

- **“first Jack” / “second Jack” indicators**: Not shown during play
- **AI declaration**: AI does not yet declare Jacks automatically
- **Multiple declarations**: No restriction on declaring multiple times per round (should be once)

## Files Changed

- `web/src/stores/game.js`
- `web/src/App.vue`
- `api/routes/api.php`
- `api/app/Http/Controllers/GameController.php`
- `api/app/Services/Rams/GameService.php`

## Next Steps

- Implement “first Jack” / “second Jack” indicators during play
- Add AI logic to declare Jacks when detected
- Add validation to prevent multiple declarations per round
- Update documentation to reflect Jacks terminology
