<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Level;
use Illuminate\Http\JsonResponse;

class LevelController extends Controller
{
    /**
     * GET /api/levels
     * Daftar semua level (tanpa relasi berat), untuk halaman level select.
     */
    public function index(): JsonResponse
    {
        $levels = Level::orderBy('order')
            ->with(['treasures', 'achievements'])
            ->get()
            ->map(fn($l) => [
                'id'               => $l->id,
                'level_key'        => $l->level_key,
                'title'            => $l->title,
                'description'      => $l->description,
                'thumbnail'        => $l->thumbnail,
                'character_avatar' => $l->character_avatar,
                'character_name'   => $l->character_name,
                'time_limit'       => $l->time_limit,
                'star_thresholds'  => $l->star_thresholds,
                'is_locked'        => (bool) $l->is_locked,
                'order'            => $l->order,
                'treasures'        => $l->treasures->map(fn($t) => [
                    'id'           => $t->id,
                    'treasure_key' => $t->treasure_key,
                    'name'         => $t->name,
                    'description'  => $t->description,
                    'image'        => $t->image,
                    'x'            => $t->x,
                    'y'            => $t->y,
                    'width'        => $t->width,
                    'height'       => $t->height,
                    'scene_id'     => $t->scene_id,
                ]),
                'achievements'     => $l->achievements->map(fn($a) => [
                    'id'              => $a->id,
                    'achievement_key' => $a->achievement_key,
                    'title'           => $a->title,
                    'description'     => $a->description,
                    'icon'            => $a->icon,
                    'type'            => $a->type,
                    'is_golden'       => (bool) $a->is_golden,
                    'time_threshold'  => $a->time_threshold,
                    'order'           => $a->order,
                ]),
            ]);

        return response()->json($levels);
    }

    /**
     * GET /api/levels/{level_key}
     * Detail satu level lengkap: scenes + hotspots + puzzles + hints + dialog.
     * Inilah yang di-fetch React saat memulai gameplay.
     */
    public function show(string $levelKey): JsonResponse
    {
        $level = Level::where('level_key', $levelKey)
            ->with([
                'scenes' => function ($q) {
                    $q->orderBy('order')->with([
                        'hotspots' => fn($q) => $q->orderBy('order'),
                    ]);
                },
                'puzzles',
                'levelHints' => fn($q) => $q->orderBy('order'),
                'dialogLines' => fn($q) => $q->orderBy('order'),
            ])
            ->firstOrFail();

      $data = [
    'id' => $level->id,
    'level_key' => $level->level_key,
    'title' => $level->title,
    'description' => $level->description,
    'thumbnail' => $level->thumbnail,
            'character_avatar' => $level->character_avatar,
            'character_name'   => $level->character_name,
    'time_limit' => $level->time_limit,
    'star_thresholds' => $level->star_thresholds,
    'music' => $level->music,
    'dialog_music' => $level->dialog_music,
    'is_locked' => (bool) $level->is_locked,

    // Level 7 dinamika
    'scene_hints' => $level->scene_hints ?? [],
    'scene_dialogs' => $level->scene_dialogs ?? [],
    'scene_comics' => $level->scene_comics ?? [],
    'characters' => $level->characters ?? [],
    'comic_src' => $level->comic_src,
    'exit_door_points' => $level->exit_door_points,
    'exit_light_pos' => $level->exit_light_pos,

    'scenes' => $level->scenes->map(fn($s) => [
        'id' => $s->id,
        'scene_key' => $s->scene_key,
        'label' => $s->label,
        'background_image' => $s->background_image,
        'exit_puzzle_id' => $s->exit_puzzle_id,
        'order' => $s->order,
        // dinamis per-scene
        'char1_layer' => $s->char1_layer,
        'char2_layer' => $s->char2_layer,
        'char3_layer' => $s->char3_layer,
        'char1_sub_views' => $s->char1_sub_views ?? [],
        'char2_sub_views' => $s->char2_sub_views ?? [],
        'char3_sub_views' => $s->char3_sub_views ?? [],
        'scene_comic' => $s->scene_comic,
        'comic_pages' => $s->comic_pages ?? [],
        'highlight_hotspots' => $s->highlight_hotspots ?? [],
        'intro_dialog' => $s->intro_dialog ?? [],
        'auto_switch_char' => $s->auto_switch_char,
        'hotspots' => $s->hotspots->map(fn($h) => [
            'id' => $h->id, 'hotspot_key' => $h->hotspot_key, 'label' => $h->label,
            'x' => $h->x, 'y' => $h->y, 'width' => $h->width, 'height' => $h->height,
            'cursor' => $h->cursor, 'image' => $h->image, 'z_index' => $h->z_index,
            'action_type' => $h->action_type, 'action_data' => $h->action_data,
            'visible_data' => $h->visible_data, 'order' => $h->order,
        ]),
    ]),

    'puzzles' => $level->puzzles->map(fn($p) => [
        'id' => $p->id, 'puzzle_key' => $p->puzzle_key, 'type' => $p->type,
        'title' => $p->title, 'description' => $p->description,
        'answer' => $p->answer, 'symbol_answer' => $p->symbol_answer,
        'extra_data' => $p->extra_data,
        'max_digits' => $p->max_digits, 'pattern_size' => $p->pattern_size,
        'clue_ids' => $p->clue_ids,
    ]),

    'hints' => $level->levelHints->map(fn($h) => [
        'id' => $h->id, 'text' => $h->text, 'order' => $h->order,
    ]),

    'dialog_lines' => $level->dialogLines->map(fn($d) => [
        'id' => $d->id, 'type' => $d->type, 'character' => $d->character,
        'avatar' => $d->avatar, 'avatar_left' => $d->avatar_left, 'avatar_right' => $d->avatar_right,
        'avatar_left_style' => $d->avatar_left_style, 'avatar_right_style' => $d->avatar_right_style,
        'text' => $d->text, 'side' => $d->side, 'is_system' => (bool) $d->is_system, 'order' => $d->order,
    ]),

    'achievements' => $level->achievements->map(fn($a) => [
        'id' => $a->id, 'achievement_key' => $a->achievement_key, 'title' => $a->title,
        'description' => $a->description, 'icon' => $a->icon, 'type' => $a->type,
        'is_golden' => $a->is_golden, 'time_threshold' => $a->time_threshold,
    ]),

    'treasures' => $level->treasures->map(fn($t) => [
        'id' => $t->id, 'treasure_key' => $t->treasure_key,
        'name' => $t->name, 'description' => $t->description,
        'achievement_id' => $t->achievement_id, 'scene_id' => $t->scene_id ?? null,
        'x' => $t->x, 'y' => $t->y, 'width' => $t->width, 'height' => $t->height,
        'image' => $t->image,
    ]),
];

        return response()->json($data);
    }
}