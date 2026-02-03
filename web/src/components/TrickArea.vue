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
  width: 240px;
  height: 240px;
  position: relative;
  /* border: 1px dashed rgba(255,255,255,0.1); */
  border-radius: 50%;
}

.card-slot {
  position: absolute;
  width: 80px;
  height: 120px;
  transition: all 0.3s ease-out;
}

/* Positions relative to center */
.pos-bottom {
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  z-index: 4;
}

.pos-top {
  top: 0;
  left: 50%;
  transform: translateX(-50%);
  z-index: 2;
}

.pos-left {
  left: 0;
  top: 50%;
  transform: translateY(-50%) rotate(90deg);
  z-index: 3;
}

.pos-right {
  right: 0;
  top: 50%;
  transform: translateY(-50%) rotate(-90deg);
  z-index: 3;
}

.slot-label {
  position: absolute;
  top: -24px;
  width: 100%;
  text-align: center;
  color: rgba(255,255,255,0.8);
  font-size: 14px;
  font-weight: 600;
  pointer-events: none;
}

.pos-bottom .slot-label { top: auto; bottom: -24px; }
.pos-left .slot-label { transform: rotate(-90deg); top: auto; right: 100%; margin-right: 8px; }
.pos-right .slot-label { transform: rotate(90deg); top: auto; left: 100%; margin-left: 8px; }

.empty-slot {
  width: 100%;
  height: 100%;
  border: 2px dashed rgba(255,255,255,0.1);
  border-radius: 8px;
  opacity: 0.5;
}

.played-card {
  box-shadow: 0 4px 10px rgba(0,0,0,0.3);
}

.card-slot.winner .played-card {
  box-shadow: 0 0 20px rgba(255, 215, 0, 0.6); /* Gold glow */
  transform: scale(1.1);
  z-index: 10 !important;
}

/* Override rotation for cards so they face up? 
   Actually, in real life, side players play sideways. 
   But for readability, maybe we want them upright?
   Let's keep rotation for now, it looks more "table-like".
*/
</style>
