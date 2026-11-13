<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Puzzle extends Model
{
   protected $fillable = [
    'level_id', 'puzzle_key', 'type', 'title',
    'description', 'answer', 'symbol_answer', 'extra_data',
    'max_digits', 'pattern_size', 'clue_ids',
];
protected $casts = [
    'clue_ids' => 'array',
    'symbol_answer' => 'array',
    'extra_data' => 'array',
];
 
    public function level()
    {
        return $this->belongsTo(Level::class);
    }
}