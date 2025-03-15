<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard;

class CustomDashboard extends Dashboard
{
    /*protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.custom-dashboard';*/

    public function getColumns(): int | array
    {
        return [
            'default' => 1, // Single column on small screens
            'md' => 2,     // Two columns on medium screens and up
            'lg' => 3,     // Three columns on large screens
        ];
    }
}
