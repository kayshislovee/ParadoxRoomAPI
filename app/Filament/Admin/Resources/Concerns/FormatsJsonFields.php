<?php

namespace App\Filament\Admin\Resources\Concerns;

use Filament\Forms;
use Filament\Forms\Components\Textarea;

/**
 * Helper untuk kolom yang disimpan sebagai JSON.
 *
 * Kolom JSON (mis. `scene_dialogs`, `characters`, `char1_layer`) dulu tidak
 * punya field di admin panel, sehingga isinya tidak bisa disunting sama
 * sekali — inilah salah satu alasan "edit di admin tidak berefek di game".
 * Dengan helper ini, kolom tersebut bisa diedit sebagai teks JSON.
 */
trait FormatsJsonFields
{
    protected static function jsonField(string $name, string $label, int $rows = 8): Textarea
    {
        return Textarea::make($name)
            ->label($label)
            ->rows($rows)
            ->columnSpanFull()
            ->rules(['nullable', 'json'])
            ->formatStateUsing(fn ($state) => is_array($state)
                ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                : $state)
            ->dehydrateStateUsing(function ($state) {
                if (is_array($state)) return $state;
                if (is_string($state) && trim($state) !== '') {
                    $decoded = json_decode($state, true);
                    return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
                }
                return null;
            })
            ->helperText('Isi dengan JSON valid, contoh: {"scene7_1": {"text": "..."}}');
    }
}
