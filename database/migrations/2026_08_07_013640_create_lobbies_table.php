<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lobbies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->unique();         // Kode lobby 6-8 karakter
            $table->foreignId('host_id')->constrained('players')->onDelete('cascade');
            $table->string('level_key');                 // Level yang dimainkan
            $table->enum('status', ['waiting', 'playing', 'finished'])->default('waiting');
            $table->integer('max_players')->default(4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lobbies');
    }
};