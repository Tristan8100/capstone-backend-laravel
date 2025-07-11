<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PasswordReset;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        // Generate 6-digit OTP
        $otp = (string) rand(100000, 999999);

        // Hash the OTP
        $hashedOtp = Hash::make($otp);

        // Create or update the OTP record
        PasswordReset::updateOrCreate(
            ['email' => $request->email],
            [
                'code_hash' => $hashedOtp,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Send OTP email
        Mail::raw("Your password reset OTP is: $otp. It expires in 10 minutes.", function ($message) use ($request) {
            $message->to($request->email)
                    ->subject('Password Reset OTP');
        });

        return response()->json(['message' => 'OTP sent to your email.', 'email' => $request->email]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:password_resets,email',
            'otp' => 'required|numeric|digits:6',
        ]);

        $record = PasswordReset::where('email', $request->email)->first();

        if (!$record || !Hash::check($request->otp, $record->code_hash)) {
            return response()->json(['message' => 'Invalid OTP or email.'], 400);
        }

        // Check if OTP expired (10 mins)
        if ($record->updated_at->diffInMinutes(now()) > 10) {
            return response()->json(['message' => 'OTP has expired.'], 400);
        }

        $token = Str::random(60);
        $hashedToken = Hash::make($token);

        $record->update([
            'token' => $hashedToken,
        ]);

        return response()->json([
            'message' => 'OTP verified successfully.',
            'token' => $token, // Plain token sent to frontend
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
            'email' => 'required|email|exists:password_resets,email',
        ]);

        $record = PasswordReset::where('email', $request->email)->first();

        if (!$record || !Hash::check($request->token, $record->token)) {
            return response()->json(['message' => 'Invalid token.'], 400);
        }

        User::where('email', $request->email)->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['message' => 'Password reset successfully.']);
    }
}

