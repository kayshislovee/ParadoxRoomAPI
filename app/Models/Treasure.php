<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Treasure extends Model
{
    protected $fillable = [
    'level_id', 'achievement_id', 'treasure_key', 'name', 'description', 'scene_id',
    'x', 'y', 'width', 'height', 'image', 'order',
];

    public function level(): BelongsTo { return $this->belongsTo(Level::class); }
    public function achievement(): BelongsTo { return $this->belongsTo(Achievement::class); }
}