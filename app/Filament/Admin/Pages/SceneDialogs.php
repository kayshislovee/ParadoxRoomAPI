<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Resources\DialogLineResource;
use App\Filament\Forms\Components\ImagePathUpload;
use App\Models\Level;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

/**
 * Editor untuk "scene dialogs" — dialog dinamis per-scene yang disimpan sebagai
 * JSON di kolom levels.scene_dialogs (dipakai gameplay multi-karakter, mis. Level 6/7).
 *
 * Format JSON di database (sama seperti data lokal game):
 *   { "scene7_1_item": [ { "character": ..., "text": ..., "side": ..., "avatarLeft": ... }, ... ], ... }
 */
class SceneDialogs extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    protected static ?string $navigationGroup = 'Konten Game';

    protected static ?string $navigationLabel = 'Scene Dialogs';

    protected static ?string $title = 'Edit Dialog Per-Scene';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.admin.pages.scene-dialogs';

    /** Level yang sedang diedit */
    public ?int $levelId = null;

    /** Key grup dialog, mis. scene7_1_item */
    public ?string $groupKey = null;

    /** Nama grup baru saat membuat */
    public string $newGroupKey = '';

    /** State form (lines repeater) */
    public ?array $data = [];

    public function mount(): void
    {
        $this->levelId = Level::whereNotNull('scene_dialogs')
            ->orderBy('order')
            ->value('id')
            ?? Level::orderBy('order')->value('id');

        $this->selectGroup($this->groupKeys()[0] ?? null);
    }

    /** Semua level untuk dropdown pemilihan. */
    public function levels(): Collection
    {
        return Level::orderBy('order')->get(['id', 'level_key', 'title', 'scene_dialogs']);
    }

    /** Daftar key grup dialog pada level terpilih. */
    public function groupKeys(): array
    {
        $level = $this->currentLevel();

        return array_keys(is_array($level?->scene_dialogs) ? $level->scene_dialogs : []);
    }

    public function currentLevel(): ?Level
    {
        return $this->levelId ? Level::find($this->levelId) : null;
    }

    public function updatedLevelId(): void
    {
        $this->selectGroup($this->groupKeys()[0] ?? null);
    }

    public function updatedGroupKey(): void
    {
        $this->fillLines();
    }

    public function selectGroup(?string $key): void
    {
        $this->groupKey = $key;
        $this->fillLines();
    }

    public function createGroup(): void
    {
        $key = trim($this->newGroupKey);

        if ($key === '') {
            Notification::make()->title('Isi nama key dialog dulu')->warning()->send();

            return;
        }

        if (! preg_match('/^[a-zA-Z0-9_\-]+$/', $key)) {
            Notification::make()->title('Key hanya boleh huruf, angka, underscore (_) dan strip (-)')->danger()->send();

            return;
        }

        $level = $this->currentLevel();

        if (! $level) {
            return;
        }

        $dialogs = is_array($level->scene_dialogs) ? $level->scene_dialogs : [];

        if (array_key_exists($key, $dialogs)) {
            Notification::make()->title("Grup \"{$key}\" sudah ada")->danger()->send();

            return;
        }

        $dialogs[$key] = [];
        $level->scene_dialogs = $dialogs;
        $level->save();

        $this->newGroupKey = '';
        $this->selectGroup($key);

        Notification::make()->title("Grup \"{$key}\" dibuat")->success()->send();
    }

    public function deleteGroup(): void
    {
        $level = $this->currentLevel();

        if (! $level || ! $this->groupKey) {
            return;
        }

        $dialogs = is_array($level->scene_dialogs) ? $level->scene_dialogs : [];
        unset($dialogs[$this->groupKey]);

        $level->scene_dialogs = $dialogs;
        $level->save();

        $deleted = $this->groupKey;
        $this->selectGroup(array_keys($dialogs)[0] ?? null);

        Notification::make()->title("Grup \"{$deleted}\" dihapus")->success()->send();
    }

    protected function fillLines(): void
    {
        $level = $this->currentLevel();
        $dialogs = is_array($level?->scene_dialogs) ? $level->scene_dialogs : [];
        $lines = ($this->groupKey && isset($dialogs[$this->groupKey])) ? $dialogs[$this->groupKey] : [];

        $this->data = ['lines' => array_values(is_array($lines) ? $lines : [])];
        $this->form->fill($this->data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Repeater::make('lines')
                    ->label('Baris Dialog')
                    ->schema([
                        Forms\Components\TextInput::make('character')
                            ->label('Karakter')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Kayshii, Vania, Zahra, …'),
                        Forms\Components\Select::make('side')
                            ->label('Sisi')
                            ->options([
                                'left' => 'Kiri',
                                'right' => 'Kanan',
                            ])
                            ->default('left')
                            ->required(),
                        Forms\Components\Toggle::make('isSystem')
                            ->label('Baris sistem')
                            ->default(false),
                        Forms\Components\Textarea::make('text')
                            ->label('Isi Dialog')
                            ->required()
                            ->rows(2)
                            ->columnSpanFull(),
                        ImagePathUpload::make('avatar')
                            ->label('Avatar')
                            ->directory('avatars')
                            ->placeholder('/avatars/lab2.png'),
                        ImagePathUpload::make('avatarLeft')
                            ->label('Avatar Kiri')
                            ->directory('avatars')
                            ->placeholder('/avatars/lab3.png (opsional)'),
                        ImagePathUpload::make('avatarRight')
                            ->label('Avatar Kanan')
                            ->directory('avatars')
                            ->placeholder('/avatars/villain1.png (opsional)'),
                        Forms\Components\KeyValue::make('avatarLeftStyle')
                            ->label('Style Avatar Kiri (opsional)')
                            ->keyLabel('Property')
                            ->valueLabel('Nilai')
                            ->dehydrateStateUsing(fn ($state) => DialogLineResource::numericValues($state))
                            ->helperText('bottom, left, height — angka.'),
                        Forms\Components\KeyValue::make('avatarRightStyle')
                            ->label('Style Avatar Kanan (opsional)')
                            ->keyLabel('Property')
                            ->valueLabel('Nilai')
                            ->dehydrateStateUsing(fn ($state) => DialogLineResource::numericValues($state))
                            ->helperText('bottom, right, height — angka.'),
                    ])
                    ->columns(2)
                    ->default([])
                    ->addActionLabel('+ Tambah baris dialog')
                    ->reorderableWithButtons()
                    ->collapsible()
                    ->cloneable()
                    ->itemLabel(fn (array $state): ?string => filled($state['character'] ?? null) || filled($state['text'] ?? null)
                        ? trim(($state['character'] ?? '?') . ' — ' . mb_strimwidth((string) ($state['text'] ?? ''), 0, 60, '…'))
                        : 'Baris dialog')
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $level = $this->currentLevel();

        if (! $level || ! $this->groupKey) {
            Notification::make()->title('Pilih level & grup dialog dulu')->warning()->send();

            return;
        }

        $state = $this->form->getState();

        $lines = collect($state['lines'] ?? [])
            ->map(fn (array $line) => $this->cleanLine($line))
            ->values()
            ->all();

        $dialogs = is_array($level->scene_dialogs) ? $level->scene_dialogs : [];
        $dialogs[$this->groupKey] = $lines;

        $level->scene_dialogs = $dialogs;
        $level->save();

        Notification::make()
            ->title("Dialog \"{$this->groupKey}\" tersimpan (" . count($lines) . ' baris)')
            ->success()
            ->send();
    }

    /** Rapikan satu baris: buang key kosong, pastikan style berupa angka. */
    protected function cleanLine(array $line): array
    {
        $line = array_filter($line, fn ($value) => ! (is_string($value) && trim($value) === '') && $value !== null);

        foreach (['avatarLeftStyle', 'avatarRightStyle'] as $key) {
            if (isset($line[$key]) && is_array($line[$key])) {
                $line[$key] = DialogLineResource::numericValues($line[$key]);

                if ($line[$key] === []) {
                    unset($line[$key]);
                }
            }
        }

        if (($line['isSystem'] ?? false) === false) {
            unset($line['isSystem']);
        }

        return $line;
    }
}
