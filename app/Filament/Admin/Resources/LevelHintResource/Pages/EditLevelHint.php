<?php

namespace App\Filament\Admin\Resources\LevelHintResource\Pages;

use App\Filament\Admin\Resources\LevelHintResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLevelHint extends EditRecord
{
    protected static string $resource = LevelHintResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
