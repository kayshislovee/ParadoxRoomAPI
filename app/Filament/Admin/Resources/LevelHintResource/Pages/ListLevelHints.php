<?php

namespace App\Filament\Admin\Resources\LevelHintResource\Pages;

use App\Filament\Admin\Resources\LevelHintResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLevelHints extends ListRecords
{
    protected static string $resource = LevelHintResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
