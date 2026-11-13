<?php

use App\Models\Lobby;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Channel privat untuk data realtime berfrekuensi tinggi (posisi karakter
 * di mini-game maze, mis. Brain Maze).
 *
 * Kenapa privat + whisper?
 * Sebelumnya posisi dikirim lewat HTTP POST /lobby/{code}/input tiap ~66ms.
 * Setiap request menulis DB + broadcast seluruh game state (~200ms karena
 * ShouldBroadcastNow), sementara API dev hanya punya satu worker. Akibatnya
 * antrean menumpuk dan gerakan teammate jadi lambat + patah-patah.
 * Lewat whisper, posisi cukup lewat WebSocket yang sudah terbuka.
 *
 * Hanya anggota lobby yang boleh masuk channel ini.
 */
Broadcast::channel('maze.{code}', function ($user, string $code) {
    return Lobby::where('code', strtoupper($code))
        ->whereHas('players', fn ($q) => $q->where('players.id', $user->id))
        ->exists();
});
