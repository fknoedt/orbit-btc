<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Forms\Form;

class Login extends BaseLogin
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Add a custom section with the message above the form
                Section::make()
                    ->schema([
                        Placeholder::make('')
                            ->content('Demo version. Give us a shout at hey@orbitbtc.space')
                            ->extraAttributes(['class' => 'text-sm text-gray-500 mb-1']),
                    ])
                    ->collapsible(false),
                // Default login form fields (email, password, etc.)
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ]);
    }
}
