<?php

namespace App\Filament\Resources\UserModelResource\Pages;

use App\Filament\Resources\UserModelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUserModels extends ListRecords
{
    protected static string $resource = UserModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Model'),
        ];
    }
}
