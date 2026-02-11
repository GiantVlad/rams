<script setup>
import { computed, onMounted } from 'vue'
import { useGameStore } from './stores/game'
import GameTable from './components/GameTable.vue'
import ScoreBoard from './components/ScoreBoard.vue'
import PhasePanel from './components/PhasePanel.vue'

const game = useGameStore()

const statusLine = computed(() => {
  if (!game.state) return 'Connecting...'
  const id = game.gameId
  const phase = game.phase
  const turn = game.currentPlayerIndex
  const status = game.gameStatus
  
  if (status === 'finished') {
    return `Game #${id} · Finished`
  }
  
  const phaseLabel = phase.charAt(0).toUpperCase() + phase.slice(1)
  return `Game #${id} · ${phaseLabel} Phase · Turn: ${game.getPlayerName(turn)}`
})

onMounted(async () => {
  await game.checkResume()
})
</script>

<template>
  <div class="app-container">
    <!-- Header -->
    <header class="app-header">
      <div class="brand">
        <span class="logo-icon">♠</span>
        <span class="logo-text">Rams</span>
      </div>
      <div class="status-bar">
        {{ statusLine }}
      </div>
      <div class="connection-status" :class="{ error: game.error }">
        <span v-if="game.error">Error: {{ game.error }}</span>
        <span v-else-if="game.loading">Syncing...</span>
        <span v-else class="online">● Online</span>
      </div>
    </header>

    <!-- Main Layout -->
    <main v-if="game.state" class="game-layout">
      <!-- Left: Scoreboard -->
      <aside class="layout-col left">
        <ScoreBoard 
          :players="game.players" 
          :currentTurn="game.currentPlayerIndex"
          :roundTaken="game.state.round?.taken || []"
        />
      </aside>

      <!-- Center: Table -->
      <section class="layout-col center">
        <GameTable />
      </section>

      <!-- Right: Phase/Actions -->
      <aside class="layout-col right">
        <PhasePanel />
      </aside>
    </main>

    <!-- Resume Prompt -->
    <div v-else-if="game.resumableGame" class="modal-overlay">
      <div class="modal-card">
        <h2>Active Game Found</h2>
        <p>
          You have an unfinished game (ID: {{ game.resumableGameSummary.id }}).<br>
          Round {{ game.resumableGameSummary.round }} - {{ game.resumableGameSummary.phase }} phase.
        </p>
        <div class="modal-actions">
          <button class="btn-primary" @click="game.confirmResume()">Continue Game</button>
          <button class="btn-secondary" @click="game.abandonResume()">Start New Game</button>
        </div>
      </div>
    </div>

    <div v-else class="loading-screen">
      <h1>Rams</h1>
      <p>Waiting for game data...</p>
      <button @click="game.newGame()">Start New Game</button>
    </div>

    <!-- Notifications / Overlays -->
    <Transition name="fade">
      <div v-if="game.justFinishedRound !== null" class="notification-overlay" @click="game.dismissRoundResult">
        <div class="notif-card">
          <h3>Round {{ game.justFinishedRound }} Finished</h3>
          <p v-if="game.roundWinner !== null">
            Winner: <strong>{{ game.getPlayerName(game.roundWinner) }}</strong> with {{ game.roundWinnerTricks }} tricks
          </p>
          <p v-else>No winner (Tie)</p>
          <span class="dismiss-hint">Click to dismiss</span>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
/* ... (keep existing styles) */
.modal-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 200;
  backdrop-filter: blur(5px);
}

.modal-card {
  background: #1f2937;
  padding: 30px;
  border-radius: 12px;
  border: 1px solid #4b5563;
  max-width: 400px;
  width: 90%;
  text-align: center;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.modal-card h2 {
  color: #60a5fa;
  margin-top: 0;
}

.modal-actions {
  display: flex;
  gap: 15px;
  justify-content: center;
  margin-top: 25px;
}

.btn-primary {
  background: #2563eb;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: bold;
}

.btn-primary:hover {
  background: #1d4ed8;
}

.btn-secondary {
  background: transparent;
  color: #9ca3af;
  border: 1px solid #4b5563;
  padding: 10px 20px;
  border-radius: 6px;
  cursor: pointer;
}

.btn-secondary:hover {
  background: #374151;
  color: white;
}

.app-container {
  display: flex;
  flex-direction: column;
  height: 100vh;
  width: 100vw;
  background: #111827; /* Dark background */
  color: #e5e7eb;
  overflow: hidden;
}

.app-header {
  height: 50px;
  background: #1f2937;
  border-bottom: 1px solid #374151;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 20px;
  flex-shrink: 0;
}

.brand {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: bold;
  font-size: 18px;
}

.logo-icon { color: #60a5fa; }

.status-bar {
  font-family: monospace;
  opacity: 0.7;
}

.connection-status {
  font-size: 12px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.connection-status.error { color: #ef4444; }
.online { color: #10b981; }

.game-layout {
  flex: 1;
  display: flex;
  padding: 20px;
  gap: 20px;
  overflow: hidden; /* Prevent scrolling, scale table instead */
}

.layout-col {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.layout-col.left {
  width: 240px;
  flex-shrink: 0;
}

.layout-col.center {
  flex: 1;
  min-width: 0; /* Flexbox text-overflow fix */
}

.layout-col.right {
  width: 240px;
  flex-shrink: 0;
}

.loading-screen {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 20px;
}

/* Notification Overlay */
.notification-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100;
  backdrop-filter: blur(4px);
}

.notif-card {
  background: #1f2937;
  padding: 30px;
  border-radius: 16px;
  border: 2px solid #3b82f6;
  text-align: center;
  box-shadow: 0 20px 50px rgba(0,0,0,0.5);
  animation: slideUp 0.3s ease-out;
}

.notif-card h3 {
  margin: 0 0 10px 0;
  color: #60a5fa;
}

.dismiss-hint {
  display: block;
  margin-top: 20px;
  font-size: 12px;
  opacity: 0.5;
}

@keyframes slideUp {
  from { transform: translateY(20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Mobile Responsive (Basic) */
@media (max-width: 900px) {
  .game-layout {
    flex-direction: column;
    overflow-y: auto;
  }
  
  .layout-col.left, .layout-col.right {
    width: 100%;
    height: auto;
  }
  
  .layout-col.center {
    min-height: 500px;
  }
}
</style>