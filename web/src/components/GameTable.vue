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
}

.felt-surface {
  width: 100%;
  height: 600px; /* Fixed height for table feel? Or flex? */
  background-color: #2c5e2e; /* Classic felt green */
  background-image: radial-gradient(circle at center, #357a38 0%, #2c5e2e 100%);
  border-radius: 40px;
  box-shadow: inset 0 0 50px rgba(0,0,0,0.5), 0 10px 30px rgba(0,0,0,0.3);
  position: relative;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  border: 12px solid #5d4037; /* Wood rim */
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
  color: rgba(255,255,255,0.6);
  font-size: 12px;
  font-weight: bold;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.trump-card {
  transform: scale(1.1) rotate(-5deg);
  box-shadow: 0 5px 15px rgba(0,0,0,0.4);
}

.trump-placeholder {
  width: 80px;
  height: 120px;
  border: 2px dashed rgba(255,255,255,0.2);
  border-radius: 8px;
}

.seat-pos {
  position: absolute;
  z-index: 10;
}

.seat-pos.top { top: 20px; left: 50%; transform: translateX(-50%); }
.seat-pos.left { left: 20px; top: 50%; transform: translateY(-50%); }
.seat-pos.right { right: 20px; top: 50%; transform: translateY(-50%); }

.center-area {
  z-index: 1;
  pointer-events: none; /* Let clicks pass through empty areas if needed */
}

.bottom-area {
  position: absolute;
  bottom: 30px;
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  z-index: 20;
}

.my-seat-info {
  /* Position absolute to the left of hand or integrated? */
  position: absolute;
  left: 40px;
  bottom: 0;
}

.hand-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
}

.hand-label {
  background: rgba(0,0,0,0.5);
  padding: 4px 12px;
  border-radius: 12px;
  color: white;
  font-size: 14px;
  backdrop-filter: blur(4px);
}

.turn-alert {
  color: #fbbf24; /* Amber */
  font-weight: bold;
  animation: pulse 1.5s infinite;
}

.hand-cards {
  display: flex;
  gap: 10px; /* Slight overlap handled by Card css? No, gap is better for hover */
  padding: 10px;
}

@keyframes pulse {
  0% { opacity: 1; }
  50% { opacity: 0.5; }
  100% { opacity: 1; }
}
</style>
