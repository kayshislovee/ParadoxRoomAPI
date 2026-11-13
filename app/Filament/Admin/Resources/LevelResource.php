<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\LevelResource\Pages;
use App\Filament\Admin\Resources\Concerns\FormatsJsonFields;
use App\Models\Level;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LevelResource extends Resource
{
    use FormatsJsonFields;

    protected static ?string $model = Level::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Konten Game';
    protected static ?string $navigationLabel = 'Levels';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('level_key')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('thumbnail')
                    ->required()
                    ->maxLength(255)
                    ->default('?'),
                // ── Profil karakter per level ────────────────────────────
                // Dipakai di layar Achievement (avatar + nama karakter).
                Forms\Components\TextInput::make('character_name')
                    ->label('Nama Karakter')
                    ->maxLength(255)
                    ->default(null)
                    ->helperText('Contoh: Ethan, Katsuragi, Vania'),

                Forms\Components\TextInput::make('character_avatar')
                    ->label('Avatar Karakter (path gambar)')
                    ->maxLength(255)
                    ->default(null)
                    ->helperText('Contoh: /avatars/lab3.png'),

                Forms\Components\TextInput::make('time_limit')
                    ->required()
                    ->numeric()
                    ->default(600),
                Forms\Components\Textarea::make('star_thresholds')
                    ->required()
                    ->columnSpanFull()
                    ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state) : $state)
                    ->dehydrateStateUsing(fn ($state) => $state ? json_decode($state, true) : null),
                Forms\Components\TextInput::make('music')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('dialog_music')
                    ->maxLength(255)
                    ->default(null),

                // ── Konten lanjutan (JSON) ───────────────────────────────────
                // Kolom ini dulu tidak punya field di admin sehingga isinya
                // tidak bisa disunting sama sekali.
                Forms\Components\Section::make('Konten Lanjutan')
                    ->description('Kolom JSON. Edit di sini, lalu frontend memakai hasilnya secara langsung.')
                    ->collapsible()
                    ->schema([
                        self::jsonField('characters', 'Karakter (JSON)'),
                        self::jsonField('scene_comics', 'Comic per Scene (JSON)'),
                        self::jsonField('scene_hints', 'Hint per Scene (JSON)'),
                        self::jsonField('scene_dialogs', 'Dialog per Scene (JSON)'),
                        self::jsonField('exit_light_pos', 'Posisi Lampu Exit (JSON)'),
                    ]),

                Forms\Components\TextInput::make('comic_src')
                    ->label('Comic Awal (path gambar)')
                    ->maxLength(255)
                    ->default(null)
                    ->helperText('Path gambar, contoh: /comic/scene5_end.png'),

                Forms\Components\TextInput::make('exit_door_points')
                    ->label('Titik Pintu Keluar (JSON)')
                    ->maxLength(255)
                    ->default(null),

                Forms\Components\Toggle::make('is_locked')
                    ->required(),
                Forms\Components\TextInput::make('order')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('level_key')
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('thumbnail')
                    ->searchable(),
                Tables\Columns\TextColumn::make('time_limit')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('music')
                    ->searchable(),
                Tables\Columns\TextColumn::make('dialog_music')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_locked')
                    ->boolean(),
                Tables\Columns\TextColumn::make('order')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
            'index' => Pages\ListLevels::route('/'),
            'create' => Pages\CreateLevel::route('/create'),
            'edit' => Pages\EditLevel::route('/{record}/edit'),
        ];
    }
}