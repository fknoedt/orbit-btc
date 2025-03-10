<?php

namespace App\Filament\Pages;

use App\Filament\Charts\UserModelChart;
use App\Models\UserModel;
use Filament\Pages\Page;

class UserModelScore extends Page
{
    use UserModelChart;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static string $view = 'filament.pages.user-model-score';

    protected static ?string $title = 'Model Score';

    public bool $deferLoading = true;

    protected function getViewData(): array
    {
        // TODO: show all UserModels with basic info + "No Metrics => no chart" links to View/ Edit

        $userModel = UserModel::where('user_id', auth()->id())->first();

        return [
            'options' => $this->getChartOptions($userModel->id),
        ];
    }
}
