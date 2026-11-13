<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lobby_players', function (Blueprint $table) {
            $table->string('character')->nullable(); // 'char1' (Kayshi) or 'char2' (Vania)
        });

        Schema::table('lobby_game_states', function (Blueprint $table) {
            $table->string('released_character')->nullable();   // 'char1' or 'char2'
            $table->string('scene5_character')->nullable();    // 'char3' (Katsuragi) or 'char1' or 'char2'
        });
    }

    public function down(): void
    {
        Schema::table('lobby_players', function (Blueprint $table) {
            $table->dropColumn('character');
        });

        Schema::table('lobby_game_states', function (Blueprint $table) {
            $table->dropColumn(['released_character', 'scene5_character']);
        });
    }
};
