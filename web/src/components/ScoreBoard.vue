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
  background: var(--bg-panel);
  border-radius: 16px;
  padding: 20px;
  color: var(--text-main);
  height: 100%;
  border: 1px solid var(--border-color);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2);
}

.section-title {
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 2px;
  color: var(--text-muted);
  margin-bottom: 20px;
  font-weight: 800;
  border-bottom: 1px solid var(--border-color);
  padding-bottom: 12px;
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
  padding: 12px;
  background: rgba(255,255,255,0.03);
  border-radius: 10px;
  border: 1px solid transparent;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.player-row.active {
  background: rgba(59, 130, 246, 0.1);
  border-color: rgba(59, 130, 246, 0.3);
  box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.1);
}

.row-main {
  display: flex;
  align-items: center;
  gap: 12px;
}

.p-avatar {
  width: 36px;
  height: 36px;
  background: var(--bg-panel-light);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 700;
  color: white;
  border: 2px solid var(--border-color);
}

.player-row.active .p-avatar {
  border-color: var(--accent-blue);
  color: var(--accent-blue);
}

.p-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.p-name {
  font-size: 14px;
  font-weight: 700;
  display: flex;
  gap: 8px;
  align-items: center;
}

.type {
  font-size: 10px;
  opacity: 0.5;
  text-transform: uppercase;
  font-weight: 500;
  background: rgba(255,255,255,0.05);
  padding: 1px 4px;
  border-radius: 3px;
}

.p-stats {
  display: flex;
  gap: 6px;
}

.stat-pill {
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 6px;
  background: rgba(0,0,0,0.2);
  font-weight: 600;
}

.pile { color: var(--text-main); }
.jacks { color: var(--accent-gold); }

.tricks-count {
  text-align: right;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.t-label {
  font-size: 10px;
  text-transform: uppercase;
  font-weight: 700;
  opacity: 0.4;
  letter-spacing: 0.5px;
}

.t-val {
  font-size: 18px;
  font-weight: 800;
  color: var(--accent-blue);
  line-height: 1;
}
</style>
