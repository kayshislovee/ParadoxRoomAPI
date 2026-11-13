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
        $table->json('puzzle_inputs')->default('{}');   // input sementara per puzzle
        $table->integer('shared_hint_tokens')->default(5); // token hint bersama
        $table->integer('shared_hints_used')->default(0);  // hint yang sudah dipakai
    });
}

public function down(): void
{
    Schema::table('lobby_game_states', function (Blueprint $table) {
        $table->dropColumn(['puzzle_inputs', 'shared_hint_tokens', 'shared_hints_used']);
    });
}
};
