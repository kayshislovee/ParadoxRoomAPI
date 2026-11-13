<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlayerProgress;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    // GET /api/progress — ambil semua progress milik player yang login
    public function index(Request $request)
    {
        $progress = PlayerProgress::where('player_id', $request->user()->id)->get()
            ->map(fn($p) => [
                'level_key' => $p->level_key,
                'completed' => $p->completed,
                'best_time' => $p->best_time,
                'stars'     => $p->stars,
            ]);

        return response()->json($progress);
    }

    // POST /api/progress — simpan/update progress setelah level selesai
    public function store(Request $request)
    {
        $validated = $request->validate([
            'level_key' => 'required|string',
            'completed' => 'required|boolean',
            'best_time' => 'required|integer|min:0',
            'stars'     => 'required|integer|min:1|max:3',
        ]);

        $progress = PlayerProgress::updateOrCreate(
            [
                'player_id' => $request->user()->id,
                'level_key' => $validated['level_key'],
            ],
            [
                'completed' => $validated['completed'],
                'best_time' => min($validated['best_time'],
                    PlayerProgress::where('player_id', $request->user()->id)
                        ->where('level_key', $validated['level_key'])
                        ->value('best_time') ?? $validated['best_time']
                ),
                'stars' => max($validated['stars'],
                    PlayerProgress::where('player_id', $request->user()->id)
                        ->where('level_key', $validated['level_key'])
                        ->value('stars') ?? 0
                ),
            ]
        );

        return response()->json($progress, 201);
    }

}
