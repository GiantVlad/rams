# Stage 4 – Minimal Frontend UI (Result)

## Goals (from `ai_dev/dev_stages.md`)

- Make the game playable by a human user
- Focus on clarity over visuals

## What was implemented

### 1) Vue 3 SPA (Vite)

Folder:

- `web/`

Notes:

- This is a standalone SPA intended to be the future frontend.

### 2) Pinia store

Added:

- `web/src/stores/game.js`

Responsibilities:

- Hold current backend state (`state`)
- Call backend API endpoints:
  - `POST /api/games` (create new game)
  - `GET /api/games/{id}` (refresh)
  - `POST /api/games/{id}/bid`
  - `POST /api/games/{id}/move`
- Store and display backend validation errors

### 3) API client helper

Added:

- `web/src/api/client.js`

Responsibilities:

- Wrapper around `fetch()` that:
  - parses JSON
  - throws readable errors on non-2xx responses

### 4) Dev proxy to backend API

Updated:

- `web/vite.config.js`

Dev server proxy:

- `/api/*` is proxied to `http://127.0.0.1:8000`

This avoids CORS issues during development.

### 5) Minimal game UI

Updated:

- `web/src/App.vue`

UI capabilities:

- Start a new game
- Show basic state:
  - game id, phase, current turn
  - scores
  - bids
  - current trick
  - human hand (seat 0)
- Bid input (only enabled when it is the human’s turn)
- Play cards (buttons)
  - Client-side “follow suit” hint/disable:
    - if leading suit exists and player has it, non-leading-suit cards are disabled
  - Backend remains the authority and will still validate the move

### 6) Small global style adjustment

Updated:

- `web/src/style.css`

Reason:

- The default Vite template centers the entire app (`body { display:flex }`), which is not suitable for a full-page game view.

## How to run (development)

### Backend

In one terminal:

- `php artisan serve`

Run in:

- `api/`

This starts Laravel at `http://127.0.0.1:8000`.

### Frontend

In another terminal:

- `npm run dev`

Run in:

- `web/`

Open:

- `http://localhost:5173`

The frontend calls the backend through the Vite proxy.

## Notes / Refactors

- No backend refactor was required for Stage 4 (Stage 2/3 API shape was sufficient).
- The UI is intentionally minimal and text-based.

## Deliverables status

- **Playable UI**: done (`web/`)
- **Pinia stores**: done (`web/src/stores/game.js`)
- **API integration**: done (proxy + store actions)
