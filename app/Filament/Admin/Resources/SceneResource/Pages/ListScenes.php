<?php

namespace App\Filament\Admin\Resources\SceneResource\Pages;

use App\Filament\Admin\Resources\SceneResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListScenes extends ListRecords
{
    protected static string $resource = SceneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
