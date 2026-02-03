Youyou# Rams Card Game

A multiplayer card game implementation with real-time updates, featuring a 4-player setup with AI opponents.

## Game Overview

Rams is a trick-taking card game where players compete to win tricks and manage their pile points. The game features:
- 4 players (1 human + 3 AI)
- 5 cards per player
- Exchange phase for card swapping
- Play phase with trump suits
- Jacks declarations for special scoring
- Real-time WebSocket updates

## Technology Stack

### Backend
- **Laravel 12** - PHP Framework
- **MySQL** - Database
- **Node.js** - WebSocket server
- **Domain-Driven Design** - Architecture pattern

### Frontend
- **Vue 3** - JavaScript Framework
- **Pinia** - State Management
- **Vite** - Build Tool
- **WebSocket API** - Real-time communication

## Prerequisites

- PHP 8.2+
- Node.js 18+
- MySQL 8.0+
- Composer
- npm/yarn

## Installation & Setup

### 1. Clone the Repository
```bash
git clone <repository-url>
cd rams
```

### 2. Backend Setup

#### Install PHP Dependencies
```bash
cd api
composer install
```

#### Configure Environment
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` file with your database settings:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rams
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

#### Run Database Migrations
```bash
php artisan migrate
```

### 3. Frontend Setup

#### Install Node.js Dependencies
```bash
cd web
npm install
```

### 4. WebSocket Server Setup

#### Install WebSocket Dependencies
```bash
cd /home/mac_mac/projects/rams
npm install ws
```

## Running the Application

You'll need 3 separate terminals to run all components:

### Terminal 1: WebSocket Server
```bash
cd /home/mac_mac/projects/rams
./start-websocket.sh
```
This starts the WebSocket server on port 8080 for real-time updates.

### Terminal 2: Laravel API Server
```bash
cd /home/mac_mac/projects/rams/api
php artisan serve
```
The API will be available at `http://localhost:8000`

### Terminal 3: Frontend Development Server
```bash
cd /home/mac_mac/projects/rams/web
npm run dev
```
The frontend will be available at `http://localhost:5173`

## How to Play

### Starting a Game
1. Open your browser and navigate to `http://localhost:5173`
2. Click "New Game" to start a new game session
3. The game will automatically deal 5 cards to each player

### Game Phases

#### 1. Exchange Phase
- **Objective**: Swap unwanted cards for new ones from the deck
- **How to Play**:
  - Select cards you want to discard (they'll be highlighted)
  - Click "Submit Exchange" to swap them
  - AI players will automatically exchange 0 cards
- **Visual Feedback**: Watch the exchange status update in real-time

#### 2. Play Phase
- **Objective**: Win tricks by playing the highest cards
- **Rules**:
  - Player to the left of dealer leads first trick
  - Winner of previous trick leads next
  - Must follow suit if possible
  - If you can't follow suit and have trump, you must play trump
  - Otherwise, you can play any card
- **Trump**: The trump suit beats all other suits

### Special Features

#### Jacks Declaration
- If you have two Jacks of the same suit, you can declare "Jacks"
- This reduces your pile from 25 to 5 points
- Can only be declared once per round

#### Scoring
- Each trick won deducts 1 point from your pile
- Jacks declaration reduces pile to 5 points
- Game ends when a player reaches 0 pile points

### UI Elements

#### Left Column
- **Players Table**: Shows all players and their current pile points
- **Current Round Points**: Displays tricks won and Jacks declared this round

#### Right Column
- **Current Turn**: Shows whose turn it is with a spinner for AI
- **Trump Card**: Displays the current trump suit
- **Exchange Section**: Shows exchange status and allows card swapping
- **Current Trick**: Shows cards played in the current trick
- **Your Hand**: Your playable cards (click to play)

## Development Features

### Real-time Updates
- All game actions are broadcast via WebSocket
- Watch AI players make moves in real-time
- Exchange progress shows with proper delays
- No manual refresh needed

### AI Players
- 3 AI opponents with basic strategy
- AI respects all game rules
- Automatic card exchanges and plays
- Realistic turn delays

### Visual Design
- Wood texture background in play area
- Color-coded card suits (red for hearts/diamonds)
- Responsive layout for different screen sizes
- Smooth animations and transitions

## API Endpoints

### Games
- `POST /api/games` - Create new game
- `GET /api/games/{id}` - Get game state
- `POST /api/games/{id}/exchange` - Exchange cards
- `POST /api/games/{id}/play` - Play a card
- `POST /api/games/{id}/declare-jacks` - Declare Jacks

### WebSocket Events
- `game.created` - New game started
- `exchange.started` - Player began exchanging
- `exchange.completed` - Player finished exchanging
- `card.played` - Card played in trick

## Troubleshooting

### Common Issues

#### WebSocket Connection Failed
- Ensure WebSocket server is running on port 8080
- Check firewall settings
- Verify no other service is using port 8080

#### Database Connection Error
- Verify MySQL is running
- Check database credentials in `.env`
- Ensure database exists and migrations are run

#### Frontend Not Loading
- Ensure all three servers are running
- Check browser console for errors
- Verify npm dependencies are installed

#### Cards Not Updating After Exchange
- Check Laravel logs for errors: `storage/logs/laravel.log`
- Verify WebSocket server is receiving broadcast requests
- Refresh the page to reconnect

### Debug Mode

#### Enable Debug Logging
```bash
# In Laravel .env
APP_DEBUG=true
LOG_LEVEL=debug
```

#### View Logs
```bash
# Laravel logs
tail -f api/storage/logs/laravel.log

# WebSocket server logs (visible in terminal)
```

## Project Structure

```
rams/
├── api/                    # Laravel backend
│   ├── app/
│   │   ├── Domain/Rams/    # Domain logic
│   │   ├── Events/         # Event classes
│   │   ├── Http/           # Controllers
│   │   ├── Models/         # Eloquent models
│   │   └── Services/       # Business logic
│   ├── config/             # Configuration files
│   ├── database/           # Migrations and seeds
│   └── routes/             # API routes
├── web/                    # Vue.js frontend
│   ├── src/
│   │   ├── stores/         # Pinia stores
│   │   ├── websocket.js    # WebSocket client
│   │   └── App.vue         # Main component
│   └── public/             # Static assets
├── ai_dev/                 # Development documentation
├── websocket-server.js     # Node.js WebSocket server
├── package.json           # Node.js dependencies
└── start-websocket.sh     # WebSocket startup script
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open-source and available under the [MIT License](LICENSE).

## Support

For issues and questions:
- Check the troubleshooting section
- Review the development documentation in `ai_dev/`
- Create an issue in the repository with detailed information
