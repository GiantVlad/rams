import { defineStore } from 'pinia'
import { apiFetch } from '../api/client'
import { wsClient } from '../websocket'

export const useGameStore = defineStore('game', {
      state: () => ({
      state: null,
      loading: false,
      error: null,
          discardCardIds: [],
          justFinishedRound: null,
          lastRoundTaken: null,
          pollingInterval: null,
          processingAiMove: false,
          pendingUpdates: [],
          isDisplayingTrickResult: false,
        }),
        getters: {
          gameId: (s) => s.state?.game?.id ?? null,
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
          handleStateUpdate(newState) {
              // Always reset processing flag on state update reception (it comes from server)
              this.processingAiMove = false

              // If we are currently showing a full trick result, queue any incoming updates (like the cleared trick state)
              if (this.isDisplayingTrickResult) {
                  this.pendingUpdates.push(newState)
                  return
              }

              // Apply state logic
              this.applyState(newState)

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
            this.discardCardIds = []
            
            // Clear any existing polling
            if (this.pollingInterval) {
              clearInterval(this.pollingInterval)
            }
            
            try {
              const body = seed === null ? {} : { seed }
              const data = await apiFetch('/api/games', { method: 'POST', body: JSON.stringify(body) })
              
              // Connect to WebSocket for real-time updates
              wsClient.connect(data.game.id)
              wsClient.on('game.update', (update) => {
                console.log('Received game update:', update)
                this.handleStateUpdate(update)
                
                // If it's AI's turn, trigger AI move
                if (update.game.current_player_index !== 0 && update.game.status === 'in_progress') {
                  console.log('Triggering AI move for player', update.game.current_player_index)
                  this.triggerAiMove()
                }
              })
              
              this.state = data
            } catch (e) {
              this.error = e.message
            } finally {
              this.loading = false
            }
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
      
              async triggerAiMove() {
                if (!this.gameId || this.processingAiMove) {
                  return
                }
                
                // Don't trigger if already loading from user action
                if (this.loading && this.currentPlayerIndex === 0) {
                  return
                }
                
                this.processingAiMove = true
                
                console.log('AI move planned in 1.5s...')
                
                setTimeout(async () => {
                  try {
                    console.log('Making AI move request...')
                    await apiFetch(`/api/games/${this.gameId}/ai-play`, {
                      method: 'POST'
                    })
                    console.log('AI move requested')
                  } catch (e) {
                    console.error('Error triggering AI move:', e)
                  } finally {
                    this.processingAiMove = false
                  }
                }, 1500)
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
