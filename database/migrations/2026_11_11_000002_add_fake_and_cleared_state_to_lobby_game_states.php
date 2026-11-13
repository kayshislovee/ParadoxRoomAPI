<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gangguan level 7 (glitch reactor + fake info) harus bisa DIBAIKI, bukan
 * hanya ditambah. Sebelumnya server hanya melakukan union saat menambah
 * daftar, sehingga daftar "sudah diperbaiki" tidak pernah tersimpan dan
 * glitch/fake info muncul kembali di semua pemain.
 *
 * Kolom baru:
 *  - glitched_cleared : id reactor yang glitch-nya sudah diperbaiki Katsuragi
 *  - fake_reactors     : id reactor yang sedang menampilkan fake info
 *  - fake_cleared      : id reactor yang fake info-nya sudah teridentifikasi
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lobby_game_states', function (Blueprint $table) {
            if (! Schema::hasColumn('lobby_game_states', 'glitched_cleared')) {
                $table->longText('glitched_cleared')->nullable()->after('glitched_reactors');
            }
            if (! Schema::hasColumn('lobby_game_states', 'fake_reactors')) {
                $table->longText('fake_reactors')->nullable()->after('glitched_cleared');
            }
            if (! Schema::hasColumn('lobby_game_states', 'fake_cleared')) {
                $table->longText('fake_cleared')->nullable()->after('fake_reactors');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lobby_game_states', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['glitched_cleared', 'fake_reactors', 'fake_cleared'],
                fn ($c) => Schema::hasColumn('lobby_game_states', $c),
            ));

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
