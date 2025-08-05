<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin;
use App\Models\TokenValidation;

class AdminAuthenticationController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $admin = Admin::where('email', $credentials['email'])->first();

        if (!$admin || !Hash::check($credentials['password'], $admin->password)) {
            return response()->json([
                'response_code' => 401,
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        // Delete old tokens & create new token
        $admin->tokens()->delete();
        TokenValidation::where('user_id', $admin->id)->delete(); // delete the old one
        $token = $admin->createToken('admin-auth-token')->plainTextToken;
        TokenValidation::create([
            'user_id' => $admin->id,
            'token_bearer' => $token,
            'user_agent' => $request->header('User-Agent'),
        ]);

        return response()->json([
            'response_code' => 200,
            'status' => 'success',
            'message' => 'Login successful',
            'token' => $token,
            'token_type' => 'Bearer',
            'user_info' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
            ]
        ]);
    }

    public function changePasswordAdmin(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:admins,email',
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = Admin::where('email', $request->email)->first();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided password does not match your current password.'],
            ]);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'message' => 'Password changed successfully.',
            'success' => true,
        ]);
    }
}
