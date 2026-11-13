<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PlayerTreasure extends Model
{
    protected $fillable = ['player_id', 'treasure_id', 'found_at'];
    protected $casts = ['found_at' => 'datetime'];
}