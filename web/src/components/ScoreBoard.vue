<script setup>
import { useGameStore } from '../stores/game'
const game = useGameStore()

const props = defineProps({
  players: {
    type: Array,
    required: true
  },
  currentTurn: {
    type: Number,
    default: null
  },
  roundTaken: {
    type: Array,
    default: () => []
  }
})
</script>

<template>
  <div class="score-board">
    <div class="section-title">Scoreboard</div>
    
    <div class="players-list">
      <div 
        v-for="p in players" 
        :key="p.seat_index" 
        class="player-row"
        :class="{ active: currentTurn === p.seat_index }"
      >
        <div class="row-main">
          <div class="p-avatar">{{ game.getPlayerName(p.seat_index) === 'You' ? 'U' : game.getPlayerName(p.seat_index).charAt(0) }}</div>
          <div class="p-info">
            <div class="p-name">
              {{ game.getPlayerName(p.seat_index) }}
              <span class="type">{{ p.type }}</span>
            </div>
            <div class="p-stats">
              <span class="stat-pill pile">Pile: {{ p.pile }}</span>
              <span class="stat-pill jacks" v-if="p.maltzy_count > 0">Jacks: {{ p.maltzy_count }}</span>
            </div>
          </div>
        </div>
        
        <div class="round-stats" v-if="roundTaken[p.seat_index] !== undefined">
          <div class="tricks-count">
            <span class="t-label">Tricks</span>
            <span class="t-val">{{ roundTaken[p.seat_index] }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.score-board {
  background: #1f2937;
  border-radius: 12px;
  padding: 16px;
  color: white;
  height: 100%;
  border: 1px solid #374151;
}

.section-title {
  font-size: 14px;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: #9ca3af;
  margin-bottom: 16px;
  font-weight: 700;
  border-bottom: 1px solid #374151;
  padding-bottom: 8px;
}

.players-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.player-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px;
  background: rgba(255,255,255,0.03);
  border-radius: 8px;
  border-left: 3px solid transparent;
  transition: all 0.2s;
}

.player-row.active {
  background: rgba(59, 130, 246, 0.1);
  border-left-color: #3b82f6;
}

.row-main {
  display: flex;
  align-items: center;
  gap: 10px;
}

.p-avatar {
  width: 32px;
  height: 32px;
  background: #4b5563;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: bold;
}

.p-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.p-name {
  font-size: 14px;
  font-weight: 600;
  display: flex;
  gap: 6px;
  align-items: center;
}

.type {
  font-size: 10px;
  opacity: 0.5;
  text-transform: uppercase;
}

.p-stats {
  display: flex;
  gap: 6px;
}

.stat-pill {
  font-size: 11px;
  padding: 1px 6px;
  border-radius: 4px;
  background: rgba(0,0,0,0.3);
}

.pile { color: #fff; }
.jacks { color: #fcd34d; }

.tricks-count {
  text-align: right;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.t-label {
  font-size: 10px;
  opacity: 0.5;
}

.t-val {
  font-size: 16px;
  font-weight: bold;
  color: #60a5fa;
}
</style>
