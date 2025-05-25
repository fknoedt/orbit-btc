<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvestorInquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Allow all users to submit the form
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'interest' => ['required', 'in:invest,partner'],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }
}
