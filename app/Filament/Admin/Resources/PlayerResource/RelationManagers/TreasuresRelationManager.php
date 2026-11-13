<?php

namespace App\Filament\Admin\Resources\PlayerResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TreasuresRelationManager extends RelationManager
{
    protected static string $relationship = 'treasures';
    protected static ?string $title = 'Treasures Ditemukan';

    public function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('treasure_key')
                ->label('Key'),
            Tables\Columns\TextColumn::make('level.title')
                ->label('Level'),
            Tables\Columns\TextColumn::make('pivot.found_at')
                ->label('Ditemukan')
                ->dateTime('d M Y H:i'),
        ])
        ->actions([
            Tables\Actions\DetachAction::make()
                ->label('Hapus'),
        ]);
    }
}