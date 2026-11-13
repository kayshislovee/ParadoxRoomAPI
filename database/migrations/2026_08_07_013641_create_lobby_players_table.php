<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lobby_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lobby_id')->constrained()->onDelete('cascade');
            $table->foreignId('player_id')->constrained('players')->onDelete('cascade');
            $table->enum('role', ['host', 'member'])->default('member');
            $table->boolean('is_ready')->default(false);
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            $table->unique(['lobby_id', 'player_id']); // 1 player hanya bisa join 1x per lobby
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lobby_players');
    }
};