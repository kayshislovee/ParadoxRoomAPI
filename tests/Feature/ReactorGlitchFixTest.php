<?php

namespace Tests\Feature;

use App\Models\Lobby;
use App\Models\LobbyGameState;
use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Glitch reactor & fake info level 7 harus bisa DIBAIKI (bukan cuma ditambah):
 *
 *  1. Satu player solve reactor -> glitch muncul untuk SEMUA pemain.
 *  2. Katsuragi (hacking device+) perbaiki glitch lewat maze -> glitch hilang
 *     untuk semua pemain, TIDAK muncul lagi.
 *  3. Fake info muncul -> Katsuragi identifikasi lewat spot-diff -> fake info
 *     reactor tsb hilang untuk semua pemain.
 */
class ReactorGlitchFixTest extends TestCase
{
    use RefreshDatabase;

    protected function makePlayer(string $username): Player
    {
        return Player::create([
            'username' => $username,
            'email'    => $username . '@test.local',
            'password' => bcrypt('secret1234'),
        ]);
    }

    protected function makeLobby(Player $host, Player $member): Lobby
    {
        $lobby = Lobby::create([
            'code'        => 'GLTCH01',
            'host_id'     => $host->id,
            'level_key'   => 'level_7',
            'status'      => 'playing',
            'max_players' => 4,
        ]);

        $lobby->players()->attach($host->id, [
            'role' => 'host', 'is_ready' => true, 'joined_at' => now(), 'character' => 'char1',
        ]);
        $lobby->players()->attach($member->id, [
            'role' => 'member', 'is_ready' => true, 'joined_at' => now(), 'character' => 'char3',
        ]);

        LobbyGameState::create(['lobby_id' => $lobby->id, 'revision' => 0]);

        return $lobby->fresh();
    }

    private function sync(Lobby $lobby, Player $as, array $payload): void
    {
        Sanctum::actingAs($as);
        $this->postJson("/api/lobby/{$lobby->code}/sync", [
            'action'  => 'reactor_state',
            'payload' => $payload,
        ])->assertOk();
    }

    public function test_glitch_appears_for_all_players_and_disappears_after_fix(): void
    {
        $host   = $this->makePlayer('glitchhost');
        $member = $this->makePlayer('glitchmember');
        $lobby  = $this->makeLobby($host, $member);

        // Player 1 selesai satu reactor -> glitch untuk semua pemain.
        $this->sync($lobby, $host, ['glitchAdd' => ['reactor']]);

        Sanctum::actingAs($member);
        $res = $this->getJson("/api/lobby/{$lobby->code}")->assertOk();
        $this->assertSame(['reactor'], $res->json('game_state.glitched_reactors'));

        // Katsuragi memperbaiki glitch (maze simple).
        $this->sync($lobby, $member, ['glitchClear' => ['reactor']]);

        // Semua pemain: glitch sudah hilang dan tidak boleh muncul lagi.
        foreach ([$host, $member] as $player) {
            Sanctum::actingAs($player);
            $res = $this->getJson("/api/lobby/{$lobby->code}")->assertOk();
            $this->assertSame([], $res->json('game_state.glitched_reactors'));
            $this->assertSame(['reactor'], $res->json('game_state.glitched_cleared'));
        }
    }

    public function test_cleared_glitch_is_not_re_added_by_stale_broadcast(): void
    {
        $host   = $this->makePlayer('glitchhost2');
        $member = $this->makePlayer('glitchmember2');
        $lobby  = $this->makeLobby($host, $member);

        $this->sync($lobby, $host, ['glitchAdd' => ['reactor']]);
        $this->sync($lobby, $member, ['glitchClear' => ['reactor']]);

        // Event lama (broadcast yang telat) tidak boleh membangkitkan glitch lagi.
        $this->sync($lobby, $host, ['glitchAdd' => []]);

        Sanctum::actingAs($host);
        $res = $this->getJson("/api/lobby/{$lobby->code}")->assertOk();
        $this->assertSame([], $res->json('game_state.glitched_reactors'));
    }

    public function test_fake_info_is_shared_and_can_be_cleared_per_reactor(): void
    {
        $host   = $this->makePlayer('fakehost');
        $member = $this->makePlayer('fakemember');
        $lobby  = $this->makeLobby($host, $member);

        // Fake info muncul untuk semua pemain.
        $this->sync($lobby, $host, ['fakeAdd' => ['reactor', 'cooling']]);

        Sanctum::actingAs($member);
        $res = $this->getJson("/api/lobby/{$lobby->code}")->assertOk();
        $this->assertSame(['reactor', 'cooling'], $res->json('game_state.fake_reactors'));
        $this->assertTrue((bool) $res->json('game_state.fake_info_active'));

        // Katsuragi mengidentifikasi fake info pada satu reactor saja.
        $this->sync($lobby, $member, ['fakeClear' => ['reactor']]);

        $res = $this->getJson("/api/lobby/{$lobby->code}")->assertOk();
        $this->assertSame(['cooling'], $res->json('game_state.fake_reactors'));
        $this->assertTrue((bool) $res->json('game_state.fake_info_active'));

        // Reactor terakhir -> fake info selesai untuk semua pemain.
        $this->sync($lobby, $member, ['fakeClear' => ['cooling']]);

        $res = $this->getJson("/api/lobby/{$lobby->code}")->assertOk();
        $this->assertSame([], $res->json('game_state.fake_reactors'));
        $this->assertFalse((bool) $res->json('game_state.fake_info_active'));
        $this->assertSame(['reactor', 'cooling'], $res->json('game_state.fake_cleared'));
    }
}
