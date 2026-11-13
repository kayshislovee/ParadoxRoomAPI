<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LobbyGameState extends Model
{
    protected $fillable = [
        'lobby_id',
        'solved_puzzles',
        'used_hotspots',
        'found_treasures',
        'inventory',
        'current_scene_id',
        'time_elapsed',
        'finish_type',
        'puzzle_inputs',
        'shared_hint_tokens',
        'shared_hints_used',
        'last_input_by',      // ← siapa yang terakhir input puzzle
        'released_character', // 'char1' or 'char2'
        'scene5_character',  // 'char3' or 'char1' or 'char2'
        'revision',          // counter monotonik untuk urutan event
        'character_inventories',
        'discovered_clues',
        'last_input_at',
        'timer_paused',
        'glitched_reactors',   // id reactor yang sedang glitch (shared)
        'glitched_cleared',    // id reactor yang glitch-nya sudah diperbaiki
        'fake_reactors',       // id reactor yang sedang menampilkan fake info
        'fake_cleared',        // id reactor yang fake info-nya sudah teridentifikasi
        'fake_info_active',    // fake info sudah muncul (shared)
        'any_reactor_solved',  // ada reactor yang sudah diselesaikan (shared)
        'pod_logged_in',       // sudah ada player yang login ke pod (shared)
    ];

    protected $casts = [
        'solved_puzzles'        => 'array',
        'used_hotspots'         => 'array',
        'found_treasures'       => 'array',
        'inventory'             => 'array',
        'puzzle_inputs'         => 'array',
        'character_inventories' => 'array',
        'discovered_clues'      => 'array',
        'shared_hint_tokens'    => 'integer',
        'shared_hints_used'     => 'integer',
        'revision'              => 'integer',
        'last_input_at'         => 'datetime',
        'timer_paused'          => 'boolean',
        'glitched_reactors'     => 'array',
        'glitched_cleared'      => 'array',
        'fake_reactors'         => 'array',
        'fake_cleared'          => 'array',
        'fake_info_active'      => 'boolean',
        'any_reactor_solved'    => 'boolean',
        'pod_logged_in'         => 'boolean',
    ];

    public function lobby()
    {
        return $this->belongsTo(Lobby::class);
    }
}