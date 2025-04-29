<?php

namespace App\Filament\Resources\MetricResource\Pages;

use App\Filament\Resources\MetricResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;

class ListMetrics extends ListRecords
{
    protected static string $resource = MetricResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    public function table(Table $table): Table
    {
        return parent::table($table)->query(
            MetricResource::getEloquentQuery()->withCount('userMetricAlerts')
        );
    }
}
