<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::table('lobby_game_states', function (Blueprint $table) {
        $table->string('finish_type')->default('win');
    });
}

public function down(): void
{
    Schema::table('lobby_game_states', function (Blueprint $table) {
        $table->dropColumn('finish_type');
    });
}
};
