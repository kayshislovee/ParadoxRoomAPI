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
        Schema::create('dialog_lines', function (Blueprint $table) {
           $table->id();
        $table->foreignId('level_id')->constrained()->cascadeOnDelete();
        $table->enum('type', ['intro', 'win', 'lose'])->default('intro');
        $table->string('character');
        $table->string('avatar')->nullable();
        $table->string('avatar_left')->nullable();
        $table->string('avatar_right')->nullable();
        $table->json('avatar_left_style')->nullable();
        $table->json('avatar_right_style')->nullable();
        $table->text('text');
        $table->enum('side', ['left', 'right'])->default('left');
        $table->boolean('is_system')->default(false);
        $table->integer('order')->default(0);
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dialog_lines');
    }
};
