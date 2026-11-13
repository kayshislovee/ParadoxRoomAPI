<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel achievements & treasures sebelumnya dibuat manual di production,
 * sehingga environment baru (mis. test sqlite) kehilangan tabel ini.
 * Guard hasTable() dipakai supaya database production tidak tersentuh.
 *
 * Kolom name/description pada treasures sengaja TIDAK dibuat di sini karena
 * sudah ditambahkan oleh migration 2026_11_09_000000_add_name_description_to_treasures_table.
 * Kolom scene_id ditambahkan oleh 2026_11_06_000001_add_dynamic_columns_to_levels_and_scenes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('achievements')) {
            Schema::create('achievements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('level_id')->constrained()->cascadeOnDelete();
                $table->string('achievement_key')->unique();
                $table->string('title');
                $table->text('description');
                $table->string('icon')->nullable();
                $table->enum('type', ['time', 'treasure'])->default('time');
                $table->boolean('is_golden')->default(false);
                $table->integer('time_threshold')->nullable();
                $table->integer('order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('treasures')) {
            Schema::create('treasures', function (Blueprint $table) {
                $table->id();
                $table->foreignId('level_id')->constrained()->cascadeOnDelete();
                $table->foreignId('achievement_id')->constrained()->cascadeOnDelete();
                $table->string('treasure_key');
                $table->double('x');
                $table->double('y');
                $table->double('width');
                $table->double('height');
                $table->string('image')->nullable();
                $table->integer('order')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('treasures');
        Schema::dropIfExists('achievements');
    }
};
