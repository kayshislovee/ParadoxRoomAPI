<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Achievement extends Model
{
    protected $fillable = [
        'level_id', 'achievement_key', 'title',
        'description', 'icon', 'type',
        'is_golden', 'time_threshold', 'order',
    ];

    protected $casts = ['is_golden' => 'boolean'];

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function treasures(): HasMany
    {
        return $this->hasMany(Treasure::class);
    }
}