<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class InvestorInquiryController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required',
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
        $subject = 'Orbit Contact Form';
        $message = "A new inquiry has been submitted:\n\n" .
            "Name: {$data['name']}\n" .
            "Email: {$data['email']}\n" .
            "Subject: {$data['subject']}\n" .
            "Message: {$data['message']}\n" .
            "Submitted At: " . now()->toDateTimeString();

        // Send the email to the system administrator
        Mail::raw($message, function ($mail) use ($adminEmail, $subject) {
            $mail->to($adminEmail)
                ->subject($subject);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Thank you! Your message has been submitted successfully.',
        ], 200);
    }
}
