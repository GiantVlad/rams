# Rams Card Game Project

## Project Overview

This project is a real-time multiplayer card game called "Rams". It is a mono-repo structure containing a Laravel backend, a Vue.js frontend, and a custom Node.js WebSocket server for real-time game state synchronization.

**Key Features:**
*   4-player trick-taking game (1 Human vs. 3 AI).
*   Real-time state updates via WebSockets.
*   Domain-Driven Design (DDD) in the Laravel backend.
*   Reactive UI with Vue 3 and Pinia.

## Architecture

The project is divided into three main components:

1.  **Backend (`api/`)**:
    *   **Framework**: Laravel 12 (PHP 8.2+).
    *   **Database**: MySQL (or SQLite for dev).
    *   **Role**: Manages game state, business logic, persistence, and broadcasting events to the WebSocket server.
    *   **Key Directories**:
        *   `app/Domain/Rams`: Core game logic and domain models.
        *   `app/Events`: Classes that trigger WebSocket broadcasts.
        *   `app/Services/Rams`: Service layer for game operations.

2.  **Frontend (`web/`)**:
    *   **Framework**: Vue 3 (Composition API).
    *   **Build Tool**: Vite.
    *   **State Management**: Pinia.
    *   **Role**: Renders the game interface and handles user interactions. Connects to the WebSocket server to receive updates.

3.  **WebSocket Server (`/`)**:
    *   **Runtime**: Node.js.
    *   **Role**: Acts as a relay between the backend and frontend. It listens for HTTP broadcasts from the Laravel backend and forwards them to connected WebSocket clients (frontend).

## Building and Running

The application requires three separate processes running concurrently.

### Prerequisites
*   PHP 8.2+ & Composer
*   Node.js 18+ & npm
*   MySQL 8.0+

### 1. Backend (API)
```bash
cd api
cp .env.example .env
# Configure .env with your database credentials
composer install
php artisan key:generate
php artisan migrate
php artisan serve
```
*API will run at `http://localhost:8000`*

### 2. WebSocket Server
```bash
# From project root
npm install
./start-websocket.sh
```
*Server will run at `ws://localhost:8080`*

### 3. Frontend (Web)
```bash
cd web
npm install
npm run dev
```
*Frontend will run at `http://localhost:5173`*

## Development Conventions

*   **Game Logic**: Complex game rules and state transitions are handled in the Backend (`api/app/Domain/Rams`).
*   **Real-time**: The backend sends HTTP POST requests to the Node.js WebSocket server to broadcast events.
*   **AI**: AI logic resides in the backend to ensure a single source of truth for game state.
*   **Documentation**: See `ai_dev/` for detailed development stages and game rules.

## Key Files & Directories

*   `api/`: Laravel Backend application.
*   `web/`: Vue.js Frontend application.
*   `websocket-server.js`: Custom WebSocket server implementation.
*   `start-websocket.sh`: Helper script to launch the WebSocket server.
*   `ai_dev/`: Project documentation and development logs.
