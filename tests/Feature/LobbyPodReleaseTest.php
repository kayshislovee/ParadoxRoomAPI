<?php

namespace Tests\Feature;

use App\Models\Lobby;
use App\Models\LobbyGameState;
use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Regression test untuk bug pod level 7:
 *  1. Ganti karakter di lobby TIDAK boleh membuat pod terlihat "sudah dibebaskan".
 *  2. Karakter hanya bisa dibebaskan pod satu kali (pod-release).
 *  3. Dua pemain tidak boleh memakai karakter yang sama (lobby & scene 5).
 */
class LobbyPodReleaseTest extends TestCase
{
    use RefreshDatabase;

    protected function makePlayer(string $username): Player
    {
        return Player::create([
            'username'   => $username,
            'email'      => $username . '@test.local',
            'password'   => bcrypt('secret1234'),
        ]);
    }

    protected function makeLobby(Player $host, Player $member): Lobby
    {
        $lobby = Lobby::create([
            'code'        => 'TEST01',
            'host_id'     => $host->id,
            'level_key'   => 'level_7',
            'status'      => 'playing',
            'max_players' => 4,
        ]);

        $lobby->players()->attach($host->id, [
            'role' => 'host', 'is_ready' => true, 'joined_at' => now(), 'character' => 'char1',
        ]);
        $lobby->players()->attach($member->id, [
            'role' => 'member', 'is_ready' => true, 'joined_at' => now(), 'character' => 'char2',
        ]);

        LobbyGameState::create(['lobby_id' => $lobby->id, 'revision' => 0]);

        return $lobby->fresh();
    }

    public function test_lobby_character_swap_does_not_release_pod(): void
    {
        $host   = $this->makePlayer('host1');
        $member = $this->makePlayer('member1');
        $lobby  = $this->makeLobby($host, $member);

        Sanctum::actingAs($host);

        // Ganti karakter di lobby -> endpoint /release
        $this->postJson("/api/lobby/{$lobby->code}/release", ['character' => 'char1'])
            ->assertOk();

        // released_character TIDAK boleh terisi (pod belum solved)
        $this->assertNull(
            LobbyGameState::where('lobby_id', $lobby->id)->value('released_character'),
            'Ganti karakter di lobby tidak boleh menandai pod sebagai dibebaskan.'
        );

        // Karakter lama di-release, jadi bisa assign ulang
        $this->assertNull($lobby->players()->where('players.id', $host->id)->value('character'));
    }

    public function test_assign_character_is_unique_per_player(): void
    {
        $host   = $this->makePlayer('host2');
        $member = $this->makePlayer('member2');
        $lobby  = $this->makeLobby($host, $member);

        // Host sudah pegang char1; member mencoba ambil char1 juga.
        Sanctum::actingAs($member);
        $this->postJson("/api/lobby/{$lobby->code}/character", ['character' => 'char1'])
            ->assertStatus(409);

        // Member tetap pegang char2
        $this->assertSame(
            'char2',
            $lobby->players()->where('players.id', $member->id)->value('character')
        );
    }

    public function test_pod_release_only_once_and_sets_released_character(): void
    {
        $host   = $this->makePlayer('host3');
        $member = $this->makePlayer('member3');
        $lobby  = $this->makeLobby($host, $member);

        Sanctum::actingAs($host);
        $this->postJson("/api/lobby/{$lobby->code}/pod-release", ['character' => 'char2'])
            ->assertOk();

        $this->assertSame(
            'char2',
            LobbyGameState::where('lobby_id', $lobby->id)->value('released_character')
        );

        // Tidak boleh ada karakter kedua yang dibebaskan.
        Sanctum::actingAs($member);
        $this->postJson("/api/lobby/{$lobby->code}/pod-release", ['character' => 'char1'])
            ->assertStatus(409);

        $this->assertSame(
            'char2',
            LobbyGameState::where('lobby_id', $lobby->id)->value('released_character')
        );
    }

    public function test_reactor_glitch_is_shared_between_players(): void
    {
        $host   = $this->makePlayer('host6');
        $member = $this->makePlayer('member6');
        $lobby  = $this->makeLobby($host, $member);

        // Player 1 selesai reactor -> state glitch dikirim ke lobby.
        Sanctum::actingAs($host);
        $this->postJson("/api/lobby/{$lobby->code}/sync", [
            'action'  => 'reactor_state',
            'payload' => [
                'glitchAdd'         => ['reactor', 'cooling'],
                'fakeAdd'           => ['reactor'],
                'anyReactorSolved'  => true,
            ],
        ])->assertOk();

        $state = LobbyGameState::where('lobby_id', $lobby->id)->first();

        // Player 2 harus melihat glitch yang sama (dari state bersama).
        $this->assertSame(['reactor', 'cooling'], $state->glitched_reactors);
        $this->assertTrue((bool) $state->fake_info_active);
        $this->assertTrue((bool) $state->any_reactor_solved);

        // State bersama juga terlihat lewat endpoint lobby.
        Sanctum::actingAs($member);
        $res = $this->getJson("/api/lobby/{$lobby->code}")->assertOk();
        $this->assertSame(['reactor', 'cooling'], $res->json('game_state.glitched_reactors'));
        $this->assertTrue((bool) $res->json('game_state.fake_info_active'));
    }

    public function test_pod_login_is_counted_for_all_players(): void
    {
        $host   = $this->makePlayer('host7');
        $member = $this->makePlayer('member7');
        $lobby  = $this->makeLobby($host, $member);

        // Hanya SATU pemain yang login ke pod.
        Sanctum::actingAs($host);
        $this->postJson("/api/lobby/{$lobby->code}/sync", [
            'action'  => 'pod_login',
            'payload' => [],
        ])->assertOk();

        $state = LobbyGameState::where('lobby_id', $lobby->id)->first();
        $this->assertTrue((bool) $state->pod_logged_in);

        // Player lain ikut dianggap sudah login.
        Sanctum::actingAs($member);
        $res = $this->getJson("/api/lobby/{$lobby->code}")->assertOk();
        $this->assertTrue((bool) $res->json('game_state.pod_logged_in'));
    }
    public function test_leaving_player_does_not_count_as_level_won(): void
    {
        $host   = $this->makePlayer('host5');
        $member = $this->makePlayer('member5');
        $lobby  = $this->makeLobby($host, $member);

        // Member keluar duluan, host menyusul.
        Sanctum::actingAs($member);
        $this->postJson("/api/lobby/{$lobby->code}/leave")->assertOk();

        Sanctum::actingAs($host);
        $this->postJson("/api/lobby/{$lobby->code}/leave")->assertOk();

        $state = LobbyGameState::where('lobby_id', $lobby->id)->first();

        // Level TIDAK boleh tercatat sebagai 'win' -> tidak ada bintang,
        // tidak ada win screen untuk pemain yang tinggal.
        $this->assertNotSame('win', $state->finish_type);
        $this->assertSame('abandon', $state->finish_type);
        $this->assertSame('finished', $lobby->fresh()->status);
    }
    public function test_scene5_character_must_be_unique_and_allowed(): void
    {
        $host   = $this->makePlayer('host4');
        $member = $this->makePlayer('member4');
        $lobby  = $this->makeLobby($host, $member);

        // Pod belum dibebaskan -> char1/char2 belum boleh dipakai di scene 5.
        Sanctum::actingAs($host);
        $this->postJson("/api/lobby/{$lobby->code}/select-scene5-char", ['character' => 'char1'])
            ->assertStatus(409);

        // Pod dibebaskan char2 -> pilihan valid: char3 atau char2.
        $this->postJson("/api/lobby/{$lobby->code}/pod-release", ['character' => 'char2'])->assertOk();

        $this->postJson("/api/lobby/{$lobby->code}/select-scene5-char", ['character' => 'char3'])->assertOk();

        // Member tidak boleh memakai char3 yang sudah dipakai host.
        Sanctum::actingAs($member);
        $this->postJson("/api/lobby/{$lobby->code}/select-scene5-char", ['character' => 'char3'])
            ->assertStatus(409);

        // Tapi boleh memakai char2 (yang dibebaskan pod).
        $this->postJson("/api/lobby/{$lobby->code}/select-scene5-char", ['character' => 'char2'])
            ->assertOk();

        $this->assertSame(
            'char2',
            $lobby->players()->where('players.id', $member->id)->value('scene5_character')
        );
    }
}
