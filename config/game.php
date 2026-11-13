<?php

/**
 * config/game.php
 * Konfigurasi game yang bisa diubah via .env
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Hint Token Settings
    |--------------------------------------------------------------------------
    | Dipakai oleh HintConfigController → dibaca React saat game start.
    | Ubah nilai default via .env:
    |   GAME_HINT_MAX_TOKENS=5
    |   GAME_HINT_REFILL_SECONDS=60
    */
    'hint_max_tokens'     => (int) env('GAME_HINT_MAX_TOKENS', 5),
    'hint_refill_seconds' => (int) env('GAME_HINT_REFILL_SECONDS', 60),

];