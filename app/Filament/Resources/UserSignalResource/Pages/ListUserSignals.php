<?php

namespace App\Filament\Resources\UserSignalResource\Pages;

use App\Filament\Resources\UserSignalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUserSignals extends ListRecords
{
    protected static string $resource = UserSignalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Signal'),
        ];
    }
}
