<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lobby_game_states', function (Blueprint $table) {
            $table->boolean('timer_paused')->default(false)->after('last_input_at');
        });
    }

    public function down(): void
    {
        Schema::table('lobby_game_states', function (Blueprint $table) {
            $table->dropColumn('timer_paused');
        });
    }
};