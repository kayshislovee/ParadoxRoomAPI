<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lobby_game_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lobby_id')->constrained()->onDelete('cascade');
            $table->json('solved_puzzles')->default('[]');    // puzzle yang sudah diselesaikan
            $table->json('used_hotspots')->default('[]');     // hotspot yang sudah dipakai
            $table->json('found_treasures')->default('[]');   // treasure yang sudah ditemukan
            $table->json('inventory')->default('[]');         // item di inventory bersama
            $table->string('current_scene_id')->nullable();   // scene yang sedang aktif
            $table->integer('time_elapsed')->default(0);      // waktu berjalan (detik)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lobby_game_states');
    }
};