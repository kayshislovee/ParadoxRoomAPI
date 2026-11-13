<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\HotspotResource\Pages;
use App\Filament\Admin\Resources\Concerns\ValidatesAssetPaths;
use App\Filament\Forms\Components\ImagePathUpload;
use App\Models\Hotspot;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters;

class HotspotResource extends Resource
{
    use ValidatesAssetPaths;

    protected static ?string $model = Hotspot::class;

    protected static ?string $navigationIcon = 'heroicon-o-cursor-arrow-rays';

    protected static ?string $navigationGroup = 'Konten Game';

    protected static ?string $navigationLabel = 'Hotspots / Objects';

    protected static ?string $modelLabel = 'Hotspot';

    protected static ?string $pluralModelLabel = 'Hotspots / Objects';

    /**
     * Daftar action_type yang dikenal game (boleh diketik manual bila perlu tipe baru).
     */
    public static function actionTypeOptions(): array
    {
        return [
            'open_note' => 'open_note — buka catatan / gambar zoom (action_data.image)',
            'open_zoom' => 'open_zoom — buka modal zoom (action_data.zoomImage, label)',
            'open_puzzle' => 'open_puzzle — buka puzzle (action_data.puzzleId)',
            'message' => 'message — tampilkan pesan (action_data.text)',
            'give_item' => 'give_item — beri item (action_data.itemId)',
            'give_item_and_dialog' => 'give_item_and_dialog — beri item + dialog (action_data.itemId, dialogId)',
            'collect_treasure' => 'collect_treasure — ambil treasure (action_data.treasureId)',
            'go_to_scene' => 'go_to_scene — pindah scene (action_data.sceneId)',
            'enter_subview' => 'enter_subview — masuk sub-view (action_data.subView)',
            'open_zoom_and_dialog' => 'open_zoom_and_dialog — zoom + dialog (action_data.zoomImage, label, dialogId)',
            'open_desktop_and_dialog' => 'open_desktop_and_dialog — desktop PC + dialog (action_data.dialogId)',
            'open_hacking_minigame' => 'open_hacking_minigame — buka hacking minigame',
            'open_tape_reader' => 'open_tape_reader — buka tape reader',
            'open_pod_control' => 'open_pod_control — buka pod control',
            'open_power' => 'open_power — buka panel power',
            'open_cooling' => 'open_cooling — buka panel cooling',
            'open_reactor' => 'open_reactor — buka panel reactor',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Scene & Identitas')
                    ->description('Di scene mana hotspot ini tampil, dan kode uniknya.')
                    ->schema([
                        Forms\Components\Select::make('scene_id')
                            ->label('Scene')
                            ->relationship('scene', 'label')
                            ->searchable()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record
                                ? trim(($record->level ? $record->level->title . ' — ' : '') . $record->label . ' (' . $record->scene_key . ')')
                                : '')
                            ->required(),
                        Forms\Components\TextInput::make('hotspot_key')
                            ->label('Hotspot Key')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Kode unik, mis. hs_lever. Dipakai logika game.'),
                        Forms\Components\TextInput::make('label')
                            ->label('Label')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Nama yang tampil saat hover / tooltip.'),
                    ])->columns(3),

                Forms\Components\Section::make('Posisi & Tampilan')
                    ->description('Posisi dalam persen terhadap ukuran scene (0–100).')
                    ->schema([
                        Forms\Components\TextInput::make('x')
                            ->label('X (%)')
                            ->required()
                            ->numeric()
                            ->step(0.1),
                        Forms\Components\TextInput::make('y')
                            ->label('Y (%)')
                            ->required()
                            ->numeric()
                            ->step(0.1),
                        Forms\Components\TextInput::make('width')
                            ->label('Lebar (%)')
                            ->required()
                            ->numeric()
                            ->step(0.1),
                        Forms\Components\TextInput::make('height')
                            ->label('Tinggi (%)')
                            ->required()
                            ->numeric()
                            ->step(0.1),
                        Forms\Components\Select::make('cursor')
                            ->label('Kursor')
                            ->options([
                                'pointer' => 'pointer — tangan (klik biasa)',
                                'zoom-in' => 'zoom-in — perbesar',
                                'not-allowed' => 'not-allowed — terkunci',
                                'grab' => 'grab — bisa diseret',
                                'help' => 'help — tanda tanya',
                                'default' => 'default — panah biasa',
                            ])
                            ->searchable()
                            ->required()
                            ->default('pointer'),
                        Forms\Components\TextInput::make('z_index')
                            ->label('Z-Index')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(1)
                            ->helperText('Semakin besar, semakin di atas.'),
                        Forms\Components\TextInput::make('order')
                            ->label('Urutan')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        ImagePathUpload::make('image')
                            ->label('Gambar Hotspot (opsional)')
                            ->directory('hotspots')
                            ->placeholder('/objects/lever.png')
                            ->columnSpanFull()
                            ->rules([self::assetPathRule()])
                            ->helperText(self::assetHelperText()),
                    ])->columns(4),

                Forms\Components\Section::make('Aksi Saat Diklik')
                    ->description('Apa yang terjadi saat hotspot diklik, dan syarat tampilnya.')
                    ->schema([
                        Forms\Components\Select::make('action_type')
                            ->label('Tipe Aksi')
                            ->options(self::actionTypeOptions())
                            ->searchable()
                            ->required()
                            ->helperText('Pilih tipe aksi; detailnya diisi di "Action Data".'),
                        Forms\Components\KeyValue::make('action_data')
                            ->label('Action Data (JSON)')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->addButtonLabel('Tambah field')
                            ->columnSpanFull()
                            ->dehydrateStateUsing(fn ($state) => DialogLineResource::numericValues($state))
                            ->helperText('Key umum: image / zoomImage / label / puzzleId / text / itemId / dialogId / treasureId / sceneId / subView. Value harus teks, mis. puzzleId = puzzle_lever_pattern.'),
                        Forms\Components\KeyValue::make('visible_data')
                            ->label('Visible Data (JSON) — syarat tampil (opsional)')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->addButtonLabel('Tambah field')
                            ->columnSpanFull()
                            ->dehydrateStateUsing(fn ($state) => DialogLineResource::numericValues($state))
                            ->helperText('Mis. requiresSolved = puzzle_lever_pattern, atau requiresItem = item_1. Kosongkan bila selalu tampil.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('scene.level.title')
                    ->label('Level')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('scene.label')
                    ->label('Scene')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('hotspot_key')
                    ->label('Key')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('label')
                    ->label('Label')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('position')
                    ->label('Posisi (x, y)')
                    ->state(fn ($record) => sprintf('%s, %s', $record->x, $record->y))
                    ->toggleable(),
                Tables\Columns\ImageColumn::make('image')
                    ->label('Gambar')
                    ->state(fn ($record) => AchievementResource::publicUrl($record->image))
                    ->checkFileExistence(false)
                    ->height(40)
                    ->width(40),
                Tables\Columns\TextColumn::make('action_type')
                    ->label('Aksi')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('z_index')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('order')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('scene_id')
            ->reorderable('order')
            ->filters([
                Filters\SelectFilter::make('level')
                    ->label('Level')
                    ->relationship('scene.level', 'title')
                    ->searchable()
                    ->preload(),
                Filters\SelectFilter::make('scene_id')
                    ->label('Scene')
                    ->relationship('scene', 'label')
                    ->searchable()
                    ->preload(),
                Filters\SelectFilter::make('action_type')
                    ->label('Tipe Aksi')
                    ->options(self::actionTypeOptions()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHotspots::route('/'),
            'create' => Pages\CreateHotspot::route('/create'),
            'edit' => Pages\EditHotspot::route('/{record}/edit'),
        ];
    }
}
