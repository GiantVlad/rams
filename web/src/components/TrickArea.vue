<script setup>
import Card from './Card.vue'
import { useGameStore } from '../stores/game'

const game = useGameStore()

const props = defineProps({
  trick: {
    type: Array, // [{ player: int, card: string }]
    default: () => []
  },
  winnerIndex: {
    type: Number,
    default: null
  }
})

function getCardForPlayer(playerIndex) {
  return props.trick.find(p => p.player === playerIndex)?.card
}

// Map player index to position class
function getPositionClass(playerIndex) {
  // Assuming P0 is always bottom (Human)
  // P1: Left, P2: Top, P3: Right
  switch (playerIndex) {
    case 0: return 'pos-bottom'
    case 1: return 'pos-left'
    case 2: return 'pos-top'
    case 3: return 'pos-right'
    default: return ''
  }
}
</script>

<template>
  <div class="trick-area">
    <div class="trick-center">
      <!-- 4 slots for 4 players -->
      <div 
        v-for="i in 4" 
        :key="i-1" 
        class="card-slot" 
        :class="[getPositionClass(i-1), { winner: winnerIndex === i-1 }]"
      >
        <div class="slot-label">{{ game.getPlayerName(i-1) }}</div>
        <Card 
          v-if="getCardForPlayer(i-1)" 
          :cardId="getCardForPlayer(i-1)" 
          class="played-card"
        />
        <div v-else class="empty-slot"></div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.trick-area {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

.trick-center {
  width: 280px;
  height: 280px;
  position: relative;
  border-radius: 50%;
  background: rgba(0,0,0,0.1); /* Subtle dark circle in the middle */
  box-shadow: inset 0 0 20px rgba(0,0,0,0.2);
}

.card-slot {
  position: absolute;
  width: 80px;
  height: 120px;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

/* Positions relative to center */
.pos-bottom {
  bottom: 10px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 4;
}

.pos-top {
  top: 10px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 2;
}

.pos-left {
  left: 10px;
  top: 50%;
  transform: translateY(-50%) rotate(10deg); /* Slight natural rotation */
  z-index: 3;
}

.pos-right {
  right: 10px;
  top: 50%;
  transform: translateY(-50%) rotate(-10deg);
  z-index: 3;
}

.slot-label {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 100%;
  text-align: center;
  color: rgba(255,255,255,0.4);
  font-size: 14px;
  font-weight: 700;
  pointer-events: none;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.empty-slot {
  width: 100%;
  height: 100%;
  border: 2px dashed rgba(255,255,255,0.15);
  border-radius: 6px;
  opacity: 0.5;
  background: rgba(255,255,255,0.02);
}

.played-card {
  box-shadow: 0 10px 20px rgba(0,0,0,0.4);
  animation: playCardIn 0.3s ease-out;
}

@keyframes playCardIn {
  from { transform: scale(1.5) translateY(20px); opacity: 0; }
  to { transform: scale(1) translateY(0); opacity: 1; }
}

.card-slot.winner .played-card {
  box-shadow: 0 0 30px rgba(251, 191, 36, 0.8), 0 0 10px rgba(251, 191, 36, 0.4) inset; /* Rich gold glow */
  transform: scale(1.15) translateY(-10px);
  z-index: 10 !important;
}
</style>
