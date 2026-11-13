<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LevelController;
use App\Http\Controllers\Api\HintConfigController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\AchievementController;
use App\Http\Controllers\Api\TreasureController;
use App\Http\Controllers\Api\LobbyController;
// ──────────────────────────────────────────────
//  LEVELS  (beserta scenes, hotspots, puzzles,
//           hints, dialog lines di dalamnya)
// ──────────────────────────────────────────────
Route::get('/levels', [LevelController::class, 'index']);
Route::get('/levels/{level_key}', [LevelController::class, 'show']);
Route::post('/lobby/{code}/exit-beacon', [LobbyController::class, 'exitBeacon']);

// ──────────────────────────────────────────────
//  HINT CONFIG  (max token & interval refill)
// ──────────────────────────────────────────────
Route::get('/hint-config', [HintConfigController::class, 'show']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',    [AuthController::class, 'me']);
     Route::get('/progress', [ProgressController::class, 'index']);
    Route::post('/progress', [ProgressController::class, 'store']);
    
Route::get('/profile', [ProfileController::class, 'show']);
Route::put('/profile', [ProfileController::class, 'update']);
Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar']);
});

Route::get('/achievements', [AchievementController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/achievements/unlock', [AchievementController::class, 'unlock']);
    Route::get('/achievements/my',      [AchievementController::class, 'myAchievements']);
    Route::post('/treasures/found',     [TreasureController::class, 'found']);
    Route::get('/treasures/my',         [TreasureController::class, 'myTreasures']);
    Route::post('/lobby', [LobbyController::class, 'create']);  // ← pastikan di sini
    Route::post('/lobby/join', [LobbyController::class, 'join']);
    Route::get('/lobby/my', [LobbyController::class, 'my']);    // ← dulu
Route::get('/lobby/{code}', [LobbyController::class, 'show']); // ← habis ini
    Route::post('/lobby/{code}/start', [LobbyController::class, 'start']);
    Route::post('/lobby/{code}/sync', [LobbyController::class, 'sync']);
    Route::post('/lobby/{code}/leave', [LobbyController::class, 'leave']);
    Route::post('/lobby/{code}/exit', [LobbyController::class, 'exit']);
    Route::post('/lobby/{code}/lose', [LobbyController::class, 'lose']);
    Route::post('/lobby/{code}/complete', [LobbyController::class, 'complete']);
    Route::post('/lobby/{code}/input',    [LobbyController::class, 'input']);
Route::post('/lobby/{code}/hint',     [LobbyController::class, 'useHint']);
Route::post('/lobby/{code}/chat', [LobbyController::class, 'chat']);

Route::post('/lobby/{code}/reset', [LobbyController::class, 'reset']);
Route::post('/lobby/{code}/force-end', [LobbyController::class, 'forceEnd']);
Route::post('/lobby/{code}/signal', [LobbyController::class, 'signal']);
    Route::post('/lobby/{code}/character', [LobbyController::class, 'assignCharacter']);
    Route::post('/lobby/{code}/release', [LobbyController::class, 'releaseCharacter']);
    Route::post('/lobby/{code}/pod-release', [LobbyController::class, 'podReleaseCharacter']);
    Route::post('/lobby/{code}/select-scene5-char', [LobbyController::class, 'selectScene5Character']);
});

