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
        $token = $admin->createToken('admin-auth-token')->plainTextToken;

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
}
