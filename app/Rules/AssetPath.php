<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validasi bahwa path aset yang diketik di panel admin adalah path yang dikenal.
 *
 * Konvensi path yang dipakai game:
 *  1. "/objects/nama.png"   → file ada di folder public frontend
 *  2. "achievements/x.png"  → hasil upload ke storage Laravel (di-serve sebagai /storage/...)
 *  3. "https://..."         → URL penuh (atau data: URI)
 *
 * Kesalahan yang sering terjadi: "/object/monitor.png" (tanpa huruf s) → file tidak
 * ketemu dan hotspot jadi tidak terlihat di game. Validasi ini menolak path yang
 * foldernya tidak dikenal lebih awal, sebelum disimpan ke database.
 */
class AssetPath implements ValidationRule
{
    /** Folder yang dikenal di folder public frontend. */
    private const FRONTEND_FOLDERS = [
        'objects', 'avatars', 'notes', 'treasures', 'achievements',
        'backgrounds', 'comic', 'hotspots', 'ui', 'audio', 'levels',
    ];

    /** Folder tempat upload admin menyimpan file. */
    private const STORAGE_FOLDERS = [
        'achievements', 'avatars', 'hotspots', 'treasures',
        'backgrounds', 'objects', 'notes', 'uploads',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return; // kosong = opsional
        }

        $path = trim((string) $value);

        // URL penuh & data URI selalu sah.
        if (preg_match('#^(https?:)?//#i', $path) || preg_match('#^(data|blob):#i', $path)) {
            return;
        }

        $folder = strtolower(explode('/', ltrim($path, '/'))[0] ?? '');
        $known = array_merge(self::FRONTEND_FOLDERS, self::STORAGE_FOLDERS, ['storage']);

        if ($folder !== '' && ! in_array($folder, $known, true)) {
            $fail("Folder \"{$folder}\" tidak dikenal. Folder yang tersedia: " . implode(', ', $known) . '.');
        }
    }
}
