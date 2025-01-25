<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class Stats extends ChartWidget
{
    protected static ?int $sort = 5;
    protected static ?string $heading = 'Chart';
    protected static ?string $description = 'Just a test';

    protected function getData(): array
    {
        return [
            //
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
