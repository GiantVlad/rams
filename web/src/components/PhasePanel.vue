<script setup>
import { computed } from 'vue'
import { useGameStore } from '../stores/game'

const game = useGameStore()

const cardsLeft = computed(() => {
  if (game.state?.round?.remaining_deck_count !== undefined) {
    return game.state.round.remaining_deck_count
  }
  if (!game.state?.round?.hands) return 0
  const totalDealt = game.state.round.hands.flat().length
  const trumpCard = game.state.trumpCardId ? 1 : 0
  return 36 - totalDealt - trumpCard
})

const formattedExchangeStatus = computed(() => {
  if (!game.exchangeStatus) return null
  return game.exchangeStatus.replace(/P(\d+)/g, (match, index) => {
    return game.getPlayerName(parseInt(index))
  })
})
</script>

<template>
  <div class="phase-panel">
    <div class="section-title">Actions & Info</div>

    <!-- Exchange Phase -->
    <div v-if="game.phase === 'exchange'" class="phase-section">
      <div class="phase-header">Exchange Phase</div>
      <div class="phase-body">
        <div class="info-row">
          <span>Cards in deck</span>
          <span class="val">{{ cardsLeft }}</span>
        </div>
        
        <div v-if="game.isHumanTurn" class="human-actions">
          <div class="selection-info">
            Selected: <span class="highlight">{{ game.discardCardIds.length }}</span>
          </div>
          <button 
            class="action-btn primary"
            :disabled="game.loading"
            @click="game.submitExchange()"
          >
            Confirm Exchange
          </button>
          <div class="tip">Select cards to discard</div>
        </div>
        
        <div v-else class="ai-status">
          {{ formattedExchangeStatus || 'Waiting for others...' }}
        </div>
      </div>
    </div>

    <!-- Participation Phase -->
    <div v-if="game.phase === 'choose_to_play'" class="phase-section">
      <div class="phase-header">Participation</div>
      
      <div v-if="game.isHumanTurn" class="human-actions">
        <p class="tip">Will you play this round?</p>
        <button 
          class="action-btn primary" 
          :disabled="game.loading" 
          @click="game.submitParticipation(true)"
        >
          Play Round
        </button>
        <button 
          class="action-btn warning" 
          :disabled="game.loading" 
          @click="game.submitParticipation(false)"
        >
          Pass (Fold)
        </button>
      </div>
      <div v-else class="ai-status">
        Waiting for {{ game.getPlayerName(game.currentPlayerIndex) }} to decide...
      </div>
    </div>

    <!-- Play Phase -->
    <div v-if="game.phase === 'play'" class="phase-section">
      <div class="phase-header">Play Phase</div>
      
      <!-- Jacks Declaration -->
      <div v-if="game.humanHasJacks && game.isHumanTurn" class="special-action">
        <div class="action-title">Special Move</div>
        <p>You have two Jacks of {{ game.humanHasJacks }}!</p>
        <button 
          class="action-btn warning"
          :disabled="game.loading"
          @click="game.declareJacks()"
        >
          Declare Jacks (-5 Pile)
        </button>
      </div>
      
      <div class="phase-body" v-else>
         <div class="tip">
           {{ game.isHumanTurn ? 'Select a card to play.' : 'Waiting for opponents...' }}
         </div>
      </div>
    </div>

    <!-- Game End -->
    <div v-if="game.gameStatus === 'finished'" class="phase-section finished">
      <div class="phase-header">Game Over</div>
      <div class="winner-display">
        Winner: {{ game.getPlayerName(game.winnerPlayerIndex) }}
      </div>
      <button 
        class="action-btn primary"
        @click="game.newGame()"
      >
        Start New Game
      </button>
    </div>

    <!-- System Actions (Bottom) -->
    <div class="system-actions">
      <button class="sys-btn" @click="game.refresh()">Sync/Refresh</button>
      <button v-if="game.gameStatus !== 'finished'" class="sys-btn danger" @click="game.newGame()">Restart</button>
    </div>
  </div>
</template>

<style scoped>
.phase-panel {
  background: #1f2937;
  border-radius: 12px;
  padding: 16px;
  color: white;
  height: 100%;
  border: 1px solid #374151;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.section-title {
  font-size: 14px;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: #9ca3af;
  margin-bottom: 8px;
  font-weight: 700;
  border-bottom: 1px solid #374151;
  padding-bottom: 8px;
}

.phase-section {
  background: rgba(255,255,255,0.05);
  border-radius: 8px;
  padding: 12px;
  flex: 1;
}

.phase-header {
  font-size: 16px;
  font-weight: bold;
  color: #60a5fa;
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.info-row {
  display: flex;
  justify-content: space-between;
  font-size: 14px;
  opacity: 0.8;
  margin-bottom: 12px;
}

.val {
  font-weight: bold;
  color: white;
}

.human-actions {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.action-btn {
  width: 100%;
  padding: 10px;
  border-radius: 6px;
  font-weight: 600;
  border: none;
  cursor: pointer;
  transition: transform 0.1s;
}

.action-btn:active {
  transform: scale(0.98);
}

.action-btn.primary {
  background: #3b82f6;
  color: white;
}
.action-btn.primary:hover {
  background: #2563eb;
}

.action-btn.warning {
  background: #f59e0b;
  color: black;
}

.tip {
  font-size: 12px;
  opacity: 0.6;
  text-align: center;
  margin-top: 8px;
}

.highlight {
  color: #3b82f6;
  font-weight: bold;
}

.system-actions {
  margin-top: auto;
  display: flex;
  gap: 10px;
}

.sys-btn {
  flex: 1;
  background: transparent;
  border: 1px solid #4b5563;
  color: #9ca3af;
  font-size: 12px;
  padding: 8px;
}

.sys-btn:hover {
  background: rgba(255,255,255,0.05);
  color: white;
}

.sys-btn.danger:hover {
  border-color: #ef4444;
  color: #ef4444;
}

.winner-display {
  font-size: 18px;
  font-weight: bold;
  color: #10b981;
  text-align: center;
  margin-bottom: 16px;
}
</style>
