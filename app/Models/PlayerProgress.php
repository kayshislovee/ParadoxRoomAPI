<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerProgress extends Model
{
    protected $table = 'player_progress';

    protected $fillable = [
        'player_id',
        'level_key',
        'completed',
        'best_time',
        'stars',
    ];

    protected $casts = [
        'completed' => 'boolean',
    ];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}