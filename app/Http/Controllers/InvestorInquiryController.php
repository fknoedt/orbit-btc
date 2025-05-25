<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvestorInquiryRequest;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class InvestorInquiryController extends Controller
{
    public function store(InvestorInquiryRequest $request): JsonResponse|RedirectResponse
    {
        // Get validated form data
        $data = $request->validated();

        // Define the system administrator's email
        $adminEmail = config('btc.system_admin_email');

        // Prepare the email details
        $subject = 'New Investor Inquiry';
        $interestLabel = $data['interest'] === 'invest' ? 'Angel Investment' : 'Partnership';
        $message = "A new inquiry has been submitted:\n\n" .
            "Name: {$data['name']}\n" .
            "Email: {$data['email']}\n" .
            "Interest: {$interestLabel}\n" .
            "Message: {$data['message']}\n" .
            "Submitted At: " . now()->toDateTimeString();

        // Send the email to the system administrator using MailerSend
        Mail::raw($message, function ($mail) use ($adminEmail, $subject) {
            $mail->to($adminEmail)
                ->subject($subject);
        });

        if ($request->expectsJson()) {
            // AJAX response
            return response()->json([
                'status' => 'success',
                'message' => 'Inquiry Submitted: Thank you for your interest! We will get back to you soon.',
            ]);
        }

        // Fallback for non-AJAX (unlikely with updated form)
        return redirect()->back()->with('success', 'Inquiry Submitted: Thank you for your interest! We will get back to you soon.');
    }
}
