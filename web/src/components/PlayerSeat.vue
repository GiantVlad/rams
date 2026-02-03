<script setup>
import { computed } from 'vue'
import { useGameStore } from '../stores/game'

const game = useGameStore()

const props = defineProps({
  player: {
    type: Object,
    required: true
  },
  isActive: {
    type: Boolean,
    default: false
  },
  isLocal: {
    type: Boolean,
    default: false
  },
  isDealer: {
    type: Boolean,
    default: false
  },
  position: {
    type: String, // 'bottom', 'left', 'top', 'right'
    default: 'top'
  }
})

const playerName = computed(() => game.getPlayerName(props.player.seat_index))
const playerInitial = computed(() => {
  if (playerName.value === 'You') return 'U'
  return playerName.value.charAt(0)
})

const isPassed = computed(() => {
  return game.passedPlayers.includes(props.player.seat_index)
})
</script>

<template>
  <div class="seat" :class="[position, { active: isActive, local: isLocal, passed: isPassed }]">
    <div class="avatar-container">
      <div class="avatar">
        {{ playerInitial }}
      </div>
      <div v-if="isDealer" class="dealer-badge">D</div>
      <div v-if="isPassed" class="passed-badge">PASS</div>
      <div v-if="isActive && !isPassed" class="thinking-indicator">
        <div class="dot"></div>
        <div class="dot"></div>
        <div class="dot"></div>
      </div>
    </div>
    
    <div class="info">
      <div class="name">
        {{ playerName }}
        <span class="type-badge">{{ player.type }}</span>
      </div>
      <div class="stats">
        <div class="pile-badge">
          <span class="label">Pile</span>
          <span class="value">{{ player.pile }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.seat {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 8px;
  border-radius: 12px;
  transition: all 0.3s;
  background: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(4px);
  min-width: 120px;
}

.seat.active {
  background: rgba(59, 130, 246, 0.2);
  box-shadow: 0 0 15px rgba(59, 130, 246, 0.4);
  border: 1px solid rgba(59, 130, 246, 0.5);
}

.seat.local {
  background: rgba(16, 185, 129, 0.1);
  border: 1px solid rgba(16, 185, 129, 0.3);
}

.avatar-container {
  position: relative;
}

.avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: #374151;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 18px;
  border: 2px solid #4b5563;
}

.seat.active .avatar {
  border-color: #3b82f6;
  background: #1e40af;
}

.dealer-badge {
  position: absolute;
  bottom: -4px;
  right: -4px;
  width: 20px;
  height: 20px;
  background: #f59e0b;
  color: #000;
  border-radius: 50%;
  font-size: 12px;
  font-weight: bold;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #1f2937;
}

.info {
  text-align: center;
  color: white;
}

.name {
  font-size: 16px;
  font-weight: 700;
  margin-bottom: 4px;
  display: flex;
  align-items: center;
  gap: 6px;
  justify-content: center;
}

.type-badge {
  font-size: 10px;
  background: rgba(255, 255, 255, 0.1);
  padding: 2px 4px;
  border-radius: 4px;
  text-transform: uppercase;
}

.stats {
  display: flex;
  gap: 8px;
  justify-content: center;
}

.pile-badge {
  background: rgba(0, 0, 0, 0.3);
  padding: 2px 8px;
  border-radius: 10px;
  font-size: 12px;
  display: flex;
  gap: 4px;
}

.value {
  font-weight: bold;
  color: #fff;
}

.label {
  opacity: 0.7;
}

.thinking-indicator {
  position: absolute;
  top: -8px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  gap: 2px;
}

.dot {
  width: 4px;
  height: 4px;
  background: #3b82f6;
  border-radius: 50%;
  animation: bounce 1.4s infinite ease-in-out both;
}

.dot:nth-child(1) { animation-delay: -0.32s; }
.dot:nth-child(2) { animation-delay: -0.16s; }

@keyframes bounce {
  0%, 80%, 100% { transform: scale(0); }
  40% { transform: scale(1); }
}

.seat.passed {
  opacity: 0.6;
  filter: grayscale(0.5);
}

.passed-badge {
  position: absolute;
  top: -10px;
  right: -10px;
  background: #ef4444;
  color: white;
  font-size: 10px;
  font-weight: bold;
  padding: 2px 6px;
  border-radius: 4px;
  border: 2px solid #1f2937;
  z-index: 10;
}
</style>
