<?php

namespace Tests\Feature;

use App\Models\Lobby;
use App\Models\LobbyGameState;
use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Channel privat `maze.{code}` dipakai untuk sinkronisasi POSISI karakter
 * realtime (whisper) di mini-game maze. Hanya anggota lobby yang boleh masuk.
 *
 * Catatan: phpunit menyetel BROADCAST_CONNECTION=null, jadi endpoint
 * /api/broadcasting/auth tidak bisa menghasilkan signature Pusher di test.
 * Yang diuji di sini adalah aturan otorisasinya + middleware route-nya.
 */
class MazeChannelAuthTest extends TestCase
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

    public function test_maze_channel_only_allows_lobby_members(): void
    {
        $host     = $this->makePlayer('mazehost');
        $member   = $this->makePlayer('mazemember');
        $outsider = $this->makePlayer('mazeoutsider');

        $lobby = Lobby::create([
            'code'        => 'MAZE01',
            'host_id'     => $host->id,
            'level_key'   => 'level_7',
            'status'      => 'playing',
            'max_players' => 4,
        ]);
        $lobby->players()->attach($host->id, [
            'role' => 'host', 'is_ready' => true, 'joined_at' => now(), 'character' => 'char3',
        ]);
        $lobby->players()->attach($member->id, [
            'role' => 'member', 'is_ready' => true, 'joined_at' => now(), 'character' => 'char1',
        ]);
        LobbyGameState::create(['lobby_id' => $lobby->id, 'revision' => 0]);

        $channels = Broadcast::connection()->getChannels();
        $this->assertTrue($channels->has('maze.{code}'), 'Channel maze.{code} belum terdaftar.');

        $callback = $channels->get('maze.{code}');

        $this->assertTrue((bool) $callback($host, $lobby->code), 'Host harus boleh subscribe.');
        $this->assertTrue((bool) $callback($member, $lobby->code), 'Member harus boleh subscribe.');
        $this->assertFalse((bool) $callback($outsider, $lobby->code), 'Bukan anggota tidak boleh subscribe.');
        $this->assertFalse((bool) $callback($member, 'TIDAKADA'), 'Kode lobby palsu harus ditolak.');
    }

    public function test_broadcasting_auth_route_uses_sanctum(): void
    {
        $route = collect(app('router')->getRoutes()->getRoutes())
            ->first(fn ($r) => $r->uri() === 'api/broadcasting/auth');

        $this->assertNotNull($route, 'Route /api/broadcasting/auth tidak terdaftar.');
        $this->assertContains('auth:sanctum', $route->gatherMiddleware());

        // Tanpa token, endpoint harus menolak (bukan redirect ke halaman login).
        $this->post('/api/broadcasting/auth', [
            'socket_id'    => '1234.5678',
            'channel_name' => 'private-maze.MAZE01',
        ])->assertStatus(401);
    }
}
