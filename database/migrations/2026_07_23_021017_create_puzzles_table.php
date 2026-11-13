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
        Schema::create('puzzles', function (Blueprint $table) {
            $table->id();
        $table->foreignId('level_id')->constrained()->cascadeOnDelete();
        $table->string('puzzle_key');
        $table->string('type')->default('keypad');
        $table->string('title');
        $table->text('description');
        $table->string('answer');
        $table->integer('max_digits')->nullable();
        $table->integer('pattern_size')->nullable();
        $table->json('clue_ids')->nullable();
        $table->timestamps();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('puzzles');
    }
};
