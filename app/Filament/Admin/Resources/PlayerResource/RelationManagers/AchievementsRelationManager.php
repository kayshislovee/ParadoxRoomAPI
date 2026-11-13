<?php

namespace App\Filament\Admin\Resources\PlayerResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AchievementsRelationManager extends RelationManager
{
    protected static string $relationship = 'achievements';
    protected static ?string $title = 'Achievements';

    public function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('achievement_key')
                ->label('Key'),
            Tables\Columns\TextColumn::make('title')
                ->label('Judul'),
            Tables\Columns\TextColumn::make('type')
                ->badge()
                ->color(fn($state) => match($state) {
                    'time'    => 'warning',
                    'treasure'=> 'success',
                    default   => 'gray',
                }),
            Tables\Columns\IconColumn::make('is_golden')
                ->label('Golden')
                ->boolean(),
            Tables\Columns\TextColumn::make('pivot.unlocked_at')
                ->label('Diraih')
                ->dateTime('d M Y H:i'),
        ])
        ->headerActions([])
        ->actions([
            Tables\Actions\DetachAction::make()
                ->label('Cabut Achievement'),
        ]);
    }
}