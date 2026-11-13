<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lobby_players', function (Blueprint $table) {
            // Karakter untuk scene 5 dipilih SETIAP player setelah karakter di pod
            // dibebaskan, jadi harus per-player. Sebelumnya hanya ada satu kolom
            // `scene5_character` di lobby_game_states (shared), sehingga memilih
            // di satu client langsung menimpa karakter player lain.
            $table->string('scene5_character')->nullable()->after('character');
        });
    }

    public function down(): void
    {
        Schema::table('lobby_players', function (Blueprint $table) {
            $table->dropColumn('scene5_character');
        });
    }
};