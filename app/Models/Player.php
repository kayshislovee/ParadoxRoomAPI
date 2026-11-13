<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Player extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = ['username', 'email', 'password', 'avatar','bio'];
    protected $hidden   = ['password'];
    
public function progress()
{
    return $this->hasMany(PlayerProgress::class);
}

public function getOrCreateProgress(string $levelKey)
{
    return $this->progress()
        ->firstOrCreate([
            'level_key' => $levelKey,
        ], [
            'completed' => false,
            'best_time' => 0,
            'stars'     => 0,
        ]);
}
public function achievements() {
    return $this->belongsToMany(Achievement::class, 'player_achievements')
        ->withPivot('unlocked_at')->withTimestamps();
}
public function treasures() {
    return $this->belongsToMany(Treasure::class, 'player_treasures')
        ->withPivot('found_at')->withTimestamps();
}
public function totalPlayedTime(): Attribute
{
    return Attribute::make(
        get: function () {
            $cacheKey = "total_time:{$this->id}";
            
            return Cache::remember($cacheKey, 60, function () {
                // Hitung total best_time dari semua level (dalam detik)
                $totalSeconds = $this->progress()
                    ->where('completed', true)
                    ->sum('best_time');

                // Format sebagai "X jam Y menit" (bulatkan menit)
                $hours   = floor($totalSeconds / 3600);
                $minutes = round(($totalSeconds % 3600) / 60);

                // Jika totalnya kurang dari 1 menit, tampilkan "Kurang dari 1 menit"
                if ($totalSeconds < 60) {
                    return "Kurang dari 1 menit";
                }

                $parts = [];
                if ($hours > 0) {
                    $parts[] = "{$hours} jam";
                }
                if ($minutes > 0) {
                    $parts[] = "{$minutes} menit";
                }

                return implode(' ', $parts) ?: "Kurang dari 1 menit";
            });
        },
    );
}

}