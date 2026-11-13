<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Lobby extends Model
{
    protected $fillable = ['code', 'host_id', 'level_key', 'status', 'max_players'];

    // Generate kode unik 6 karakter otomatis
    public static function generateCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    public function host()
    {
        return $this->belongsTo(Player::class, 'host_id');
    }

    public function players()
    {
        return $this->belongsToMany(Player::class, 'lobby_players')
            ->withPivot('role', 'is_ready', 'joined_at', 'character', 'scene5_character')
            ->withTimestamps();
    }

    public function gameState()
    {
        return $this->hasOne(LobbyGameState::class);
    }

    public function isFull(): bool
    {
        return $this->players()->count() >= $this->max_players;
    }
}