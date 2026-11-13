<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hotspot extends Model
{
    protected $fillable = [
        'scene_id', 'hotspot_key', 'label',
        'x', 'y', 'width', 'height',
        'cursor', 'image', 'z_index',
        'action_type', 'action_data', 'visible_data', 'order',
    ];
 
    protected $casts = [
        'action_data'  => 'array',
        'visible_data' => 'array',
    ];
 
    public function scene()
    {
        return $this->belongsTo(Scene::class);
    }
}