<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * State "efek negatif" level 7 (glitch reactor + fake info) tadinya hanya
 * disimpan di browser masing-masing pemain, jadi glitch muncul hanya di POV
 * pemain yang menyelesaikan reactor. Kolom ini menyimpan state tersebut di
 * lobby sehingga semua pemain melihatnya bersamaan.
 *
 * `pod_logged_in` dipakai supaya ketika SATU pemain berhasil login ke pod
 * (username + ID + photocard), semua pemain dianggap sudah login.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lobby_game_states', function (Blueprint $table) {
            if (! Schema::hasColumn('lobby_game_states', 'glitched_reactors')) {
                $table->longText('glitched_reactors')->nullable()->after('discovered_clues');
            }
            if (! Schema::hasColumn('lobby_game_states', 'fake_info_active')) {
                $table->boolean('fake_info_active')->default(false)->after('glitched_reactors');
            }
            if (! Schema::hasColumn('lobby_game_states', 'any_reactor_solved')) {
                $table->boolean('any_reactor_solved')->default(false)->after('fake_info_active');
            }
            if (! Schema::hasColumn('lobby_game_states', 'pod_logged_in')) {
                $table->boolean('pod_logged_in')->default(false)->after('any_reactor_solved');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lobby_game_states', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['glitched_reactors', 'fake_info_active', 'any_reactor_solved', 'pod_logged_in'],
                fn ($c) => Schema::hasColumn('lobby_game_states', $c),
            ));

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
