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
  border-radius: 8px;
  box-shadow: 0 2px 5px rgba(0,0,0,0.2);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 8px;
  box-sizing: border-box;
  position: relative;
  transition: transform 0.2s, box-shadow 0.2s, top 0.2s;
  user-select: none;
}

.card.interactive {
  cursor: pointer;
}

.card.interactive:hover:not(.disabled) {
  transform: translateY(-10px);
  box-shadow: 0 5px 15px rgba(0,0,0,0.3);
  z-index: 10;
}

.card.selected {
  transform: translateY(-15px);
  box-shadow: 0 0 0 3px #3b82f6; /* Blue ring */
}

.card.disabled {
  opacity: 0.6;
  filter: grayscale(0.8);
  cursor: not-allowed;
}

.card.faceDown {
  background-color: #2c3e50;
  border: 2px solid #ecf0f1;
}

.card-back {
  width: 100%;
  height: 100%;
  background: repeating-linear-gradient(
    45deg,
    #2c3e50,
    #2c3e50 10px,
    #34495e 10px,
    #34495e 20px
  );
  border-radius: 4px;
}

.red {
  color: #e74c3c;
}

.black {
  color: #2c3e50;
}

.card-content {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.corner {
  display: flex;
  flex-direction: column;
  align-items: center;
  line-height: 1;
  font-weight: bold;
  font-size: 18px;
  position: absolute;
}

.top-left {
  top: 6px;
  left: 6px;
}

.bottom-right {
  bottom: 6px;
  right: 6px;
  transform: rotate(180deg);
}

.center-suit {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 48px;
  opacity: 0.15;
}

.card-placeholder {
  border: 2px dashed rgba(0,0,0,0.2);
  width: 100%;
  height: 100%;
  border-radius: 6px;
}
</style>
