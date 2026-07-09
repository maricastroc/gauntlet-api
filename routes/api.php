<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MatchResultController;
use App\Http\Controllers\StandingsController;
use Illuminate\Support\Facades\Route;

// Autenticação
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Leitura pública (visão torcedor)
Route::get('/groups/{group}/standings', [StandingsController::class, 'show']);

// Ações do organizador
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/matches/{fixture}/result', [MatchResultController::class, 'update']);
});
