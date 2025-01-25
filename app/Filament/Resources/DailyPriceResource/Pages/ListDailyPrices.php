<?php

namespace App\Filament\Resources\DailyPriceResource\Pages;

use App\Filament\Resources\DailyPriceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDailyPrices extends ListRecords
{
    protected static string $resource = DailyPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
