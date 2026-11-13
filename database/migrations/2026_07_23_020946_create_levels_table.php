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
        Schema::create('levels', function (Blueprint $table) {
             $table->id();
        $table->string('level_key')->unique(); // level_1, level_2, dst
        $table->string('title');
        $table->text('description');
        $table->string('thumbnail')->default('🔐');
        $table->integer('time_limit')->default(600);
        $table->json('star_thresholds')->default('[400,200,60]');
        $table->string('music')->nullable();
        $table->string('dialog_music')->nullable();
        $table->boolean('is_locked')->default(false);
        $table->integer('order')->default(0);
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('levels');
    }
};
