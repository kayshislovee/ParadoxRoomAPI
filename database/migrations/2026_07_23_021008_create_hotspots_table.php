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
        Schema::create('hotspots', function (Blueprint $table) {
                    $table->id();
        $table->foreignId('scene_id')->constrained()->cascadeOnDelete();
        $table->string('hotspot_key');
        $table->string('label');
        $table->float('x');
        $table->float('y');
        $table->float('width');
        $table->float('height');
        $table->string('cursor')->default('pointer');
        $table->string('image')->nullable();
        $table->integer('z_index')->default(1);
        $table->string('action_type');
        $table->json('action_data')->nullable();
        $table->json('visible_data')->nullable();
        $table->integer('order')->default(0);
        $table->timestamps();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotspots');
    }
};
