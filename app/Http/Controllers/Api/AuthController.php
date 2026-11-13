<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\Lobby;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:30|unique:players',
            'email'    => 'required|email|unique:players',
            'password' => 'required|min:6',
        ]);

        $player = Player::create([
            'username' => $request->username,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $player->createToken('game-token')->plainTextToken;

        return response()->json([
            'player' => [
                'id'       => $player->id,
                'username' => $player->username,
                'email'    => $player->email,
            ],
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $player = Player::where('email', $request->email)->first();

        if (!$player || !Hash::check($request->password, $player->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        $token = $player->createToken('game-token')->plainTextToken;

        return response()->json([
            'player' => [
                'id'       => $player->id,
                'username' => $player->username,
                'email'    => $player->email,
            ],
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        Lobby::whereHas('players', fn($q) => $q->where('player_id', $user->id))
        ->whereIn('status', ['waiting', 'playing', 'finished'])
        ->each(fn($lobby) => $lobby->players()->detach($user->id));

        return response()->json([
            'id'       => $user->id,
            'username' => $user->username,
            'email'    => $user->email,
        ]);
    }
    
}