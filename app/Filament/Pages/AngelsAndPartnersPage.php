<?php

namespace App\Filament\Pages;

use Filament\Pages\SimplePage;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;

class AngelsAndPartnersPage extends SimplePage
{
    protected static string $view = 'filament.pages.angels-and-partners';

    protected static ?string $title = 'Angel Investment & Partnerships';

    public function getTitle(): string | Htmlable
    {
        return static::$title;
    }

    public function getHeading(): string | Htmlable
    {
        return static::$title;
    }

    public function render(): View
    {
        return view($this::$view, [
            'title' => $this->getTitle(),
            'heading' => $this->getHeading(),
        ]);
    }
}
