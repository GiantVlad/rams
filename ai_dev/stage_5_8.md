# Stage 5.8: Event-Driven Architecture (WebSocket-Only Updates)

In this stage, the application was refactored to enforce a strict **Single Source of Truth** for game state updates.

## 1. Architectural Shift
Previously, the frontend updated its state from *both* REST API responses (immediate) and WebSocket events (broadcasts). This "mixed mode" caused complexity and potential race conditions.

**New Flow:**
1.  **Command:** Client (Human or AI Trigger) sends a command via REST API (e.g., `POST /move`).
2.  **Ack:** Server returns `200 OK { "success": true }` immediately, *without* the new game state.
3.  **Process:** Server processes the command and updates the database.
4.  **Broadcast:** Server broadcasts the new state via WebSocket (`game.update`).
5.  **Update:** Client receives the WebSocket event and updates the UI.

## 2. Backend Changes
- **Controllers:** `GameController` (move, exchange, declareJacks) and `AiController` (playTurn) were modified to return simple success messages instead of the full `Game` object.
- **Service:** `GameService` continues to broadcast updates for every state change.

## 3. Frontend Changes (`game.js`)
- **Actions:** `playCard`, `submitExchange`, `declareJacks`, and `triggerAiMove` no longer update `this.state` from the API response.
- **AI Loop:** The recursive "Trigger Next AI Move" logic was removed from the `triggerAiMove` function. Instead, the `wsClient.on('game.update')` listener automatically detects if the new active player is an AI and triggers the move, creating a robust event-driven loop.

## 4. Benefits
- **Consistency:** The UI always reflects the server's broadcasted state, eliminating "optimistic UI" desyncs.
- **Simplicity:** Frontend logic for state management is centralized in `handleStateUpdate`.
- **Scalability:** The pattern decouples command execution from state retrieval.
