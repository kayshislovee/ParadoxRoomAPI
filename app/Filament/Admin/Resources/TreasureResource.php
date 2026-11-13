<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TreasureResource\Pages;
use App\Filament\Admin\Resources\TreasureResource\RelationManagers;
use App\Filament\Forms\Components\ImagePathUpload;
use App\Models\Treasure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TreasureResource extends Resource
{
    protected static ?string $model = Treasure::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationGroup = 'Konten Game';

    protected static ?string $navigationLabel = 'Treasures / Objects';

    protected static ?string $modelLabel = 'Treasure';

    protected static ?string $pluralModelLabel = 'Treasures / Objects';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('level_id')
                    ->relationship('level', 'title')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('achievement_id')
                    ->relationship('achievement', 'title')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('treasure_key')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('name')
                    ->label('Nama Treasure')
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label('Deskripsi')
                    ->rows(2)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('x')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('y')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('width')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('height')
                    ->required()
                    ->numeric(),
                ImagePathUpload::make('image')
                    ->label('Gambar Treasure')
                    ->directory('treasures')
                    ->placeholder('/treasures/atom.png')
                    ->extraAttributes(['class' => 'w-full'])
                    ->helperText('Path gambar: /treasures/nama.png (aset game) atau upload file baru.'),
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
                Tables\Columns\TextColumn::make('achievement.title')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('treasure_key')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('x')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('y')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('width')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('height')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\ImageColumn::make('image')
                    ->state(fn ($record) => AchievementResource::publicUrl($record->image))
                    ->checkFileExistence(false),
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
            'index' => Pages\ListTreasures::route('/'),
            'create' => Pages\CreateTreasure::route('/create'),
            'edit' => Pages\EditTreasure::route('/{record}/edit'),
        ];
    }
}
