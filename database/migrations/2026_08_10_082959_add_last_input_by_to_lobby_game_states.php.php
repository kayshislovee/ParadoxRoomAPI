<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lobby_game_states', function (Blueprint $table) {
            $table->string('last_input_by')->nullable(); // username yang terakhir input puzzle
        });
    }

    public function down(): void
    {
        Schema::table('lobby_game_states', function (Blueprint $table) {
            $table->dropColumn('last_input_by');
        });
    }
};