<?php

use App\Http\Controllers\AiController;
use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\Route;

Route::post('/games', [GameController::class, 'store']);
Route::get('/games/{game}', [GameController::class, 'show']);
Route::post('/games/{game}/exchange', [GameController::class, 'exchange']);
Route::post('/games/{game}/move', [GameController::class, 'move']);
Route::post('/games/{game}/participation', [GameController::class, 'participation']);
Route::post('/games/{game}/declare-jacks', [GameController::class, 'declareJacks']);
Route::post('/games/{game}/declare-partiya', [GameController::class, 'declarePartiya']);
Route::post('/games/{game}/ai-play', [AiController::class, 'playTurn']);
