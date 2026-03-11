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
  background: var(--bg-panel);
  border-radius: 16px;
  padding: 20px;
  color: var(--text-main);
  height: 100%;
  border: 1px solid var(--border-color);
  display: flex;
  flex-direction: column;
  gap: 20px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2);
}

.section-title {
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 2px;
  color: var(--text-muted);
  margin-bottom: 4px;
  font-weight: 800;
  border-bottom: 1px solid var(--border-color);
  padding-bottom: 12px;
}

.phase-section {
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.05);
  border-radius: 12px;
  padding: 16px;
  flex: 1;
  display: flex;
  flex-direction: column;
}

.phase-header {
  font-size: 16px;
  font-weight: 800;
  color: var(--accent-blue);
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
  letter-spacing: -0.2px;
}

.info-row {
  display: flex;
  justify-content: space-between;
  font-size: 14px;
  color: var(--text-muted);
  margin-bottom: 16px;
  font-weight: 500;
}

.val {
  font-weight: 700;
  color: var(--text-main);
}

.human-actions {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: auto;
}

.action-btn {
  width: 100%;
  padding: 12px;
  border-radius: 8px;
  font-weight: 700;
  font-size: 14px;
  border: none;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.action-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.action-btn:active:not(:disabled) {
  transform: translateY(0);
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.action-btn.primary {
  background: var(--accent-blue);
  color: white;
  box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.2);
}
.action-btn.primary:hover:not(:disabled) {
  background: var(--accent-blue-hover);
  box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3);
}

.action-btn.warning {
  background: var(--accent-gold);
  color: #000;
  box-shadow: 0 4px 6px -1px rgba(251, 191, 36, 0.2);
}
.action-btn.warning:hover:not(:disabled) {
  background: #f59e0b;
  box-shadow: 0 10px 15px -3px rgba(251, 191, 36, 0.3);
}

.tip {
  font-size: 12px;
  color: var(--text-muted);
  text-align: center;
  margin-top: 8px;
  font-weight: 500;
}

.highlight {
  color: var(--accent-blue);
  font-weight: 800;
}

.system-actions {
  margin-top: auto;
  display: flex;
  gap: 10px;
}

.sys-btn {
  flex: 1;
  background: var(--bg-panel-light);
  border: 1px solid var(--border-color);
  color: var(--text-main);
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  padding: 8px;
  border-radius: 6px;
  transition: all 0.2s;
}

.sys-btn:hover {
  background: #475569;
  border-color: #64748b;
}

.sys-btn.danger:hover {
  background: rgba(239, 68, 68, 0.1);
  border-color: var(--accent-red);
  color: var(--accent-red);
}

.winner-display {
  font-size: 20px;
  font-weight: 800;
  color: var(--accent-green);
  text-align: center;
  margin-bottom: 20px;
  letter-spacing: -0.5px;
}
</style>
