import { defineStore } from 'pinia'
import { apiFetch } from '../api/client'
import { wsClient } from '../websocket'

export const useGameStore = defineStore('game', {
      state: () => ({
      state: null,
      resumableGame: null,
      loading: false,
      error: null,
          discardCardIds: [],
          justFinishedRound: null,
          lastRoundTaken: null,
          pollingInterval: null,
          processingAiMove: false,
          aiMoveTimeoutId: null,
          pendingAiMoveKey: null,
          pendingUpdates: [],
          isDisplayingTrickResult: false,
        }),
        getters: {
          gameId: (s) => s.state?.game?.id ?? null,
          // ... (keep existing getters)
          resumableGameSummary: (s) => {
            if (!s.resumableGame) return null
            const g = s.resumableGame.game
            return {
              id: g.id,
              round: g.round_number,
              phase: g.phase
            }
          },
          phase: (s) => s.state?.game?.phase ?? null,
          currentPlayerIndex: (s) => s.state?.game?.current_player_index ?? null,
          humanIndex: () => 0,
          isHumanTurn: (s) => s.currentPlayerIndex === 0,
          round: (s) => s.state?.round ?? null,
          hand: (s) => s.state?.round?.hands?.[0] ?? [],
          currentTrick: (s) => s.state?.round?.current_trick ?? [],
          exchanged: (s) => s.state?.round?.exchanged ?? [],
          fiveSameSuitDeclared: (s) => s.state?.round?.five_same_suit_declared ?? null,
          partiyaDeclaredBy: (s) => s.state?.round?.partiya_declared_by ?? null,
          trumpCardId: (s) => s.state?.game?.trump_card_id ?? null,
          players: (s) => s.state?.players ?? [],
          gameStatus: (s) => s.state?.game?.status ?? null,
          winnerPlayerIndex: (s) => s.state?.game?.winner_player_index ?? null,
          roundNumber: (s) => s.state?.round?.number ?? null,
          exchangeStatus: (s) => s.state?.exchangeStatus ?? null,
          passedPlayers: (s) => s.state?.round?.passed_players ?? [],
          humanHasJacks: (s) => {
            const hand = s.hand
            if (!Array.isArray(hand) || hand.length !== 5) return null
            const suitCounts = {}
            for (const id of hand) {
              if (!id || typeof id !== 'string') continue
              const [suit, rank] = id.split('-')
              if (rank === '11') { // Jack
                suitCounts[suit] = (suitCounts[suit] || 0) + 1
                if (suitCounts[suit] >= 2) return suit
              }
            }
            return null
          },
          roundWinner: (s) => {
              if (!s.lastRoundTaken) return null
              let maxTricks = -1
              let winners = []
              s.lastRoundTaken.forEach((tricks, playerIdx) => {
                  if (tricks > maxTricks) {
                      maxTricks = tricks
                      winners = [playerIdx]
                  } else if (tricks === maxTricks) {
                      winners.push(playerIdx)
                  }
              })
              // Return the first winner even if there's a tie
              if (winners.length > 0) return winners[0]
              return null
          },
          roundWinnerTricks: (s) => {
              if (!s.lastRoundTaken) return 0
              return Math.max(...s.lastRoundTaken)
          },
          getPlayerName: (s) => (index) => {
            const names = ['You', 'Mike', 'William', 'Sarah']
            return names[index] || `Player ${index}`
          }
        },
        actions: {
          initializeRealtime(gameId) {
            wsClient.removeAllListeners('game.update')
            wsClient.connect(gameId)
            wsClient.on('game.update', (update) => {
              console.log('Received game update:', update)
              this.handleStateUpdate(update)
            })
          },

          shouldAutoPlayAi(gameState = this.state) {
            return Boolean(
              gameState &&
              gameState.game?.status === 'in_progress' &&
              gameState.game?.current_player_index !== 0
            )
          },

          getAiMoveKey(gameState = this.state) {
            const game = gameState?.game
            if (!game) return null

            return `${game.id}:${game.phase}:${game.current_player_index}:${game.round_number}`
          },

          cancelScheduledAiMove() {
            if (this.aiMoveTimeoutId) {
              clearTimeout(this.aiMoveTimeoutId)
            }

            this.aiMoveTimeoutId = null
            this.pendingAiMoveKey = null
            this.processingAiMove = false
          },

          scheduleAiMove(gameState = this.state) {
            if (!this.shouldAutoPlayAi(gameState)) {
              this.cancelScheduledAiMove()
              return
            }

            const nextMoveKey = this.getAiMoveKey(gameState)
            if (this.pendingAiMoveKey === nextMoveKey) {
              return
            }

            if (this.aiMoveTimeoutId) {
              clearTimeout(this.aiMoveTimeoutId)
            }

            this.pendingAiMoveKey = nextMoveKey
            this.processingAiMove = true

            console.log('AI move planned in 1.5s...')

            this.aiMoveTimeoutId = setTimeout(async () => {
              const requestKey = this.pendingAiMoveKey
              this.aiMoveTimeoutId = null

              if (!requestKey || this.getAiMoveKey() !== requestKey || !this.shouldAutoPlayAi()) {
                if (this.pendingAiMoveKey === requestKey) {
                  this.pendingAiMoveKey = null
                  this.processingAiMove = false
                }

                return
              }

              try {
                console.log('Making AI move request...')
                await apiFetch(`/api/games/${this.gameId}/ai-play`, {
                  method: 'POST'
                })
                console.log('AI move requested')
              } catch (e) {
                const staleRequest = e.status === 400 && !this.shouldAutoPlayAi()
                if (!staleRequest) {
                  console.error('Error triggering AI move:', e)
                }
              } finally {
                if (this.pendingAiMoveKey === requestKey) {
                  this.pendingAiMoveKey = null
                  this.processingAiMove = false
                }
              }
            }, 1500)
          },

          handleStateUpdate(newState) {
              // If we are currently showing a full trick result, queue any incoming updates (like the cleared trick state)
              if (this.isDisplayingTrickResult) {
                  this.pendingUpdates.push(newState)
                  return
              }

              // Apply state logic
              this.applyState(newState)
              this.scheduleAiMove(newState)

              // Check if this new state is a "Full Trick" that requires a viewing pause
              if (newState?.round?.current_trick) {
                  const passed = newState.round.passed_players || []
                  const activeCount = 4 - passed.length
                  const trickLength = newState.round.current_trick.length
                  
                  // If trick is full, start the viewing delay
                  if (trickLength > 0 && trickLength === activeCount) {
                      this.isDisplayingTrickResult = true
                      setTimeout(() => {
                          this.isDisplayingTrickResult = false
                          // Apply the latest pending update if one exists
                          if (this.pendingUpdates.length > 0) {
                              const latest = this.pendingUpdates[this.pendingUpdates.length - 1]
                              this.pendingUpdates = []
                              this.handleStateUpdate(latest)
                          }
                      }, 1500)
                  }
              }
          },

          applyState(newState) {
              if (this.state && newState) {
                  const oldRound = this.state.round?.number
                  const newRound = newState.round?.number
                  
                  if (oldRound && newRound && newRound > oldRound) {
                      this.justFinishedRound = oldRound
                      this.lastRoundTaken = this.state.round.taken
                  }
              }
              this.state = newState
          },
      
          async newGame(seed = null) {
            this.loading = true
            this.error = null
            this.justFinishedRound = null
            this.lastRoundTaken = null
            this.resumableGame = null
            this.discardCardIds = []
            
            // Clear any existing polling
            if (this.pollingInterval) {
              clearInterval(this.pollingInterval)
            }
            
            try {
              const body = seed === null ? {} : { seed }
              const data = await apiFetch('/api/games', { method: 'POST', body: JSON.stringify(body) })

              this.initializeRealtime(data.game.id)
              this.state = data
              this.scheduleAiMove(data)
            } catch (e) {
              this.error = e.message
            } finally {
              this.loading = false
            }
          },

          async checkResume() {
            this.loading = true
            this.error = null
            this.resumableGame = null
            try {
              const data = await apiFetch('/api/games/resume', { method: 'GET' })
              if (!data) {
                return false
              }

              this.resumableGame = data
              return true
            } catch (e) {
              console.error('Error checking resume:', e)
              return false
            } finally {
              this.loading = false
            }
          },

          confirmResume() {
            if (!this.resumableGame) return

            const data = this.resumableGame
            this.resumableGame = null // Clear temp state

            this.initializeRealtime(data.game.id)
            this.state = data
            this.scheduleAiMove(data)
          },

          abandonResume() {
            this.resumableGame = null
          },

          // Kept for backward compatibility if needed, but mostly replaced by checkResume + confirmResume
          async resumeGame() {
            const found = await this.checkResume()
            if (found) {
              this.confirmResume()
              return true
            }
            return false
          },
      
          startPolling() {
            if (this.pollingInterval) {
              clearInterval(this.pollingInterval)
            }
            
            // Only poll as a fallback, not for triggering AI moves
            this.pollingInterval = setInterval(async () => {
              if (this.gameId && !this.loading) {
                try {
                  const data = await apiFetch(`/api/games/${this.gameId}`)
                  // Only update if state actually changed
                  if (JSON.stringify(data) !== JSON.stringify(this.state)) {
                    this.handleStateUpdate(data)
                  }
                } catch (e) {
                  console.error('Error polling for game updates:', e)
                }
              }
            }, 5000) // Poll every 5 seconds as fallback only
          },
          stopPolling() {
            if (this.pollingInterval) {
              clearInterval(this.pollingInterval)
              this.pollingInterval = null
            }
          },
      
          async refresh() {
            if (!this.gameId) return
            this.loading = true
            this.error = null
            this.justFinishedRound = null
            this.lastRoundTaken = null
            this.discardCardIds = []
            try {
              const data = await apiFetch(`/api/games/${this.gameId}`)
              this.handleStateUpdate(data)
              // Start polling if not already started
              if (!this.pollingInterval) {
                this.startPolling()
              }
            } catch (e) {
              this.error = e.message
            } finally {
              this.loading = false
            }
          },
      
              async submitExchange() {
                if (!this.gameId) return
                this.loading = true
                this.error = null
                this.justFinishedRound = null
                try {
                  const payload = { player_index: 0, discard_card_ids: this.discardCardIds }
                  await apiFetch(`/api/games/${this.gameId}/exchange`, { method: 'POST', body: JSON.stringify(payload) })
                  // Clear discard selection after successful exchange
                  this.discardCardIds = []
                } catch (e) {
                  this.error = e.message
                            } finally {
                              this.loading = false
                            }
                          },
                      
                          async submitParticipation(play) {
                            if (!this.gameId) return
                            this.loading = true
                            this.error = null
                            try {
                              const payload = { player_index: 0, play }
                              await apiFetch(`/api/games/${this.gameId}/participation`, { method: 'POST', body: JSON.stringify(payload) })
                            } catch (e) {
                              this.error = e.message
                            } finally {
                              this.loading = false
                            }
                          },
                
                          async declareJacks() {
                if (!this.gameId) return
                this.loading = true
                this.error = null
                try {
                  const payload = { player_index: 0 }
                  await apiFetch(`/api/games/${this.gameId}/declare-jacks`, { method: 'POST', body: JSON.stringify(payload) })
                } catch (e) {
                  this.error = e.message
                } finally {
                  this.loading = false
                }
              },      
              async playCard(cardId) {
                if (!this.gameId) return
                this.loading = true
                this.error = null
                this.justFinishedRound = null
                this.discardCardIds = []
                try {
                  const payload = { player_index: 0, card_id: cardId }
                  await apiFetch(`/api/games/${this.gameId}/move`, { method: 'POST', body: JSON.stringify(payload) })
                } catch (e) {
                  this.error = e.message
                } finally {
                  this.loading = false
                }
              },      
          toggleDiscard(cardId) {
            const idx = this.discardCardIds.indexOf(cardId)
            if (idx > -1) {
              this.discardCardIds.splice(idx, 1)
            } else {
              this.discardCardIds.push(cardId)
            }
          },
      
          clearError() {
            this.error = null
          },
      
          dismissRoundResult() {
            this.justFinishedRound = null
          },
        },
      })
