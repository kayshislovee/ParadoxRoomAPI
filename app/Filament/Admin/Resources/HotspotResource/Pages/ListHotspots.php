<?php

namespace App\Filament\Admin\Resources\HotspotResource\Pages;

use App\Filament\Admin\Resources\HotspotResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHotspots extends ListRecords
{
    protected static string $resource = HotspotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
