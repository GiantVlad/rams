<script setup>
import { computed } from 'vue'

const props = defineProps({
  cardId: {
    type: String, // "H-11"
    required: false
  },
  selected: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  },
  interactive: {
    type: Boolean,
    default: false
  },
  faceDown: {
    type: Boolean,
    default: false
  }
})

const suit = computed(() => props.cardId?.split('-')[0])
const rank = computed(() => props.cardId?.split('-')[1])

const suitSymbol = computed(() => {
  switch (suit.value) {
    case 'H': return '♥'
    case 'D': return '♦'
    case 'C': return '♣'
    case 'S': return '♠'
    default: return ''
  }
})

const rankLabel = computed(() => {
  const r = Number(rank.value)
  if (r === 11) return 'J'
  if (r === 12) return 'Q'
  if (r === 13) return 'K'
  if (r === 14) return 'A'
  return String(r)
})

const isRed = computed(() => suit.value === 'H' || suit.value === 'D')
</script>

<template>
  <div 
    class="card" 
    :class="{ 
      selected, 
      disabled, 
      interactive, 
      faceDown, 
      red: isRed, 
      black: !isRed && !faceDown 
    }"
  >
    <div v-if="faceDown" class="card-back"></div>
    <div v-else-if="cardId" class="card-content">
      <div class="corner top-left">
        <span class="rank">{{ rankLabel }}</span>
        <span class="suit">{{ suitSymbol }}</span>
      </div>
      <div class="center-suit">{{ suitSymbol }}</div>
      <div class="corner bottom-right">
        <span class="rank">{{ rankLabel }}</span>
        <span class="suit">{{ suitSymbol }}</span>
      </div>
    </div>
    <div v-else class="card-placeholder"></div>
  </div>
</template>

<style scoped>
.card {
  width: 80px;
  height: 120px;
  background-color: #fff;
  border-radius: 6px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 6px;
  box-sizing: border-box;
  position: relative;
  transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.25s ease;
  user-select: none;
  font-family: 'Inter', -apple-system, sans-serif;
}

.card.interactive {
  cursor: pointer;
}

.card.interactive:hover:not(.disabled) {
  transform: translateY(-12px) scale(1.05);
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 10px 10px -5px rgba(0, 0, 0, 0.1);
  z-index: 20;
}

.card.selected {
  transform: translateY(-20px) scale(1.05);
  box-shadow: 0 0 0 3px #3b82f6, 0 10px 15px -3px rgba(59, 130, 246, 0.5);
}

.card.disabled {
  opacity: 0.6;
  filter: grayscale(0.8) contrast(0.8);
  cursor: not-allowed;
}

.card.faceDown {
  background-color: #1e3a8a; /* Dark Blue back */
  background-image: url('/images/card_bg.png'); /* Pattern overlay */
  background-size: cover;
  border: 4px solid #fff;
  box-shadow: inset 0 0 10px rgba(0,0,0,0.5), 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.card-back {
  width: 100%;
  height: 100%;
  background: repeating-linear-gradient(
    45deg,
    rgba(255,255,255,0.05),
    rgba(255,255,255,0.05) 10px,
    transparent 10px,
    transparent 20px
  );
  border-radius: 2px;
  border: 1px solid rgba(255,255,255,0.2);
}

.red {
  color: #ef4444; /* Vibrant red */
}

.black {
  color: #111827; /* Near black */
}

.card-content {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  position: relative;
}

.corner {
  display: flex;
  flex-direction: column;
  align-items: center;
  line-height: 1;
  font-weight: 800;
  font-size: 16px;
  position: absolute;
  letter-spacing: -1px;
}

.corner .suit {
  font-size: 14px;
  margin-top: 1px;
}

.top-left {
  top: 2px;
  left: 2px;
}

.bottom-right {
  bottom: 2px;
  right: 2px;
  transform: rotate(180deg);
}

.center-suit {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 52px;
  opacity: 0.1;
  text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
}

.card-placeholder {
  border: 2px dashed rgba(255,255,255,0.2);
  background: rgba(0,0,0,0.1);
  width: 100%;
  height: 100%;
  border-radius: 4px;
}
</style>
