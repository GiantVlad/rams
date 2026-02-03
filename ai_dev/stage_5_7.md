# Stage 5.7: AI Strategy Refinement & Non-Blocking Game Loop

In this stage, the AI's decision-making logic was significantly enhanced, and the game loop was refactored to release server resources immediately.

## 1. AI Strategy Improvements

The `AiService` was overhauled to move away from "random/lowest" card play to a more strategic approach.

### Exchange Phase
- **Asset Protection:** AI now recognizes high-value cards. It will never discard **Aces** or **Trump** cards.
- **Selective Discard:** It prioritizes discarding the lowest-ranking non-trump cards first.

### Play Phase
- **Winning Strategy:** The AI now calculates the current winning card in the trick. It attempts to win the trick by playing the **lowest possible winning card** (saving high cards for later).
- **Damage Control:** If it cannot win the trick, it plays its **lowest losing card** to minimize waste.
- **Rule Adherence:** Strictly follows "Must follow suit" and "Must trump if void" rules, ensuring no invalid moves are attempted.

## 2. Non-Blocking Game Loop Refactoring

To prevent PHP processes from hanging and improve system scalability, the game's "thinking" logic was moved to the frontend.

### Backend Changes (`GameService.php`)
- **Removed `sleep()` calls:** Deleted all `sleep()` and blocking `while` loops that previously simulated thinking time.
- **Atomic Operations:** Each API call now performs exactly one action (one human move or one AI move) and returns immediately.
- **Broadcasting:** Retained WebSocket broadcasting to ensure the UI stays in sync across multiple actions.

### Frontend Changes (`web/src/stores/game.js`)
- **Client-Side Orchestration:** The Pinia store now manages the "thinking" delay (1.5 seconds) using `setTimeout`.
- **Recursive AI Triggering:** If the next player in the sequence is an AI, the frontend automatically triggers the next move after the delay.
- **Concurrency Protection:** Added a `processingAiMove` flag to prevent multiple overlapping AI move requests.

## 3. Verification
- **Unit Testing:** Created `tests/Unit/AiServiceTest.php` with 6 test cases covering discard logic, trick winning, and trumping rules. All tests passed.
- **Performance:** Server response time for moves is now near-instant, with the "wait time" handled asynchronously by the browser.
