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
  background: var(--bg-panel);
  padding: 30px;
  border-radius: 16px;
  border: 1px solid var(--border-color);
  max-width: 400px;
  width: 90%;
  text-align: center;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
}

.modal-card h2 {
  color: var(--accent-blue);
  margin-top: 0;
}

.modal-actions {
  display: flex;
  gap: 15px;
  justify-content: center;
  margin-top: 25px;
}

.btn-primary {
  background: var(--accent-blue);
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.2s;
}

.btn-primary:hover {
  background: var(--accent-blue-hover);
  transform: translateY(-2px);
}

.btn-secondary {
  background: transparent;
  color: var(--text-muted);
  border: 1px solid var(--border-color);
  padding: 12px 24px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.2s;
}

.btn-secondary:hover {
  background: var(--bg-panel-light);
  color: white;
}

.app-container {
  display: flex;
  flex-direction: column;
  height: 100vh;
  width: 100vw;
  color: var(--text-main);
  overflow: hidden;
  background: transparent; /* Rely on body background */
}

.app-header {
  height: 60px;
  background: rgba(30, 41, 59, 0.8);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid rgba(255,255,255,0.05);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
  flex-shrink: 0;
}

.brand {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 800;
  font-size: 20px;
  letter-spacing: -0.5px;
}

.logo-icon { 
  color: var(--accent-blue); 
  font-size: 24px;
}

.status-bar {
  font-family: monospace;
  opacity: 0.6;
  font-size: 13px;
}

.connection-status {
  font-size: 13px;
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 500;
}

.connection-status.error { color: var(--accent-red); }
.online { color: var(--accent-green); }

.game-layout {
  flex: 1;
  display: flex;
  padding: 24px;
  gap: 24px;
  overflow: hidden; /* Prevent scrolling, scale table instead */
}

.layout-col {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.layout-col.left {
  width: 280px;
  flex-shrink: 0;
}

.layout-col.center {
  flex: 1;
  min-width: 0; /* Flexbox text-overflow fix */
}

.layout-col.right {
  width: 280px;
  flex-shrink: 0;
}

.loading-screen {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 30px;
}

.loading-screen h1 {
  font-size: 64px;
  font-weight: 800;
  margin: 0;
  background: linear-gradient(135deg, #60a5fa, #3b82f6);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  display: flex;
  align-items: center;
  gap: 16px;
}

.loading-screen h1::before {
  content: '♠';
  font-size: 72px;
  color: var(--accent-blue);
  -webkit-text-fill-color: initial;
  animation: float 3s ease-in-out infinite;
}

@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

.loading-screen p {
  font-size: 18px;
  color: var(--text-muted);
  margin: 0;
}

.loading-screen button {
  background: var(--accent-blue);
  color: white;
  border: none;
  padding: 16px 32px;
  border-radius: 12px;
  font-size: 18px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
  box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3);
}

.loading-screen button:hover {
  transform: translateY(-4px) scale(1.05);
  background: var(--accent-blue-hover);
  box-shadow: 0 20px 25px -5px rgba(59, 130, 246, 0.4);
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
  background: var(--bg-panel);
  padding: 30px;
  border-radius: 16px;
  border: 2px solid var(--accent-blue);
  text-align: center;
  box-shadow: 0 20px 50px rgba(0,0,0,0.5);
  animation: slideUp 0.3s ease-out;
}

.notif-card h3 {
  margin: 0 0 10px 0;
  color: var(--accent-blue);
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