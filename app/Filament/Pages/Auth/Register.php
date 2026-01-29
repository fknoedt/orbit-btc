<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Auth\Register as BaseRegister;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;

class Register extends BaseRegister
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Add a custom section with the message above the form
                Section::make()
                    ->schema([
                        Placeholder::make('')
                            ->content(new HtmlString('<div style="text-align: center; color: red; font-size: 0.875rem; font-weight: 600; padding: 0.0rem;"><span style="font-size: 1rem;">Demo version</span><br/>Get 6 months of Pro Access for free if you sign up now</div>')),
                    ])
                    ->compact()
                    ->extraAttributes(['style' => 'background-color: rgba(255, 227, 227); border: 1px solid red;'])
                    ->collapsible(false),
                // Default register form fields (name, email, password, etc.)
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }

    public function register(): ?RegistrationResponse
    {
        // Perform the default registration process and capture the response
        $response = parent::register();

        // Get the newly registered user (now authenticated after parent::register())
        $user = auth()->user();

        // Define the system administrator's email
        $adminEmail = config('btc.system_admin_email');

        // Prepare the email details
        $subject = 'New User Registration';
        $message = "A new user has registered:\n\n" .
            "Name: {$user->name}\n" .
            "Email: {$user->email}\n" .
            "Registered At: {$user->created_at}\n" .
            "View User: " . URL::route('filament.app.resources.users.view', ['record' => $user->id]);

        // Send the email to the system administrator
        Mail::raw($message, function ($mail) use ($adminEmail, $subject) {
            $mail->to($adminEmail)
                ->subject($subject);
        });

        // Return the parent response to maintain default redirect behavior
        return $response;
    }
}
