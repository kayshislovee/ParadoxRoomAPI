<?php

namespace App\Http\Controllers\Api;

use App\Events\GameStateUpdated;
use App\Http\Controllers\Controller;
use App\Models\Lobby;
use App\Models\LobbyGameState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LobbyController extends Controller
{
    /**
     * Aksi yang boleh dikirim client lewat /sync.
     * Nilai di luar daftar ini diabaikan (tidak lagi di-echo bebas ke channel).
     */
    private const SYNC_ACTIONS = [
        'puzzle_solved',
        'hotspot_used',
        'item_collected',
        'item_removed',
        'clue_discovered',
        'treasure_found',
        'scene_changed',
        'character_state',
        'time_sync',
        'timer_paused',
        'reactor_state',
        'pod_login',
    ];

    /**
     * Pastikan caller adalah anggota lobby. Tanpa ini, siapa pun yang tahu
     * kode 6 karakter bisa menulis state, menguras hint, atau mengakhiri game.
     */
    private function requireMember(Request $request, Lobby $lobby): void
    {
        // Pesan harus string — HttpException::__construct tidak menerima array.
        abort_unless(
            $lobby->players()->where('players.id', $request->user()->id)->exists(),
            403,
            'Kamu bukan anggota lobby ini.'
        );
    }

    private function findLobbyOrFail(string $code): Lobby
    {
        return Lobby::where('code', strtoupper($code))->firstOrFail();
    }

    /**
     * Client JS memakai camelCase, service/lama memakai snake_case.
     * Baca keduanya supaya tidak ada payload yang diam-diam jadi null.
     */
    private function payloadKey(array $payload, string $key)
    {
        $snake = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $key));

        return $payload[$key] ?? $payload[$snake] ?? null;
    }
    // POST /api/lobby — Buat lobby baru
    public function create(Request $request)
    {
        $request->validate([
            'level_key'   => 'required|string',
            'max_players' => 'sometimes|integer|min:2|max:4',
            'character_preference' => 'sometimes|string|in:char1,char2',
        ]);

        $player = $request->user();

        // Pastikan player tidak sedang di lobby lain yang masih aktif
        $existing = Lobby::whereHas('players', fn($q) => $q->where('player_id', $player->id))
            ->whereIn('status', ['waiting', 'playing'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Kamu masih berada di lobby lain. Keluar terlebih dahulu.',
                'lobby_code' => $existing->code,
            ], 409);
        }

        $maxPlayers = $request->level_key === 'level_7' ? 2 : ($request->max_players ?? 4);

        $lobby = Lobby::create([
            'code'        => Lobby::generateCode(),
            'host_id'     => $player->id,
            'level_key'   => $request->level_key,
            'status'      => 'waiting',
            'max_players' => $maxPlayers,
        ]);

        // Host otomatis join sebagai host
        $attachData = [
            'role'      => 'host',
            'is_ready'  => true,
            'joined_at' => now(),
        ];

        if ($request->level_key === 'level_7' && $request->character_preference) {
            $attachData['character'] = $request->character_preference;
        }

        $lobby->players()->attach($player->id, $attachData);

        // Buat game state awal
        LobbyGameState::create([
            'lobby_id'        => $lobby->id,
            'solved_puzzles'  => [],
            'used_hotspots'   => [],
            'found_treasures' => [],
            'inventory'       => [],
            'current_scene_id' => null,
            'time_elapsed'    => 0,
            // Seed dari config supaya GAME_HINT_MAX_TOKENS benar-benar berpengaruh
            // (sebelumnya kolom default 5 yang selalu dipakai).
            'shared_hint_tokens' => (int) config('game.hint_max_tokens', 5),
            'revision'        => 0,
        ]);

        return response()->json([
            'lobby_code'  => $lobby->code,
            'level_key'   => $lobby->level_key,
            'max_players' => $lobby->max_players,
            'status'      => $lobby->status,
            'players'     => $this->formatPlayers($lobby),
        ], 201);
    }

    // POST /api/lobby/join — Join lobby dengan kode
    public function join(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $player = $request->user();
        $lobby  = Lobby::where('code', strtoupper($request->code))->first();

        if (!$lobby) {
            return response()->json(['message' => 'Kode lobby tidak ditemukan.'], 404);
        }

        if ($lobby->status !== 'waiting') {
            return response()->json(['message' => 'Game sudah dimulai atau sudah selesai.'], 403);
        }

        if ($lobby->isFull()) {
            return response()->json(['message' => 'Lobby sudah penuh.'], 403);
        }

        // Cek sudah join sebelumnya
        if ($lobby->players()->where('player_id', $player->id)->exists()) {
            return response()->json($this->lobbyData($lobby));
        }

        $attachData = [
            'role'      => 'member',
            'is_ready'  => false,
            'joined_at' => now(),
        ];

        // Auto-assign opposite character for level_7
        if ($lobby->level_key === 'level_7') {
            $host = $lobby->players()->where('role', 'host')->first();
            if ($host && $host->pivot->character) {
                $attachData['character'] = $host->pivot->character === 'char1' ? 'char2' : 'char1';
            }
        }

        $lobby->players()->attach($player->id, $attachData);

        // Broadcast ke semua player di lobby bahwa ada player baru
        broadcast(new GameStateUpdated(
            lobbyCode:   $lobby->code,
            state:       $this->gameStateArray($lobby),
            triggeredBy: $player->username,
            action:      'player_joined',
        ));

        return response()->json($this->lobbyData($lobby));
    }

    // GET /api/lobby/{code} — Info lobby
    public function show(Request $request, string $code)
    {
        $lobby = Lobby::where('code', strtoupper($code))->first();

        if (!$lobby) {
            return response()->json(['message' => 'Lobby tidak ditemukan.'], 404);
        }

        return response()->json($this->lobbyData($lobby));
    }

    // POST /api/lobby/{code}/start — Host mulai game
    public function start(Request $request, string $code)
    {
        $lobby  = $this->findLobbyOrFail($code);
        $player = $request->user();

        if ($lobby->host_id !== $player->id) {
            return response()->json(['message' => 'Hanya host yang bisa memulai game.'], 403);
        }

        if ($lobby->status === 'playing') {
            // Idempoten: host yang klik dua kali tidak boleh merusak state.
            return response()->json(['message' => 'Game sudah dimulai.', 'status' => 'playing']);
        }

        if ($lobby->players()->count() < 2) {
            return response()->json(['message' => 'Minimal 2 player untuk memulai.'], 403);
        }

        // For level_7, verify both players have characters assigned
        if ($lobby->level_key === 'level_7') {
            $playersWithoutCharacter = $lobby->players()
                ->whereNull('lobby_players.character')
                ->count();

            if ($playersWithoutCharacter > 0) {
                return response()->json(['message' => 'Semua player harus memilih karakter terlebih dahulu.'], 403);
            }
        }

        $lobby->update(['status' => 'playing']);

        // Set scene awal di game state
        $lobby->gameState()->update(['current_scene_id' => 'scene_1']);

        broadcast(new GameStateUpdated(
            lobbyCode:   $lobby->code,
            state:       $this->gameStateArray($lobby),
            triggeredBy: $player->username,
            action:      'game_started',
        ));

        return response()->json(['message' => 'Game dimulai!', 'status' => 'playing']);
    }

    // POST /api/lobby/{code}/sync — Sync game state (puzzle solved, hotspot, item, dll)
    public function sync(Request $request, string $code)
    {
        $validated = $request->validate([
            'action'  => 'required|string|in:' . implode(',', self::SYNC_ACTIONS),
            'payload' => 'sometimes|array',
        ]);

        $lobby   = $this->findLobbyOrFail($code);
        $player  = $request->user();
        $this->requireMember($request, $lobby);

        $action  = $validated['action'];
        $payload = $request->input('payload', []);

        $state = DB::transaction(function () use ($lobby, $action, $payload) {
            // lockForUpdate mencegah lost update saat 2 player menulis bersamaan.
            $state = LobbyGameState::where('lobby_id', $lobby->id)->lockForUpdate()->first();

            if (!$state) {
                $state = LobbyGameState::create(['lobby_id' => $lobby->id]);
            }

            $updates = [];

            switch ($action) {
                case 'puzzle_solved':
                    $puzzleId = $this->payloadKey($payload, 'puzzleId');
                    if ($puzzleId !== null) {
                        $solved = $state->solved_puzzles ?? [];
                        if (!in_array($puzzleId, $solved, true)) {
                            $updates['solved_puzzles'] = [...$solved, $puzzleId];
                        }
                    }
                    break;

                case 'hotspot_used':
                    $hotspotId = $this->payloadKey($payload, 'hotspotId');
                    if ($hotspotId !== null) {
                        $used = $state->used_hotspots ?? [];
                        if (!in_array($hotspotId, $used, true)) {
                            $updates['used_hotspots'] = [...$used, $hotspotId];
                        }
                    }
                    break;

                case 'item_collected':
                    $itemId = $this->payloadKey($payload, 'itemId');
                    if ($itemId !== null) {
                        $inv = $state->inventory ?? [];
                        if (!in_array($itemId, $inv, true)) {
                            $updates['inventory'] = [...$inv, $itemId];
                        }
                    }
                    break;

                case 'item_removed':
                    $itemId = $this->payloadKey($payload, 'itemId');
                    if ($itemId !== null) {
                        $updates['inventory'] = array_values(array_filter(
                            $state->inventory ?? [],
                            fn($id) => $id !== $itemId
                        ));
                    }
                    break;

                case 'clue_discovered':
                    $clueId = $this->payloadKey($payload, 'clueId');
                    if ($clueId !== null) {
                        $clues = $state->discovered_clues ?? [];
                        if (!in_array($clueId, $clues, true)) {
                            $updates['discovered_clues'] = [...$clues, $clueId];
                        }
                    }
                    break;

                case 'treasure_found':
                    $treasureId = $this->payloadKey($payload, 'treasureId');
                    if ($treasureId !== null) {
                        $treasures = $state->found_treasures ?? [];
                        if (!in_array($treasureId, $treasures, true)) {
                            $updates['found_treasures'] = [...$treasures, $treasureId];
                        }
                    }
                    break;

                case 'scene_changed':
                    $sceneId = $this->payloadKey($payload, 'sceneId');
                    if ($sceneId !== null) {
                        $updates['current_scene_id'] = $sceneId;
                    }
                    break;

                case 'character_state':
                    // Level 7: inventory tiap karakter disimpan terpisah.
                    $character = $this->payloadKey($payload, 'character');
                    $items    = $payload['inventory'] ?? [];
                    if ($character !== null && is_array($items)) {
                        $byChar = $state->character_inventories ?? [];
                        // Simpan hanya id item supaya kolom JSON tidak membengkak.
                        $byChar[$character] = array_values(array_filter(
                            array_map(fn($i) => is_array($i) ? ($i['id'] ?? null) : $i, $items),
                            fn($id) => $id !== null
                        ));
                        $updates['character_inventories'] = $byChar;
                    }
                    break;

                case 'time_sync':
                    $elapsed = $this->payloadKey($payload, 'timeElapsed');
                    if (is_numeric($elapsed) && $elapsed >= 0) {
                        $updates['time_elapsed'] = (int) $elapsed;
                    }
                    break;

                case 'timer_paused':
                    $paused = filter_var($payload['paused'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    $updates['timer_paused'] = $paused;
                    break;

                case 'reactor_state':
                    // Glitch reactor + fake info harus terlihat di SEMUA pemain
                    // pada saat yang sama, jadi disimpan di state bersama.
                    //
                    // PENTING: gangguan juga bisa DIBAIKI (Katsuragi memakai
                    // Hacking Device+). Dulu server hanya melakukan union saat
                    // menambah daftar, sehingga "sudah diperbaiki" tidak pernah
                    // tersimpan dan glitch muncul lagi di semua pemain.
                    $glitchAdd = array_merge(
                        is_array($payload['glitchAdd'] ?? null) ? $payload['glitchAdd'] : [],
                        is_array($payload['glitched'] ?? null) ? $payload['glitched'] : [],
                    );
                    $glitchAdd = array_values(array_filter($glitchAdd, 'is_string'));
                    if ($glitchAdd) {
                        $updates['glitched_reactors'] = array_values(array_unique([
                            ...($state->glitched_reactors ?? []),
                            ...$glitchAdd,
                        ]));
                    }

                    $glitchClear = is_array($payload['glitchClear'] ?? null)
                        ? array_values(array_filter($payload['glitchClear'], 'is_string'))
                        : [];
                    if ($glitchClear) {
                        $current = $updates['glitched_reactors'] ?? ($state->glitched_reactors ?? []);
                        $updates['glitched_reactors'] = array_values(array_diff($current, $glitchClear));
                        // Dicatat supaya tidak bisa "dibalik" oleh broadcast lama.
                        $updates['glitched_cleared'] = array_values(array_unique([
                            ...($state->glitched_cleared ?? []),
                            ...$glitchClear,
                        ]));
                    }

                    $fakeAdd = array_merge(
                        is_array($payload['fakeAdd'] ?? null) ? $payload['fakeAdd'] : [],
                        is_array($payload['fakeReactors'] ?? null) ? $payload['fakeReactors'] : [],
                    );
                    $fakeAdd = array_values(array_filter($fakeAdd, 'is_string'));
                    if ($fakeAdd) {
                        $updates['fake_reactors'] = array_values(array_unique([
                            ...($state->fake_reactors ?? []),
                            ...$fakeAdd,
                        ]));
                    }

                    $fakeClear = is_array($payload['fakeClear'] ?? null)
                        ? array_values(array_filter($payload['fakeClear'], 'is_string'))
                        : [];
                    if ($fakeClear) {
                        $currentFake = $updates['fake_reactors'] ?? ($state->fake_reactors ?? []);
                        $updates['fake_reactors'] = array_values(array_diff($currentFake, $fakeClear));
                        $updates['fake_cleared'] = array_values(array_unique([
                            ...($state->fake_cleared ?? []),
                            ...$fakeClear,
                        ]));
                    }

                    // Fake info aktif selama masih ada reactor fake yang belum
                    // teridentifikasi — dihitung server supaya semua player sama.
                    $effectiveFake = $updates['fake_reactors'] ?? ($state->fake_reactors ?? []);
                    $updates['fake_info_active'] = count($effectiveFake) > 0;

                    if (array_key_exists('anyReactorSolved', $payload)) {
                        $updates['any_reactor_solved'] = filter_var($payload['anyReactorSolved'], FILTER_VALIDATE_BOOLEAN);
                    }
                    break;

                case 'pod_login':
                    // Satu player berhasil login ke pod => semua player dihitung
                    // sudah login (pod adalah satu perangkat untuk satu tim).
                    $updates['pod_logged_in'] = true;
                    break;
            }

            $updates['revision'] = ((int) $state->revision) + 1;
            $state->update($updates);

            return $state;
        });

        $fresh = $lobby->fresh();

        broadcast(new GameStateUpdated(
            lobbyCode:   $lobby->code,
            state:       $this->gameStateArray($fresh),
            triggeredBy: $player->username,
            action:      $action,
        ));

        return response()->json([
            'message' => 'State diperbarui.',
            'state'   => $this->gameStateArray($fresh),
        ]);
    }

    // POST /api/lobby/{code}/leave — Keluar dari lobby
   public function leave(Request $request, string $code)
{
    $lobby  = $this->findLobbyOrFail($code);
    $player = $request->user();

    $this->detachPlayer($lobby, $player);

    return response()->json(['message' => 'Berhasil keluar dari lobby.']);
}

/**
 * Lepaskan satu player dari lobby, migrasikan host bila perlu, lalu siarkan
 * player_left. Dipakai oleh /leave, /exit dan /exit-beacon supaya ketiga
 * jalur itu konsisten (sebelumnya hanya /leave yang migrasi host).
 */
private function detachPlayer(Lobby $lobby, $player): void
{
    $wasMember = $lobby->players()->where('players.id', $player->id)->exists();
    $wasPlaying = $lobby->status === 'playing';

    $lobby->players()->detach($player->id);

    if ($wasMember && $lobby->host_id === $player->id) {
        $nextPlayer = $lobby->players()->first();

        if ($nextPlayer) {
            $lobby->update(['host_id' => $nextPlayer->id]);
            $lobby->players()->updateExistingPivot($nextPlayer->id, ['role' => 'host']);
        } else {
            $lobby->update(['status' => 'finished']);
        }
    }

    if ($wasMember && $wasPlaying && $lobby->players()->count() === 0) {
        $lobby->update(['status' => 'finished']);

        // PENTING: level ini BELUM diselesaikan — pemainnya Simply keluar.
        // Tandai 'abandon' supaya klien tidak salah menganggap lobby selesai
        // sebagai MENANG (bintang + win screen) padahal level tidak
        // pernah diselesaikan.
        $lobby->gameState()->update([
            'finish_type' => 'abandon',
            'revision'    => ((int) ($lobby->gameState?->revision ?? 0)) + 1,
        ]);
    }

    if ($wasMember) {
        broadcast(new GameStateUpdated(
            lobbyCode:   $lobby->code,
            state:       $this->gameStateArray($lobby->fresh()),
            triggeredBy: $player->username,
            action:      'player_left',
        ));
    }
}

    // POST /api/lobby/{code}/character - Assign character to player (level_7 only)
    public function assignCharacter(Request $request, string $code)
    {
        $request->validate([
            'character' => 'required|string|in:char1,char2',
        ]);

        $lobby  = Lobby::where('code', strtoupper($code))->firstOrFail();
        $player = $request->user();

        $this->requireMember($request, $lobby);

        if ($lobby->level_key !== 'level_7') {
            return response()->json(['message' => 'Hanya untuk level 7.'], 403);
        }

        // Satu karakter hanya boleh dimiliki satu pemain. Kalau tidak dicek,
        // dua pemain bisa memilih char2 yang sama lalu sama-sama masuk ke scene 5.
        $takenByOther = $lobby->players()
            ->where('players.id', '!=', $player->id)
            ->wherePivot('character', $request->character)
            ->exists();

        if ($takenByOther) {
            return response()->json([
                'message' => 'Karakter ini sudah dipilih pemain lain.',
            ], 409);
        }

        $lobby->players()->updateExistingPivot($player->id, [
            'character' => $request->character,
        ]);

        broadcast(new GameStateUpdated(
            lobbyCode:   $lobby->code,
            state:       $this->gameStateArray($lobby->fresh()),
            triggeredBy: $player->username,
            action:      'character_assigned',
        ));

        return response()->json(['message' => 'Karakter berhasil diassign.']);
    }

    // POST /api/lobby/{code}/release - Melepas karakter PILIHAN di lobby.
    //
    // PENTING: endpoint ini TIDAK boleh menulis `released_character`.
    // `released_character` berarti "karakter yang dibebaskan dari pod" (hasil
    // Solving puzzle pod) dan dibaca game untuk membuka PodControlPuzzle.
    // Kalau endpoint ini ikut menuliskannya, sekadar ganti karakter di lobby
    // akan membuat pod terlihat "sudah dibebaskan" (char2 langsung free)
    // tanpa pemain pernah menyelesaikan puzzle frekuensinya.
    public function releaseCharacter(Request $request, string $code)
    {
        $request->validate([
            'character' => 'required|string|in:char1,char2',
        ]);

        $lobby  = Lobby::where('code', strtoupper($code))->firstOrFail();
        $player = $request->user();

        $this->requireMember($request, $lobby);

        if ($lobby->level_key !== 'level_7') {
            return response()->json(['message' => 'Hanya untuk level 7.'], 403);
        }

        // Hanya lepas karakter milik pemain itu sendiri.
        $mine = $lobby->players()
            ->where('players.id', $player->id)
            ->wherePivot('character', $request->character)
            ->exists();

        if ($mine) {
            $lobby->players()->updateExistingPivot($player->id, [
                'character' => null,
            ]);
        }

        broadcast(new GameStateUpdated(
            lobbyCode:   $lobby->code,
            state:       $this->gameStateArray($lobby->fresh()),
            triggeredBy: $player->username,
            action:      'character_released',
        ));

        return response()->json(['message' => 'Karakter berhasil di-release.']);
    }

    // POST /api/lobby/{code}/pod-release - Karakter yang BENAR-BENAR dibebaskan pod.
    //
    // Dipanggil setelah puzzle pod (frekuensi + amplitude) selesai. inilah
    // satu-satunya sumber kebenaran untuk "siapa yang dibebaskan", dan hanya
    // boleh diisi satu kali (one-time transfer).
    public function podReleaseCharacter(Request $request, string $code)
    {
        $request->validate([
            'character' => 'required|string|in:char1,char2',
        ]);

        $lobby  = Lobby::where('code', strtoupper($code))->firstOrFail();
        $player = $request->user();

        $this->requireMember($request, $lobby);

        if ($lobby->level_key !== 'level_7') {
            return response()->json(['message' => 'Hanya untuk level 7.'], 403);
        }

        $state = $lobby->gameState;

        // Pod hanya bisa membebaskan SATU karakter, dan hanya satu kali.
        if ($state && $state->released_character && $state->released_character !== $request->character) {
            return response()->json([
                'message' => 'Karakter sudah dibebaskan sebelumnya.',
                'released_character' => $state->released_character,
            ], 409);
        }

        $lobby->gameState()->update(['released_character' => $request->character]);

        broadcast(new GameStateUpdated(
            lobbyCode:   $lobby->code,
            state:       $this->gameStateArray($lobby->fresh()),
            triggeredBy: $player->username,
            action:      'character_released',
        ));

        return response()->json([
            'message' => 'Karakter berhasil dibebaskan dari pod.',
            'released_character' => $request->character,
        ]);
    }

    // POST /api/lobby/{code}/select-scene5-char — Select character for scene 7_5
    public function selectScene5Character(Request $request, string $code)
    {
        $request->validate([
            'character' => 'required|string|in:char3,char1,char2',
        ]);

        $lobby  = $this->findLobbyOrFail($code);
        $player = $request->user();

        $this->requireMember($request, $lobby);

        if ($lobby->level_key !== 'level_7') {
            return response()->json(['message' => 'Hanya untuk level 7.'], 403);
        }

        // Hanya char3 (Katsuragi) dan karakter yang barusan dibebaskan pod
        // yang boleh dipakai di scene 5.
        $released = $lobby->gameState?->released_character;
        $allowed  = array_values(array_filter(['char3', $released]));

        if (! in_array($request->character, $allowed, true)) {
            return response()->json([
                'message' => 'Karakter itu tidak tersedia untuk scene 5.',
                'allowed' => $allowed,
            ], 409);
        }

        // Dua pemain tidak boleh memakai karakter yang sama di scene 5.
        $takenByOther = $lobby->players()
            ->where('players.id', '!=', $player->id)
            ->wherePivot('scene5_character', $request->character)
            ->exists();

        if ($takenByOther) {
            return response()->json([
                'message' => 'Karakter ini sudah dipilih pemain lain. Pilih karakter yang lain.',
            ], 409);
        }

        // Per-player: tiap orang memilih sendiri setelah karakter di pod dibebaskan.
        $lobby->players()->updateExistingPivot($player->id, [
            'scene5_character' => $request->character,
        ]);

        // Kolom shared di game_state hanya dipakai sebagai informasi "pilihan
        // terakhir" — jangan dipakai klien untuk menentukan karakter pemain,
        // karena tiap pemain punya pilihan sendiri (lihat lobby_players).
        $lobby->gameState()->update(['scene5_character' => $request->character]);

        broadcast(new GameStateUpdated(
            lobbyCode:   $lobby->code,
            state:       $this->gameStateArray($lobby->fresh()),
            triggeredBy: $player->username,
            action:      'scene5_character_selected',
        ));

        return response()->json([
            'message'          => 'Karakter scene 5 berhasil dipilih.',
            'scene5_character' => $request->character,
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function lobbyData(Lobby $lobby): array
    {
        return [
            'lobby_code'  => $lobby->code,
            'level_key'   => $lobby->level_key,
            'status'      => $lobby->status,
            'max_players' => $lobby->max_players,
            'host_id'     => $lobby->host_id,
            'players'     => $this->formatPlayers($lobby),
            'game_state'  => $this->gameStateArray($lobby),
        ];
    }

    private function formatPlayers(Lobby $lobby): array
    {
        return $lobby->players->map(fn($p) => [
            'id'        => $p->id,
            'username'  => $p->username,
            'avatar'    => $p->avatar ? asset('storage/' . $p->avatar) : null,
            'role'      => $p->pivot->role,
            'is_ready'  => $p->pivot->is_ready,
            'character' => $p->pivot->character,
            // Pilihan karakter scene 5 — milik player ini, bukan milik tim.
            'scene5_character' => $p->pivot->scene5_character,
        ])->values()->toArray();
    }

   private function gameStateArray(Lobby $lobby): array
{
    $state = $lobby->gameState;
    if (!$state) return [];

    // Peta harus di-cast ke object: array PHP kosong akan di-encode jadi `[]`,
    // dan di JS `[]` bukan objek — akses `.['someKey']` jadi undefined.
    return [
        'solved_puzzles'      => $state->solved_puzzles ?? [],
        'used_hotspots'       => $state->used_hotspots ?? [],
        'found_treasures'     => $state->found_treasures ?? [],
        'inventory'           => $state->inventory ?? [],
        'current_scene_id'    => $state->current_scene_id,
        'time_elapsed'        => $state->time_elapsed,
        'finish_type'         => $state->finish_type ?? 'win',
        'puzzle_inputs'       => (object) ($state->puzzle_inputs ?? []),
        'shared_hint_tokens'  => $state->shared_hint_tokens ?? 5,
        'shared_hints_used'   => $state->shared_hints_used ?? 0,
        'last_input_by'       => $state->last_input_by,
        'released_character'  => $state->released_character,
        'scene5_character'    => $state->scene5_character,
        'timer_paused'        => (bool) $state->timer_paused,
        // Sinkronisasi
        'revision'            => (int) ($state->revision ?? 0),
        'character_inventories' => (object) ($state->character_inventories ?? []),
        'discovered_clues'    => $state->discovered_clues ?? [],
        'glitched_reactors'   => $state->glitched_reactors ?? [],
        'glitched_cleared'    => $state->glitched_cleared ?? [],
        'fake_reactors'       => $state->fake_reactors ?? [],
        'fake_cleared'        => $state->fake_cleared ?? [],
        'fake_info_active'    => count($state->fake_reactors ?? []) > 0,
        'any_reactor_solved'  => (bool) $state->any_reactor_solved,
        'pod_logged_in'       => (bool) $state->pod_logged_in,
        'last_input_at'       => $state->last_input_at?->toISOString(),
    ];
}

public function my(Request $request)
{
    $player = $request->user();
    
    $lobby = Lobby::whereHas('players', fn($q) => $q->where('player_id', $player->id))
        ->whereIn('status', ['waiting', 'playing'])
        ->first();

    if (!$lobby) {
        return response()->json(null);
    }

    return response()->json($this->lobbyData($lobby));
}

public function exit(Request $request, string $code)
{
    $lobby  = $this->findLobbyOrFail($code);
    $player = $request->user();

    $this->detachPlayer($lobby, $player);

    return response()->json(['message' => 'Berhasil keluar dari lobby.']);
}

public function complete(Request $request, string $code)
{
    return $this->finishLobby($request, $code, 'win');
}

// POST /api/lobby/{code}/lose — Level gagal (kalah)
public function lose(Request $request, string $code)
{
    return $this->finishLobby($request, $code, 'lose');
}

/**
 * Akhiri sesi: tandai finish_type, naikkan revision, lalu siarkan ke semua
 * player SEBELUM mereka dilepas — kalau detach dulu, tidak ada satu pun yang
 * masih subscribe channel-nya dan tidak pernah tahu game sudah selesai.
 */
private function finishLobby(Request $request, string $code, string $finishType)
{
    $lobby = $this->findLobbyOrFail($code);
    $this->requireMember($request, $lobby);

    $lobby->update(['status' => 'finished']);
    $lobby->gameState()->update([
        'finish_type' => $finishType,
        'revision'    => ((int) ($lobby->gameState?->revision ?? 0)) + 1,
    ]);

    broadcast(new GameStateUpdated(
        lobbyCode:   $lobby->code,
        state:       $this->gameStateArray($lobby->fresh()),
        triggeredBy: $request->user()->username,
        action:      $finishType === 'lose' ? 'level_lost' : 'level_completed',
    ));

    $lobby->players()->detach();

    return response()->json([
        'message' => $finishType === 'lose' ? 'Game over!' : 'Level selesai!',
    ]);
}

// POST /api/lobby/{code}/reset — Host mengulang sesi dari awal (status kembali waiting)
public function reset(Request $request, string $code)
{
    $lobby = $this->findLobbyOrFail($code);
    $player = $request->user();

    if ($lobby->host_id !== $player->id) {
        return response()->json(['message' => 'Hanya host yang bisa mereset game.'], 403);
    }

    $lobby->update([
        'status'          => 'waiting',
        'current_scene_id' => null,
    ]);
    $lobby->gameState()->update([
        'solved_puzzles'      => [],
        'used_hotspots'       => [],
        'found_treasures'     => [],
        'inventory'           => [],
        'discovered_clues'    => [],
        'character_inventories' => [],
        'current_scene_id'    => null,
        'time_elapsed'        => 0,
        'finish_type'         => 'win',
        'puzzle_inputs'       => [],
        'last_input_by'       => null,
        'released_character'  => null,
        'scene5_character'    => null,
        'timer_paused'        => false,
        'glitched_reactors'   => [],
        'glitched_cleared'    => [],
        'fake_reactors'       => [],
        'fake_cleared'        => [],
        'fake_info_active'    => false,
        'any_reactor_solved'  => false,
        'pod_logged_in'       => false,
        'revision'            => ((int) ($lobby->gameState?->revision ?? 0)) + 1,
    ]);
    $lobby->players()->updateExistingPivot(['is_ready' => false]);
    // Pilihan karakter scene 5 ikut dibatalkan saat sesi direset.
    $lobby->players()->updateExistingPivot(['scene5_character' => null]);

    broadcast(new GameStateUpdated(
        lobbyCode:   $lobby->code,
        state:       $this->gameStateArray($lobby->fresh()),
        triggeredBy: $player->username,
        action:      'game_reset',
    ));

    return response()->json(['message' => 'Game direset.']);
}

// POST /api/lobby/{code}/force-end — Host memaksa akhiri sesi (mis. ada player nyangkut)
public function forceEnd(Request $request, string $code)
{
    $lobby = $this->findLobbyOrFail($code);
    $player = $request->user();

    if ($lobby->host_id !== $player->id) {
        return response()->json(['message' => 'Hanya host yang bisa mengakhiri game.'], 403);
    }

    $lobby->update(['status' => 'finished']);
    $lobby->gameState()->update([
        'finish_type' => 'lose',
        'revision'    => ((int) ($lobby->gameState?->revision ?? 0)) + 1,
    ]);

    broadcast(new GameStateUpdated(
        lobbyCode:   $lobby->code,
        state:       $this->gameStateArray($lobby->fresh()),
        triggeredBy: $player->username,
        action:      'level_lost',
    ));

    $lobby->players()->detach();

    return response()->json(['message' => 'Game dihentikan host.']);
}

public function exitBeacon(Request $request, string $code)
{
    $body = json_decode($request->getContent(), true);
    $token = $body['token'] ?? null;
    if (!$token) return response()->json(['message' => 'Unauthorized'], 401);

    $user = \Laravel\Sanctum\PersonalAccessToken::findToken($token)?->tokenable;
    if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

    $lobby = Lobby::where('code', strtoupper($code))->first();
    if (!$lobby) return response()->json(['ok' => true]);

    $this->detachPlayer($lobby, $user);

    return response()->json(['ok' => true]);
}
// POST /api/lobby/{code}/input — sync input puzzle realtime
public function input(Request $request, string $code)
{
   $validated = $request->validate([
    'puzzle_id' => 'required|string|max:64',
    // Batasi ukuran supaya kolom JSON tidak bisa dipakai sebagai broadcast amplifier.
    'input'     => ['required', function ($attr, $value, $fail) {
        if (strlen((string) json_encode($value)) > 2048) {
            $fail('Input terlalu besar.');
        }
    }],
]);

    $lobby = $this->findLobbyOrFail($code);
    $this->requireMember($request, $lobby);

    DB::transaction(function () use ($lobby, $validated, $request) {
        $state = LobbyGameState::where('lobby_id', $lobby->id)->lockForUpdate()->first();

        if (!$state) {
            $state = LobbyGameState::create(['lobby_id' => $lobby->id]);
        }

        $inputs = $state->puzzle_inputs ?? [];
        $inputs[$validated['puzzle_id']] = $validated['input'];

        $state->update([
            'puzzle_inputs' => $inputs,
            'last_input_by' => $request->user()->username,
            'last_input_at' => now(),
            'revision'      => ((int) $state->revision) + 1,
        ]);
    });

    broadcast(new GameStateUpdated(
        lobbyCode:   $lobby->code,
        state:       $this->gameStateArray($lobby->fresh()),
        triggeredBy: $request->user()->username,
        action:      'puzzle_input',
    ));

    return response()->json(['ok' => true]);
}

// POST /api/lobby/{code}/hint — pakai hint bersama
public function useHint(Request $request, string $code)
{
    $lobby = $this->findLobbyOrFail($code);
    $this->requireMember($request, $lobby);

    $state = DB::transaction(function () use ($lobby) {
        $state = LobbyGameState::where('lobby_id', $lobby->id)->lockForUpdate()->first();

        if (!$state || $state->shared_hint_tokens <= 0) {
            return null;
        }

        $tokensLeft = ((int) $state->shared_hint_tokens) - 1;
        $hintsUsed  = ((int) $state->shared_hints_used) + 1;

        $state->update([
            'shared_hint_tokens' => $tokensLeft,
            'shared_hints_used'  => $hintsUsed,
            'revision'           => ((int) $state->revision) + 1,
        ]);

        return ['tokens_left' => $tokensLeft, 'hints_used' => $hintsUsed];
    });

    if ($state === null) {
        return response()->json(['message' => 'Token hint habis!'], 403);
    }

    broadcast(new GameStateUpdated(
        lobbyCode:   $lobby->code,
        state:       $this->gameStateArray($lobby->fresh()),
        triggeredBy: $request->user()->username,
        action:      'hint_used',
    ));

    return response()->json($state);
}
public function chat(Request $request, string $code)
{
    $request->validate(['message' => 'required|string|max:200']);

    $lobby = $this->findLobbyOrFail($code);
    $this->requireMember($request, $lobby);

    broadcast(new \App\Events\ChatMessageSent(
        lobbyCode: $lobby->code,
        username:  $request->user()->username,
        // Bersihkan tag supaya payload broadcast tidak bisa jadi vektor XSS
        // kalau suatu saat chat dirender lewat dangerouslySetInnerHTML.
        message:   trim(strip_tags($request->input('message'))),
        sentAt:    now()->toISOString(),
    ));

    return response()->json(['ok' => true]);
}
public function signal(Request $request, string $code)
{
    $request->validate([
        'signal' => 'required',
        'to'     => 'nullable|string|max:64',
    ]);

    $lobby = $this->findLobbyOrFail($code);
    $this->requireMember($request, $lobby);

    broadcast(new \App\Events\WebRTCSignal(
        lobbyCode:   $lobby->code,
        from:        $request->user()->username,
        to:          $request->input('to'),
        signal:      $request->input('signal'),
    ));

    return response()->json(['ok' => true]);
}

}