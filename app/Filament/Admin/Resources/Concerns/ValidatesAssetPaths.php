<?php

namespace App\Filament\Admin\Resources\Concerns;

use Closure;
use Filament\Forms\Components\TextInput;

/**
 * Validasi path aset di form admin.
 *
 * Ada 3 konvensi path yang dipakai game:
 *  1. "/objects/nama.png"  → file ada di folder public frontend
 *  2. "achievements/x.png" → hasil upload ke storage Laravel (dis Serving /storage/...)
 *  3. "https://..."        → URL penuh
 *
 * Mistake yang sering terjadi: "/object/monitor.png" (tanpa s) → file tidak
 * ketemu dan hotspot jadi tidak terlihat di game. Validasi di bawah memberi
 * peringatan lebih awal.
 */
trait ValidatesAssetPaths
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

    protected static function assetPathRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($value === null || $value === '') return;

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
        };
    }

    protected static function assetHelperText(): string
    {
        return 'Format: /objects/nama.png (aset di frontend) atau achievements/nama.png (hasil upload → otomatis /storage/...).';
    }

    protected static function assetField(string $name, string $label, string $directory = 'hotspots'): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->columnSpanFull()
            ->rules([self::assetPathRule()])
            ->helperText(self::assetHelperText());
    }
}
