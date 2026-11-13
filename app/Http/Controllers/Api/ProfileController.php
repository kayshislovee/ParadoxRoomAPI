<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlayerProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    // GET /api/profile
    public function show(Request $request)
    {
        $player = $request->user();
        $progress = PlayerProgress::where('player_id', $player->id)
            ->where('completed', true)
            ->orderBy('level_key')
            ->get(['level_key', 'stars', 'best_time']);

        return response()->json([
            'id'               => $player->id,
            'username'         => $player->username,
            'email'            => $player->email,
            'avatar'           => $player->avatar
                                    ? asset('storage/' . $player->avatar)
                                    : null,
            'bio'              => $player->bio,
            'completed_levels' => $progress,
        ]);
    }

    // PUT /api/profile
    public function update(Request $request)
    {
        $request->validate([
            'username' => 'sometimes|string|max:30|unique:players,username,' . $request->user()->id,
            'bio'      => 'sometimes|nullable|string|max:200',
        ]);

        $player = $request->user();
        $player->update($request->only(['username', 'bio']));

        return response()->json([
            'username' => $player->username,
            'bio'      => $player->bio,
        ]);
    }

    // POST /api/profile/avatar
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|max:2048',
        ]);

        $player = $request->user();

        // Hapus avatar lama
        if ($player->avatar) {
            Storage::disk('public')->delete($player->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $player->update(['avatar' => $path]);

        return response()->json([
            'avatar' => asset('storage/' . $path),
        ]);
    }
}