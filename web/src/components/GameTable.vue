<script setup>
import { computed } from 'vue'
import Card from './Card.vue'
import TrickArea from './TrickArea.vue'
import PlayerSeat from './PlayerSeat.vue'
import { useGameStore } from '../stores/game'

const game = useGameStore()

// Positions
// P0: Bottom (Local)
// P1: Left
// P2: Top
// P3: Right

const playerBottom = computed(() => game.players[0])
const playerLeft = computed(() => game.players[1])
const playerTop = computed(() => game.players[2])
const playerRight = computed(() => game.players[3])

function isCardSelected(id) {
  return game.discardCardIds.includes(id)
}

function canPlay(id) {
  if (!game.isHumanTurn) return false
  if (game.phase !== 'play') return false
  
  // Logic from App.vue
  const leadingSuit = getLeadingSuit()
  if (!leadingSuit) return true
  
  const hand = game.hand || []
  const hasSuit = hand.some(cid => cid.startsWith(leadingSuit))
  
  if (!hasSuit) return true // Can play any card if void
  return id.startsWith(leadingSuit)
}

function getLeadingSuit() {
  const first = game.currentTrick?.[0]
  if (!first || !first.card) return null
  return String(first.card).split('-')[0]
}

function onCardClick(id) {
  if (game.phase === 'exchange') {
    game.toggleDiscard(id)
  } else if (game.phase === 'play') {
    if (canPlay(id)) {
      game.playCard(id)
    }
  }
}
</script>

<template>
  <div class="game-table-container">
    <div class="felt-surface">
      <!-- Trump Card Area (Top Leftish or near Dealer) -->
      <div class="trump-zone">
        <div class="zone-label">Trump</div>
        <Card 
          v-if="game.trumpCardId" 
          :cardId="game.trumpCardId" 
          class="trump-card"
        />
        <div v-else class="trump-placeholder"></div>
      </div>

      <!-- Player Seats (Top, Left, Right) -->
      <div v-if="playerTop" class="seat-pos top">
        <PlayerSeat 
          :player="playerTop" 
          :isActive="game.currentPlayerIndex === 2"
          :isDealer="game.state.round.dealer_index === 2"
          position="top" 
        />
      </div>
      
      <div v-if="playerLeft" class="seat-pos left">
        <PlayerSeat 
          :player="playerLeft" 
          :isActive="game.currentPlayerIndex === 1"
          :isDealer="game.state.round.dealer_index === 1"
          position="left" 
        />
      </div>

      <div v-if="playerRight" class="seat-pos right">
        <PlayerSeat 
          :player="playerRight" 
          :isActive="game.currentPlayerIndex === 3"
          :isDealer="game.state.round.dealer_index === 3"
          position="right" 
        />
      </div>

      <!-- Center Trick Area -->
      <div class="center-area">
        <TrickArea 
          :trick="game.currentTrick" 
          :winnerIndex="game.roundWinner" 
        />
      </div>

      <!-- Bottom Area: My Hand -->
      <div class="bottom-area">
        <div class="my-seat-info">
          <PlayerSeat 
            v-if="playerBottom"
            :player="playerBottom" 
            :isActive="game.currentPlayerIndex === 0"
            :isLocal="true"
            :isDealer="game.state.round.dealer_index === 0"
            position="bottom" 
          />
        </div>
        
        <div class="hand-container">
          <div class="hand-label">
            <span v-if="game.isHumanTurn" class="turn-alert">YOUR TURN</span>
            <span v-else>Waiting...</span>
          </div>
          
          <div class="hand-cards">
            <Card 
              v-for="id in game.hand" 
              :key="id"
              :cardId="id"
              :selected="isCardSelected(id)"
              :disabled="game.phase === 'play' && !canPlay(id)"
              :interactive="true"
              @click="onCardClick(id)"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.game-table-container {
  width: 100%;
  height: 100%;
  padding: 20px;
  box-sizing: border-box;
  display: flex;
  align-items: center;
  justify-content: center;
}

.felt-surface {
  width: 100%;
  max-width: 800px;
  height: 600px;
  background-color: #2e5c33; /* Darker base felt */
  background-image: url('/images/felt_pattern.png'), radial-gradient(circle at center, rgba(76, 175, 80, 0.4) 0%, rgba(27, 94, 32, 0.9) 100%);
  background-blend-mode: overlay, normal;
  border-radius: 60px; /* More rounded like a real table */
  box-shadow: inset 0 0 50px rgba(0,0,0,0.6), 0 20px 40px rgba(0,0,0,0.4);
  position: relative;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  border: 16px solid #4e342e; /* Richer Wood rim */
  border-top-color: #5d4037;
  border-bottom-color: #3e2723;
}

.trump-zone {
  position: absolute;
  top: 40px;
  left: 40px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  z-index: 5;
}

.zone-label {
  color: rgba(255,255,255,0.8);
  font-size: 13px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 2px;
  text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
}

.trump-card {
  transform: scale(1.1) rotate(-8deg);
  box-shadow: -2px 5px 15px rgba(0,0,0,0.5);
  transition: transform 0.3s;
}

.trump-card:hover {
  transform: scale(1.15) rotate(-5deg) translateY(-5px);
  z-index: 10;
}

.trump-placeholder {
  width: 80px;
  height: 120px;
  border: 2px dashed rgba(255,255,255,0.3);
  border-radius: 8px;
  background: rgba(0,0,0,0.1);
}

.seat-pos {
  position: absolute;
  z-index: 10;
}

.seat-pos.top { top: 30px; left: 50%; transform: translateX(-50%); }
.seat-pos.left { left: 30px; top: 50%; transform: translateY(-50%); }
.seat-pos.right { right: 30px; top: 50%; transform: translateY(-50%); }

.center-area {
  z-index: 1;
  pointer-events: none;
}

.bottom-area {
  position: absolute;
  bottom: 0;
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  z-index: 20;
}

.my-seat-info {
  position: absolute;
  left: 40px;
  bottom: 40px; /* Keep it away from hands */
}

.hand-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 15px;
  transform: translateY(20px); /* Push cards slightly down out of table */
}

.hand-label {
  background: rgba(17, 24, 39, 0.8);
  padding: 6px 16px;
  border-radius: 20px;
  color: white;
  font-size: 14px;
  font-weight: 600;
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255,255,255,0.1);
  box-shadow: 0 4px 6px rgba(0,0,0,0.2);
}

.turn-alert {
  color: #fbbf24;
  font-weight: 800;
  letter-spacing: 1px;
  animation: pulse 1.5s infinite;
}

.hand-cards {
  display: flex;
  gap: -20px; /* Negative margin for realistic fan overlap */
  padding: 10px;
  transition: gap 0.3s;
}

.hand-cards:hover {
  gap: 5px; /* Spread cards out on hover container */
}

@keyframes pulse {
  0% { opacity: 1; text-shadow: 0 0 5px rgba(251, 191, 36, 0.5); }
  50% { opacity: 0.6; text-shadow: none; }
  100% { opacity: 1; text-shadow: 0 0 5px rgba(251, 191, 36, 0.5); }
}
</style>
