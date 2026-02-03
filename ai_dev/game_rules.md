# Rams Card Game – Implementation-Friendly Rules

This document translates the Rams variant rules (36-card, 5-player) into a structured format suitable for **backend implementation**.

---

## 1. Game Setup

- **Deck:** 36 cards (6-Ace, 4 suits)
- **Players:** 4
- **Initial pile:** Each player has 20 points
- **Trump:** One card revealed by dealer
- **Goal:** Reduce pile from 20 to 0 points

---

## 2. Dealing & Card Exchange

### 2.1 Initial Deal
- Dealer deals **5 cards** to each player
- Dealer reveals **trump card**

### 2.2 Card Exchange Rules
- Players act in turn (left of dealer) to exchange cards with dealer:
    - Discard **1–5 cards** → receive same number from dealer
    - Can **refuse exchange** → keep original hand
    - Can **pass completely** → discard all cards and skip round (not dealer)
- Dealer exchanges **last**
    - After exchange, dealer may discard **1 card** to take the revealed trump
- If deck runs out, cannot discard more cards than remain

---

## 3. Special Case: Five Cards of the Same Suit

- If a player has **5 cards of the same suit** after deal:
    - Must announce **before first move**
    - Reduces pile by **5 points**
    - Round ends immediately, new round starts
- Priority if multiple players: **player closest left of dealer**
- If all 5 cards are **trump**:
    - Player reduces pile by **5 points**
    - All other players receive **+5 penalty points**

---

## 4. Trick Play

- Player **left of dealer** leads the first card
- Other players must **follow suit** if possible
- If cannot follow suit:
    - May play **trump** or **any card** (trumping is optional)
- Winner of trick:
    - **Highest card of suit led** or **highest trump**
- Winner leads next trick
- **Number of tricks per round:** 5

---

## 5. Scoring

- After 5 tricks, update pile:
    - **Reduce pile** by number of tricks won
    - If player played but won **no tricks** → **+5 penalty points**

---

## 6. Jacks

- **Definition:** Two Jacks of the same color = Jacks
- The game must **indicate** when played: “first Jack” / “second Jack”
- After round:
    - **5 points deducted** from pile per Jacks
- Jacks offsets penalty for taking no tricks:
    - Player initially penalized **+5 points** → Jacks reduces by 5 → pile unchanged

---

## 7. Endgame – Partiya Declaration

- If player has **≤5 points** remaining:
    - Must track tricks carefully
    - When winning **exact number of tricks to reduce pile to 0**, must announce **“partiya”**
    - Example: 3 points remaining → after 3rd trick, announce “partiya”
    - **Failing to announce** → **+5 penalty points**

---

## 8. Data Structures (Backend-Friendly)

### Player
```json
{
  "id": "UUID",
  "name": "string",
  "pile": 20,
  "hand": ["cards"],
  "maltzy": 0,
  "tricks_won": 0
}
```

### Game
```json
{
  "deck": ["cards"],
  "players": ["Player"],
  "dealer_index": 0,
  "trump_card": "card",
  "round_number": 1,
  "current_trick": ["cards"],
  "phase": "deal|exchange|play|scoring"
}
```

### Trick
```json
{
  "cards_played": [{"player_id": "UUID", "card": "string"}],
  "winner_id": "UUID"
}
```

### Round Logic
- Check **five cards same suit** before first move
- Allow **card exchanges** according to rules
- Track **tricks won** per player
- Apply **Maltzy scoring**
- Apply **penalties for zero tricks**
- Handle **partiya declaration**

---

## 9. Rules Enforcement

- Backend is **authoritative**
- Illegal moves:
    - Playing out of turn
    - Playing card not in hand
    - Failing to follow suit
    - Incorrect exchanges
    - Not declaring Partiya when required
- All must be **validated server-side**

---

*This structured format is ready to be used as the basis for a Laravel backend implementation.*