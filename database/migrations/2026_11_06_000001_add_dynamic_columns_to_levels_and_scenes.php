<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('levels', function (Blueprint $table) {
            $table->json('scene_hints')->nullable()->after('dialog_music');
            $table->json('scene_dialogs')->nullable()->after('scene_hints');
            $table->json('scene_comics')->nullable()->after('scene_dialogs');
            $table->json('characters')->nullable()->after('scene_comics');
            $table->string('comic_src')->nullable()->after('characters');
            $table->string('exit_door_points')->nullable()->after('comic_src');
            $table->json('exit_light_pos')->nullable()->after('exit_door_points');
        });

        Schema::table('scenes', function (Blueprint $table) {
            $table->json('char1_layer')->nullable()->after('exit_puzzle_id');
            $table->json('char2_layer')->nullable()->after('char1_layer');
            $table->json('char3_layer')->nullable()->after('char2_layer');
            $table->json('char1_sub_views')->nullable()->after('char3_layer');
            $table->json('char2_sub_views')->nullable()->after('char1_sub_views');
            $table->json('char3_sub_views')->nullable()->after('char2_sub_views');
            $table->json('scene_comic')->nullable()->after('char3_sub_views');
            $table->json('comic_pages')->nullable()->after('scene_comic');
            $table->json('highlight_hotspots')->nullable()->after('comic_pages');
            $table->json('intro_dialog')->nullable()->after('highlight_hotspots');
            $table->string('auto_switch_char')->nullable()->after('intro_dialog');
        });

        Schema::table('puzzles', function (Blueprint $table) {
            $table->json('symbol_answer')->nullable()->after('answer');
            $table->json('extra_data')->nullable()->after('symbol_answer');
        });

        if (!Schema::hasColumn('treasures', 'scene_id')) {
            Schema::table('treasures', function (Blueprint $table) {
                $table->string('scene_id')->nullable()->after('treasure_key');
            });
        }
    }

    public function down(): void
    {
        Schema::table('levels', function (Blueprint $table) {
            $table->dropColumn(['scene_hints', 'scene_dialogs', 'scene_comics', 'characters', 'comic_src', 'exit_door_points', 'exit_light_pos']);
        });
        Schema::table('scenes', function (Blueprint $table) {
            $table->dropColumn(['char1_layer', 'char2_layer', 'char3_layer', 'char1_sub_views', 'char2_sub_views', 'char3_sub_views', 'scene_comic', 'comic_pages', 'highlight_hotspots', 'intro_dialog', 'auto_switch_char']);
        });
        Schema::table('puzzles', function (Blueprint $table) {
            $table->dropColumn(['symbol_answer', 'extra_data']);
        });
    }
};