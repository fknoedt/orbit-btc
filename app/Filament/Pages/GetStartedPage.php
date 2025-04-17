<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class GetStartedPage extends Page
{
    protected static string $view = 'filament.pages.get-started-page';

    protected static ?string $title = 'Get Started';

    protected static ?int $navigationSort = 0;

    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';

    public function getTitle(): string
    {
        return '';
    }
}
