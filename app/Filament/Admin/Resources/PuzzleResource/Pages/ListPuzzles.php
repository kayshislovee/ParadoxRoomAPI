<?php

namespace App\Filament\Admin\Resources\PuzzleResource\Pages;

use App\Filament\Admin\Resources\PuzzleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPuzzles extends ListRecords
{
    protected static string $resource = PuzzleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
