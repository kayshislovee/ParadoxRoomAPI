<?php

namespace App\Filament\Admin\Resources\DialogLineResource\Pages;

use App\Filament\Admin\Resources\DialogLineResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDialogLine extends EditRecord
{
    protected static string $resource = DialogLineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
