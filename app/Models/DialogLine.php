<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DialogLine extends Model
{
    protected $fillable = [
        'level_id', 'type', 'character', 'avatar',
        'avatar_left', 'avatar_right',
        'avatar_left_style', 'avatar_right_style',
        'text', 'side', 'is_system', 'order',
    ];
 
    protected $casts = [
        'avatar_left_style'  => 'array',
        'avatar_right_style' => 'array',
        'is_system'          => 'boolean',
    ];
 
    public function level()
    {
        return $this->belongsTo(Level::class);
    }
}