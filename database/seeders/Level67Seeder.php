<?php

namespace Database\Seeders;

use App\Models\Achievement;
use App\Models\Level;
use App\Models\Puzzle;
use App\Models\Scene;
use App\Models\Treasure;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Level67Seeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = __DIR__ . '/data/levels.json';
        if (!file_exists($jsonPath)) {
            $this->command->error("File tidak ditemukan: {$jsonPath}. Jalankan 'node scripts/export-levels.mjs' dulu.");
            return;
        }
        $data = json_decode(file_get_contents($jsonPath), true);
        if (!is_array($data)) {
            $this->command->error('JSON tidak valid.');
            return;
        }

        $now = now();

        foreach (['level_6', 'level_7'] as $key) {
            if (!isset($data[$key])) {
                $this->command->warn("{$key} tidak ada di JSON, dilewati.");
                continue;
            }
            $lvl = $data[$key];
            $this->command->info("Seeding {$key}...");

            // ── LEVEL ─────────────────────────────────────────────────────────
            $level = Level::updateOrCreate(
                ['level_key' => $lvl['id']],
                [
                    'title' => $lvl['title'] ?? '',
                    'description' => $lvl['description'] ?? '',
                    'thumbnail' => $lvl['thumbnail'] ?? '',
                    'time_limit' => $lvl['timeLimit'] ?? 600,
                    'star_thresholds' => $lvl['starThresholds'] ?? [400, 200, 60],
                    'music' => $lvl['music'] ?? null,
                    'dialog_music' => $lvl['dialogMusic'] ?? null,
                    'scene_hints' => $lvl['sceneHints'] ?? null,
                    'scene_dialogs' => $lvl['sceneDialogs'] ?? null,
                    'scene_comics' => $lvl['sceneComics'] ?? null,
                    'characters' => $lvl['characters'] ?? null,
                    'comic_src' => $lvl['comicSrc'] ?? null,
                    'exit_door_points' => $lvl['exitDoorPoints'] ?? null,
                    'exit_light_pos' => $lvl['exitLightPos'] ?? null,
                    'is_locked' => false,
                    'order' => $key === 'level_6' ? 6 : 7,
                ]
            );

            // Hapus data lama (cascade akan hapus hotspots, tapi puzzles/treasures/achievements tidak cascade)
            DB::table('scenes')->where('level_id', $level->id)->delete();
            DB::table('puzzles')->where('level_id', $level->id)->delete();
            DB::table('level_hints')->where('level_id', $level->id)->delete();
            DB::table('dialog_lines')->where('level_id', $level->id)->delete();

            // ── SCENES + HOTSPOTS ─────────────────────────────────────────────
            foreach ($lvl['scenes'] ?? [] as $i => $sc) {
                $scene = Scene::create([
                    'level_id' => $level->id,
                    'scene_key' => $sc['id'],
                    'label' => $sc['label'] ?? $sc['id'],
                    'background_image' => $sc['backgroundImage'] ?? null,
                    'exit_puzzle_id' => $sc['exitPuzzleId'] ?? null,
                    'char1_layer' => $sc['char1Layer'] ?? null,
                    'char2_layer' => $sc['char2Layer'] ?? null,
                    'char3_layer' => $sc['char3Layer'] ?? null,
                    'char1_sub_views' => $sc['char1SubViews'] ?? null,
                    'char2_sub_views' => $sc['char2SubViews'] ?? null,
                    'char3_sub_views' => $sc['char3SubViews'] ?? null,
                    'scene_comic' => $sc['sceneComic'] ?? null,
                    'comic_pages' => $sc['comicPages'] ?? null,
                    'highlight_hotspots' => $sc['highlightHotspots'] ?? null,
                    'intro_dialog' => $sc['introDialog'] ?? null,
                    'auto_switch_char' => $sc['autoSwitchChar'] ?? null,
                    'order' => $i + 1,
                ]);

                foreach ($sc['hotspots'] ?? [] as $j => $h) {
                    DB::table('hotspots')->insert([
                        'scene_id' => $scene->id,
                        'hotspot_key' => $h['id'],
                        'label' => $h['label'] ?? $h['id'],
                        'x' => $h['x'],
                        'y' => $h['y'],
                        'width' => $h['width'],
                        'height' => $h['height'],
                        'cursor' => $h['cursor'] ?? 'pointer',
                        'image' => $h['image'] ?? null,
                        'z_index' => $h['zIndex'] ?? 1,
                        'action_type' => $h['action']['type'] ?? $h['action_type'] ?? 'message',
                        'action_data' => json_encode($h['action'] ?? $h['action_data'] ?? []),
                        'visible_data' => isset($h['visible']) ? json_encode($h['visible']) : null,
                        'order' => $j + 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
                // hotspots in char layers / subviews
                foreach (['char1Layer', 'char2Layer', 'char3Layer'] as $layerKey) {
                    if (!isset($sc[$layerKey]['hotspots'])) continue;
                    foreach ($sc[$layerKey]['hotspots'] as $j => $h) {
                        DB::table('hotspots')->insert([
                            'scene_id' => $scene->id,
                            'hotspot_key' => $h['id'],
                            'label' => $h['label'] ?? $h['id'],
                            'x' => $h['x'], 'y' => $h['y'],
                            'width' => $h['width'], 'height' => $h['height'],
                            'cursor' => $h['cursor'] ?? 'pointer',
                            'image' => $h['image'] ?? null,
                            'z_index' => $h['zIndex'] ?? 1,
                            'action_type' => $h['action']['type'] ?? 'message',
                            'action_data' => json_encode($h['action'] ?? []),
                            'visible_data' => isset($h['visible']) ? json_encode($h['visible']) : null,
                            'order' => 1000 + $j,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
                foreach (['char1SubViews', 'char2SubViews', 'char3SubViews'] as $svKey) {
                    if (!isset($sc[$svKey]) || !is_array($sc[$svKey])) continue;
                    foreach ($sc[$svKey] as $svName => $sv) {
                        if (!isset($sv['hotspots'])) continue;
                        foreach ($sv['hotspots'] as $j => $h) {
                            DB::table('hotspots')->insert([
                                'scene_id' => $scene->id,
                                'hotspot_key' => $h['id'],
                                'label' => $h['label'] ?? $h['id'],
                                'x' => $h['x'], 'y' => $h['y'],
                                'width' => $h['width'], 'height' => $h['height'],
                                'cursor' => $h['cursor'] ?? 'pointer',
                                'image' => $h['image'] ?? null,
                                'z_index' => $h['zIndex'] ?? 1,
                                'action_type' => $h['action']['type'] ?? 'message',
                                'action_data' => json_encode($h['action'] ?? []),
                                'visible_data' => isset($h['visible']) ? json_encode($h['visible']) : null,
                                'order' => 2000 + $j,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]);
                        }
                    }
                }
            }

            // ── PUZZLES ───────────────────────────────────────────────────────
            foreach ($lvl['puzzles'] ?? [] as $p) {
                Puzzle::create([
                    'level_id' => $level->id,
                    'puzzle_key' => $p['id'],
                    'type' => $p['type'] ?? 'keypad',
                    'title' => $p['title'] ?? '',
                    'description' => $p['description'] ?? '',
                    'answer' => is_array($p['answer']) ? json_encode($p['answer']) : ($p['answer'] ?? ''),
                    'symbol_answer' => $p['symbolAnswer'] ?? null,
                    'extra_data' => null,
                    'max_digits' => $p['maxDigits'] ?? null,
                    'pattern_size' => $p['patternSize'] ?? null,
                    'clue_ids' => $p['clueIds'] ?? [],
                ]);
            }

            // ── HINTS (flat) ──────────────────────────────────────────────────
            foreach ($lvl['hints'] ?? [] as $i => $h) {
                DB::table('level_hints')->insert([
                    'level_id' => $level->id,
                    'text' => $h,
                    'order' => $i + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // ── ACHIEVEMENTS ──────────────────────────────────────────────────
            foreach ($lvl['achievements'] ?? [] as $a) {
                Achievement::updateOrCreate(
                    ['achievement_key' => $a['id']],
                    [
                        'level_id' => $level->id,
                        'title' => $a['title'] ?? '',
                        'description' => $a['description'] ?? '',
                        'icon' => $a['icon'] ?? '',
                        'type' => $a['type'] ?? 'time',
                        'is_golden' => $a['isGolden'] ?? false,
                        'time_threshold' => $a['time_threshold'] ?? null,
                        'order' => $a['order'] ?? 0,
                    ]
                );
            }

            // ── TREASURES ─────────────────────────────────────────────────────
            $achId = DB::table('achievements')->where('level_id', $level->id)->where('type', 'treasure')->value('id')
                ?? DB::table('achievements')->where('level_id', $level->id)->value('id');
            foreach ($lvl['treasures'] ?? [] as $i => $t) {
                DB::table('treasures')->updateOrInsert(
                    ['treasure_key' => $t['id']],
                    [
                        'level_id' => $level->id,
                        'name' => $t['name'] ?? null,
                        'description' => $t['description'] ?? null,
                        'scene_id' => $t['sceneId'] ?? null,
                        'achievement_id' => $achId,
                        'x' => $t['x'] ?? 0,
                        'y' => $t['y'] ?? 0,
                        'width' => $t['width'] ?? 5,
                        'height' => $t['height'] ?? 5,
                        'image' => $t['image'] ?? null,
                        'order' => $i + 1,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }

            // ── DIALOG LINES (intro/win/lose) ─────────────────────────────────
            // level6/level7 menyimpan dialog di sceneDialogs & introDialog.
            // Untuk DB, simpan semua introDialog dari setiap scene sebagai dialog_lines.
            foreach ($lvl['scenes'] ?? [] as $sc) {
                if (!isset($sc['introDialog']) || !is_array($sc['introDialog'])) continue;
                foreach ($sc['introDialog'] as $i => $d) {
                    DB::table('dialog_lines')->insert([
                        'level_id' => $level->id,
                        'type' => 'intro',
                        'character' => $d['character'] ?? '',
                        'avatar' => $d['avatar'] ?? null,
                        'avatar_left' => $d['avatarLeft'] ?? null,
                        'avatar_right' => $d['avatarRight'] ?? null,
                        'avatar_left_style' => isset($d['avatarLeftStyle']) ? json_encode($d['avatarLeftStyle']) : null,
                        'avatar_right_style' => isset($d['avatarRightStyle']) ? json_encode($d['avatarRightStyle']) : null,
                        'text' => $d['text'] ?? '',
                        'side' => $d['side'] ?? 'left',
                        'is_system' => $d['isSystem'] ?? 0,
                        'order' => $i + 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
            // sceneDialogs (level 7) — simpan sebagai dialog_lines dengan type sesuai key
            if (isset($lvl['sceneDialogs']) && is_array($lvl['sceneDialogs'])) {
                foreach ($lvl['sceneDialogs'] as $sceneKey => $lines) {
                    if (!is_array($lines)) continue;
                    foreach ($lines as $i => $d) {
                        DB::table('dialog_lines')->insert([
                            'level_id' => $level->id,
                            'type' => 'intro',
                            'character' => $d['character'] ?? '',
                            'avatar' => $d['avatar'] ?? null,
                            'avatar_left' => $d['avatarLeft'] ?? null,
                            'avatar_right' => $d['avatarRight'] ?? null,
                            'avatar_left_style' => isset($d['avatarLeftStyle']) ? json_encode($d['avatarLeftStyle']) : null,
                            'avatar_right_style' => isset($d['avatarRightStyle']) ? json_encode($d['avatarRightStyle']) : null,
                            'text' => $d['text'] ?? '',
                            'side' => $d['side'] ?? 'left',
                            'is_system' => $d['isSystem'] ?? 0,
                            'order' => 1000 + $i,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }
        }

        $this->command->info('Seeding level 6 & 7 selesai.');
    }
}
