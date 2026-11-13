<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lobby_game_states', function (Blueprint $table) {
            // Monotonic counter. incremented pada setiap mutasi state.
            // Client memakai ini untuk membuang event yang sudah basi (last-write-wins protection).
            $table->unsignedInteger('revision')->default(0)->after('scene5_character');

            // Inventory per karakter untuk level 7: { "char1": ["item_a", ...], "char2": [...] }
            $table->json('character_inventories')->nullable()->after('revision');

            // Clue yang sudah ditemukan, shared antar player.
            $table->json('discovered_clues')->nullable()->after('character_inventories');

            // Waktu terakhir puzzle input berubah (ISO-8601), untuk mengabaikan input basi.
            $table->timestamp('last_input_at')->nullable()->after('discovered_clues');
        });
    }

    public function down(): void
    {
        Schema::table('lobby_game_states', function (Blueprint $table) {
            $table->dropColumn(['revision', 'character_inventories', 'discovered_clues', 'last_input_at']);
        });
    }
};