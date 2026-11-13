<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Avatar & nama karakter per level.
 *
 * Kolom ini dipakai di layar Achievement (kartu per level). Semula data ini
 * hanya ada di file frontend (hardcode), sehingga tampilannya "UNKNOWN" dan
 * tidak bisa disunting dari admin. Sekarang menjadi bagian dari database.
 */
return new class extends Migration
{
    /** Nilai awal: sama dengan data hardcode frontend sebelumnya. */
    private const DEFAULTS = [
        'level_1' => ['/avatars/lab3.png', 'Ethan'],
        'level_2' => ['/avatars/school4.png', 'Ayla'],
        'level_3' => ['/avatars/perpus3.png', 'Vania'],
        'level_4' => ['/avatars/market1.png', 'Sunny'],
        'level_5' => ['/avatars/anak2.png', 'Andi'],
        'level_6' => ['/avatars/zkayshi2.png', 'Ethan'],
        'level_7' => ['/avatars/katsuragi1.png', 'Katsuragi'],
    ];

    public function up(): void
    {
        Schema::table('levels', function (Blueprint $table) {
            if (! Schema::hasColumn('levels', 'character_avatar')) {
                $table->string('character_avatar')->nullable()->after('thumbnail');
            }
            if (! Schema::hasColumn('levels', 'character_name')) {
                $table->string('character_name')->nullable()->after('character_avatar');
            }
        });

        // Isi dengan nilai yang sama seperti data hardcode frontend.
        foreach (self::DEFAULTS as $key => [$avatar, $name]) {
            DB::table('levels')
                ->where('level_key', $key)
                ->where(function ($q) {
                    $q->whereNull('character_avatar')->orWhere('character_avatar', '');
                })
                ->update(['character_avatar' => $avatar, 'character_name' => $name]);
        }
    }

    public function down(): void
    {
        Schema::table('levels', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['character_avatar', 'character_name'],
                fn ($c) => Schema::hasColumn('levels', $c),
            ));

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
