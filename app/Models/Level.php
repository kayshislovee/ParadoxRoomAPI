<?php
 
// ============================================================
//  app/Models/Level.php
// ============================================================
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
 
class Level extends Model
{
    protected $fillable = [
    'level_key', 'title', 'description', 'thumbnail',
        'character_avatar', 'character_name',
    'time_limit', 'star_thresholds', 'music', 'dialog_music',
    'scene_hints', 'scene_dialogs', 'scene_comics', 'characters',
    'comic_src', 'exit_door_points', 'exit_light_pos',
    'is_locked', 'order',
];
protected $casts = [
    'star_thresholds' => 'array',
    'scene_hints' => 'array',
    'scene_dialogs' => 'array',
    'scene_comics' => 'array',
    'characters' => 'array',
    'exit_light_pos' => 'array',
    'is_locked' => 'boolean',
];
 
    public function scenes()
    {
        return $this->hasMany(Scene::class);
    }
 
    public function puzzles()
    {
        return $this->hasMany(Puzzle::class);
    }
 
    public function levelHints()
    {
        return $this->hasMany(LevelHint::class);
    }
 
    public function dialogLines()
    {
        return $this->hasMany(DialogLine::class);
    }
 

   public function achievements(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(Achievement::class)->orderBy('order');
}
public function treasures(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(Treasure::class)->orderBy('order');
}
}
 