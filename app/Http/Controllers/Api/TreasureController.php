<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Treasure;
use App\Models\PlayerTreasure;
use App\Models\Achievement;
use App\Models\PlayerAchievement;
use Illuminate\Http\Request;

class TreasureController extends Controller
{
    // POST /api/treasures/found
    public function found(Request $request)
    {
        $request->validate([
            'treasure_key' => 'required|exists:treasures,treasure_key',
        ]);

        if (!$request->user()) {
            return response()->json(['message' => 'Guest cannot save treasures'], 403);
        }

        $treasure = Treasure::where('treasure_key', $request->treasure_key)
            ->with('achievement')
            ->first();

        PlayerTreasure::firstOrCreate([
            'player_id'   => $request->user()->id,
            'treasure_id' => $treasure->id,
        ], ['found_at' => now()]);

        // Cek apakah semua treasure di achievement ini sudah ditemukan
        $allTreasures = Treasure::where('achievement_id', $treasure->achievement_id)->get();
        $foundCount = PlayerTreasure::where('player_id', $request->user()->id)
            ->whereIn('treasure_id', $allTreasures->pluck('id'))
            ->count();

        $achievementUnlocked = false;
        if ($foundCount >= $allTreasures->count()) {
            PlayerAchievement::firstOrCreate([
                'player_id'      => $request->user()->id,
                'achievement_id' => $treasure->achievement_id,
            ], ['unlocked_at' => now()]);
            $achievementUnlocked = true;
        }

        return response()->json([
            'message'              => 'Treasure found!',
            'achievement_unlocked' => $achievementUnlocked,
            'achievement_key'      => $achievementUnlocked
                ? $treasure->achievement->achievement_key
                : null,
        ]);
    }

    // GET /api/treasures/my
    public function myTreasures(Request $request)
    {
        if (!$request->user()) return response()->json([]);

        return response()->json(
            $request->user()->treasures->map(fn($t) => $t->treasure_key)
        );
    }
}