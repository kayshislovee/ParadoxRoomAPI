<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class HintConfigController extends Controller
{
    /**
     * GET /api/hint-config
     * Kembalikan konfigurasi hint token yang bisa diatur admin.
     * Nilai diambil dari config/game.php supaya admin bisa override via .env.
     */
    public function show(): JsonResponse
    {
        return response()->json([
            'max_tokens'       => config('game.hint_max_tokens', 5),
            'refill_seconds'   => config('game.hint_refill_seconds', 60),
        ]);
    }
}