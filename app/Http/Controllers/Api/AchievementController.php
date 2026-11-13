<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\PlayerAchievement;
use Illuminate\Http\Request;

class AchievementController extends Controller
{
    // GET /api/achievements?level_key=level_1
    public function index(Request $request)
    {
        $query = Achievement::with('level');
        if ($request->filled('level_key')) {
            $query->whereHas('level', fn($q) =>
                $q->where('level_key', $request->level_key)
            );
        }
        return response()->json($query->orderBy('order')->get()->map(fn($a) => [
            'id'             => $a->id,
            'achievement_key'=> $a->achievement_key,
            'title'          => $a->title,
            'description'    => $a->description,
            'icon'           => $a->icon,
            'type'           => $a->type,
            'is_golden'      => $a->is_golden,
            'time_threshold' => $a->time_threshold,
            'level_id'       => $a->level_id,
        ]));
    }

    // POST /api/achievements/unlock
    public function unlock(Request $request)
    {
        $request->validate([
            'achievement_key' => 'required|exists:achievements,achievement_key',
        ]);

        if (!$request->user()) {
            return response()->json(['message' => 'Guest cannot unlock achievements'], 403);
        }

        $achievement = Achievement::where('achievement_key', $request->achievement_key)->first();

        PlayerAchievement::firstOrCreate([
            'player_id'      => $request->user()->id,
            'achievement_id' => $achievement->id,
        ], ['unlocked_at' => now()]);

        return response()->json(['message' => 'Achievement unlocked!']);
    }

    // GET /api/achievements/my
    public function myAchievements(Request $request)
    {
        if (!$request->user()) return response()->json([]);

        return response()->json(
            $request->user()->achievements()
                ->withPivot('unlocked_at')
                ->orderBy('order')
                ->get()
                ->map(fn($a) => [
                    'id' => $a->id,
                    'achievement_key' => $a->achievement_key,
                    'title' => $a->title,
                    'description' => $a->description,
                    'icon' => $a->icon,
                    'type' => $a->type,
                    'is_golden' => $a->is_golden,
                    'time_threshold' => $a->time_threshold,
                    'order' => $a->order,
                    'unlocked_at' => $a->pivot->unlocked_at,
                ])
        );
    }
}