const WebSocket = require('ws');
const http = require('http');

// Create HTTP server for health checks and broadcast endpoint
const server = http.createServer((req, res) => {
    if (req.method === 'POST' && req.url === '/broadcast') {
        let body = '';
        req.on('data', chunk => {
            body += chunk.toString();
        });
        
        req.on('end', () => {
            try {
                const data = JSON.parse(body);
                console.log('Received broadcast:', data.event, 'for game:', data.gameId);
                broadcastToGame(data.gameId, data.data);
                res.writeHead(200, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ success: true }));
            } catch (error) {
                console.error('Error parsing broadcast:', error);
                res.writeHead(400, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ error: 'Invalid JSON' }));
            }
        });
    } else {
        res.writeHead(404);
        res.end();
    }
});

const wss = new WebSocket.Server({ server });

// Store connected clients by game ID
const gameClients = new Map();

wss.on('connection', (ws, request) => {
    console.log('New WebSocket connection');
    
    let currentGameId = null;
    
    ws.on('message', (message) => {
        try {
            const data = JSON.parse(message);
            
            if (data.event === 'pusher:subscribe') {
                const channelName = data.data.channel;
                // Extract game ID from channel name (e.g., "game.123" -> "123")
                const match = channelName.match(/^game\.(\d+)$/);
                if (match) {
                    currentGameId = match[1];
                    
                    if (!gameClients.has(currentGameId)) {
                        gameClients.set(currentGameId, new Set());
                    }
                    gameClients.get(currentGameId).add(ws);
                    
                    console.log(`Client subscribed to game ${currentGameId}`);
                    
                    // Send subscription confirmation
                    ws.send(JSON.stringify({
                        event: 'pusher_internal:subscription_succeeded',
                        channel: channelName,
                        data: {}
                    }));
                    
                    // After subscription, fetch and send current game state to trigger AI moves
                    setTimeout(async () => {
                        try {
                            const response = await fetch(`http://localhost:8000/api/games/${currentGameId}`);
                            const gameState = await response.json();
                            console.log('Fetched game state after subscription:', gameState.game.current_player_index);
                            
                            // Broadcast the current state to trigger AI moves if needed
                            broadcastToGame(currentGameId, gameState);
                        } catch (error) {
                            console.error('Error fetching game state:', error);
                        }
                    }, 100);
                }
            }
        } catch (error) {
            console.error('Error handling message:', error);
        }
    });
    
    ws.on('close', () => {
        if (currentGameId && gameClients.has(currentGameId)) {
            gameClients.get(currentGameId).delete(ws);
            if (gameClients.get(currentGameId).size === 0) {
                gameClients.delete(currentGameId);
            }
        }
        console.log('WebSocket connection closed');
    });
    
    ws.on('error', (error) => {
        console.error('WebSocket error:', error);
    });
});

// Function to broadcast game updates
function broadcastToGame(gameId, data) {
    const clients = gameClients.get(gameId.toString());
    console.log(`Broadcasting to game ${gameId}, clients: ${clients ? clients.size : 0}`);
    
    if (clients) {
        const message = JSON.stringify({
            event: 'game.update',
            channel: `game.${gameId}`,
            data: data
        });
        
        console.log('Sending message:', message);
        
        clients.forEach(client => {
            if (client.readyState === WebSocket.OPEN) {
                client.send(message);
                console.log('Message sent to client');
            } else {
                console.log('Client not ready, state:', client.readyState);
            }
        });
        
        console.log(`Broadcasted to ${clients.size} clients for game ${gameId}`);
    } else {
        console.log(`No clients for game ${gameId}`);
    }
}

// Make broadcast function available globally
global.broadcastToGame = broadcastToGame;

const PORT = 8080;
server.listen(PORT, () => {
    console.log(`WebSocket server running on port ${PORT}`);
});

// Handle graceful shutdown
process.on('SIGTERM', () => {
    server.close(() => {
        console.log('WebSocket server closed');
        process.exit(0);
    });
});
