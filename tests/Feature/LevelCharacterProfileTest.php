<?php

namespace Tests\Feature;

use App\Models\Level;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Profil karakter per level (avatar + nama) dipakai di layar Achievement.
 * Semula hanya ada di file frontend (hardcode) sehingga tampilannya "UNKNOWN"
 * dan tidak bisa disunting dari admin.
 */
class LevelCharacterProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function makeLevel(string $key, string $title): Level
    {
        return Level::create([
            'level_key'       => $key,
            'title'           => $title,
            'description'     => 'Deskripsi',
            'thumbnail'       => '/backgrounds/x.png',
            'time_limit'      => 600,
            'star_thresholds' => [60, 120, 180],
            'is_locked'       => false,
            'order'           => 1,
        ]);
    }

    public function test_level_api_returns_character_profile(): void
    {
        $level = $this->makeLevel('level_1', 'Lab Misterius');
        $level->update([
            'character_avatar' => '/avatars/lab3.png',
            'character_name'   => 'Ethan',
        ]);

        $res = $this->getJson('/api/levels/level_1')->assertOk();
        $this->assertSame('/avatars/lab3.png', $res->json('character_avatar'));
        $this->assertSame('Ethan', $res->json('character_name'));

        // Daftar level (level select & layar achievement) juga membawa data ini.
        $res = $this->getJson('/api/levels')->assertOk();
        $row = collect($res->json())->firstWhere('level_key', 'level_1');
        $this->assertSame('/avatars/lab3.png', $row['character_avatar'] ?? null);
        $this->assertSame('Ethan', $row['character_name'] ?? null);
    }
}
