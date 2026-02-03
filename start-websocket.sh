#!/bin/bash
echo "Starting WebSocket server for Rams game..."
cd /home/mac_mac/projects/rams

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    echo "Node.js is not installed. Please install Node.js first."
    exit 1
fi

# Check if ws module is installed
if [ ! -d "node_modules/ws" ]; then
    echo "Installing ws module..."
    npm install ws
fi

# Start the WebSocket server
echo "Starting WebSocket server on port 8080..."
node websocket-server.js
