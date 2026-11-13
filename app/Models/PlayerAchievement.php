<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PlayerAchievement extends Model
{
    protected $fillable = ['player_id', 'achievement_id', 'unlocked_at'];
    protected $casts = ['unlocked_at' => 'datetime'];
}