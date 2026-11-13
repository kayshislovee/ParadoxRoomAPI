<?php

namespace App\Filament\Admin\Resources\DialogLineResource\Pages;

use App\Filament\Admin\Resources\DialogLineResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDialogLines extends ListRecords
{
    protected static string $resource = DialogLineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
