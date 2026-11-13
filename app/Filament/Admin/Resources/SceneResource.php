<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SceneResource\Pages;
use App\Filament\Admin\Resources\Concerns\FormatsJsonFields;
use App\Models\Scene;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SceneResource extends Resource
{
    use FormatsJsonFields;

    protected static ?string $model = Scene::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Konten Game';
    protected static ?string $navigationLabel = 'Scenes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('level_id')
                    ->relationship('level', 'title')
                    ->required(),
                Forms\Components\TextInput::make('scene_key')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('label')
                    ->required()
                    ->maxLength(255),
                Forms\Components\FileUpload::make('background_image')
                    ->image(),
                Forms\Components\TextInput::make('exit_puzzle_id')
                    ->maxLength(255)
                    ->default(null),

                // ── Data khusus scene (JSON) ──────────────────────────────────
                // Char layer, sub-view, comic, dan intro dialog dulu tidak bisa
                // disunting dari admin karena tidak punya field.
                Forms\Components\Section::make('Data Scene (JSON)')
                    ->description('Kolom JSON. Edit di sini, lalu frontend memakai hasilnya secara langsung.')
                    ->collapsible()
                    ->schema([
                        self::jsonField('char1_layer', 'Layer Karakter 1 (JSON)'),
                        self::jsonField('char2_layer', 'Layer Karakter 2 (JSON)'),
                        self::jsonField('char3_layer', 'Layer Karakter 3 (JSON)'),
                        self::jsonField('char1_sub_views', 'Sub-View Karakter 1 (JSON)'),
                        self::jsonField('char2_sub_views', 'Sub-View Karakter 2 (JSON)'),
                        self::jsonField('char3_sub_views', 'Sub-View Karakter 3 (JSON)'),
                        self::jsonField('scene_comic', 'Comic Scene (JSON)'),
                        self::jsonField('comic_pages', 'Halaman Comic (JSON)'),
                        self::jsonField('intro_dialog', 'Intro Dialog Scene (JSON)'),
                        self::jsonField('highlight_hotspots', 'Hotspot Highlight (JSON)'),
                    ]),

                Forms\Components\TextInput::make('auto_switch_char')
                    ->label('Auto Switch Karakter')
                    ->maxLength(255)
                    ->default(null)
                    ->helperText('char1 / char2 / char3 (kosongkan jika tidak ada)'),

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
                Tables\Columns\TextColumn::make('level.title')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('scene_key')
                    ->searchable(),
                Tables\Columns\TextColumn::make('label')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('background_image'),
                Tables\Columns\TextColumn::make('exit_puzzle_id')
                    ->searchable(),
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
            'index' => Pages\ListScenes::route('/'),
            'create' => Pages\CreateScene::route('/create'),
            'edit' => Pages\EditScene::route('/{record}/edit'),
        ];
    }
}