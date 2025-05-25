<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvestorInquiryRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;

class InvestorInquiryController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'interest' => 'required|in:invest,partner',
            'message' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $data = $request->all();

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

        return response()->json([
            'status' => 'success',
            'message' => 'Thank you! Your inquiry has been submitted successfully.',
        ], 200);
    }
}
