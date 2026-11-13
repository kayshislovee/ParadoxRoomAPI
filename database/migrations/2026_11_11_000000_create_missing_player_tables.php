<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel-tabel pemain ini sudah ada di database production, tapi file
 * migration-nya tidak pernah ada di repo (2026_07_24_create_players_table dsb).
 * Akibatnya `migrate` di environment baru (mis. sqlite untuk testing)
 * tidak membuat tabelnya.
 *
 * Semua create dibungkus hasTable() supaya database production tidak
 * tersentuh sama sekali.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('players')) {
            Schema::create('players', function (Blueprint $table) {
                $table->id();
                $table->string('username', 30)->unique();
                $table->string('email')->unique();
                $table->string('password');
                $table->string('avatar')->nullable();
                $table->text('bio')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('player_progress')) {
            Schema::create('player_progress', function (Blueprint $table) {
                $table->id();
                $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
                $table->string('level_key');
                $table->boolean('completed')->default(false);
                $table->integer('best_time')->default(0);
                $table->integer('stars')->default(0);
                $table->timestamps();

                $table->unique(['player_id', 'level_key']);
            });
        }

        if (! Schema::hasTable('player_achievements')) {
            Schema::create('player_achievements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
                $table->foreignId('achievement_id')->constrained('achievements')->cascadeOnDelete();
                $table->timestamp('unlocked_at')->useCurrent();
                $table->timestamps();

                $table->unique(['player_id', 'achievement_id']);
            });
        }

        if (! Schema::hasTable('player_treasures')) {
            Schema::create('player_treasures', function (Blueprint $table) {
                $table->id();
                $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
                $table->foreignId('treasure_id')->constrained('treasures')->cascadeOnDelete();
                $table->timestamp('found_at')->useCurrent();
                $table->timestamps();

                $table->unique(['player_id', 'treasure_id']);
            });
        }

        if (! Schema::hasTable('leaderboard')) {
            Schema::create('leaderboard', function (Blueprint $table) {
                $table->id();
                $table->foreignId('level_id')->constrained('levels')->cascadeOnDelete();
                $table->string('player_name');
                $table->integer('time_elapsed');
                $table->integer('stars')->default(1);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('leaderboard');
        Schema::dropIfExists('player_treasures');
        Schema::dropIfExists('player_achievements');
        Schema::dropIfExists('player_progress');
        Schema::dropIfExists('players');
    }
};
