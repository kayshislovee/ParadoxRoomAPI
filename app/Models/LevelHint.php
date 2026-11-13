<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LevelHint extends Model
{
    protected $fillable = ['level_id', 'text', 'order'];
 
    public function level()
    {
        return $this->belongsTo(Level::class);
    }
}