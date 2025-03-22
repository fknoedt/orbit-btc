<?php

namespace App\Filament\Pages;

use App\Filament\Charts\UserModelChart;
use Filament\Pages\Page;

class UserModelScore extends Page
{
    use UserModelChart;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';
    protected static string $view = 'filament.pages.user-model-score';
    protected static ?string $title = 'Analysis';
    //protected static ?string

    protected function getViewData(): array
    {
        $chartData = $this->getChartData();

        return [
            'options' => $chartData['options'],
            'fullDates' => $chartData['fullDates'],
            'extraJsOptions' => $chartData['extraJsOptions'],
        ];
    }

    protected function getHeaderActions(): array
    {
        return $this->getChartActions();
    }
}
