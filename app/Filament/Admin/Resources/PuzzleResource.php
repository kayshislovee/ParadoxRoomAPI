<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PuzzleResource\Pages;
use App\Filament\Admin\Resources\PuzzleResource\RelationManagers;
use App\Models\Puzzle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PuzzleResource extends Resource
{
    protected static ?string $model = Puzzle::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('level_id')
                    ->relationship('level', 'title')
                    ->required(),
                Forms\Components\TextInput::make('puzzle_key')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                       ->label('Tipe Puzzle')
                       ->required()
                       ->options([
                           'keypad'      => 'Keypad angka (KeypadPuzzle.tsx)',
                           'pattern'     => 'Pola minerba / lever (PatternPuzzle.tsx)',
                           'sequence'    => 'Urutan / hafalan (SequencePuzzle.tsx)',
                           'symbol'      => 'Simbol (SymbolPuzzle.tsx)',
                           'combination' => 'Kombinasi / panah (ArrowInputPuzzle.tsx)',
                           'computer'    => 'Komputer (ComputerPuzzle.tsx)',
                           'dual'        => 'Dual lock (DualPuzzle.tsx)',
                       ])
                       ->default('keypad')
                       ->helperText('Pilih "keypad" untuk puzzle kode angka supaya memakai KeypadPuzzle.tsx.'),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('answer')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('max_digits')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('pattern_size')
                    ->numeric()
                    ->default(null),
                Forms\Components\Textarea::make('clue_ids')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('level.title')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('puzzle_key')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('answer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('max_digits')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pattern_size')
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
            'index' => Pages\ListPuzzles::route('/'),
            'create' => Pages\CreatePuzzle::route('/create'),
            'edit' => Pages\EditPuzzle::route('/{record}/edit'),
        ];
    }
}
