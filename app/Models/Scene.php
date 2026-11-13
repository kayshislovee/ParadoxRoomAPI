<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Scene extends Model
{
  protected $fillable = [
    'level_id', 'scene_key', 'label',
    'background_image', 'exit_puzzle_id',
    'char1_layer', 'char2_layer', 'char3_layer',
    'char1_sub_views', 'char2_sub_views', 'char3_sub_views',
    'scene_comic', 'comic_pages', 'highlight_hotspots',
    'intro_dialog', 'auto_switch_char', 'order',
];
protected $casts = [
    'char1_layer' => 'array', 'char2_layer' => 'array', 'char3_layer' => 'array',
    'char1_sub_views' => 'array', 'char2_sub_views' => 'array', 'char3_sub_views' => 'array',
    'scene_comic' => 'array', 'comic_pages' => 'array',
    'highlight_hotspots' => 'array', 'intro_dialog' => 'array',
];
 
    public function level()
    {
        return $this->belongsTo(Level::class);
    }
 
    public function hotspots()
    {
        return $this->hasMany(Hotspot::class);
    }
}
