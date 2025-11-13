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
use Illuminate\Validation\Rule;
use Cloudinary\Cloudinary;
use App\Models\ActivityLog;

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

        ActivityLog::create([
            'admin_id' => $admin->id,
            'action' => 'Logged in',
            'description' => 'Admin logged in successfully',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Delete old tokens & create new token
        $admin->tokens()->delete();
        TokenValidation::where('user_id', $admin->id)->delete(); // delete the old one
        $token = $admin->createToken('admin-auth-token')->plainTextToken;
        TokenValidation::create([
            'user_id' => $admin->id,
            'token_bearer' => $token,
            'user_agent' => $request->header('User-Agent'),
        ]);

        $cookie = cookie(
            'auth_token',               // cookie name
            $token,                     // cookie value
            60 * 24,                    // minutes (1 day)
            '/',                        // path
            null,                       // domain (null = current domain)
            app()->environment('production'), // Secure only in production
            true,                       // HttpOnly
            false,                      // Raw
            app()->environment('production') ? 'None' : 'Lax' // SameSite
        );

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
        ])->withCookie($cookie);
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

    public function createAdmin(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:admins,email',
            'password' => 'required|string|min:8',
        ]);

        // REMOVE LATER
        $admin = Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Admin created successfully.',
            'admin' => $admin,
            'success' => true,
        ]);
    }
    
    public function newAdmin(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                // unique in admins
                Rule::unique('admins', 'email'),
                //unique in users
                function ($attribute, $value, $fail) {
                    if (\App\Models\User::where('email', $value)->exists()) {
                        $fail('The '.$attribute.' has already been taken.');
                    }
                },
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'], // requires password_confirmation
        ]);

        if(Auth::user()->super_admin === false){
            return response()->json([
                'message' => 'Unauthorized. Only super admins can create new admins.'
            ], 403);
        }

        // Create the admin
        $admin = Admin::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'super_admin' => false, // always false by default
            'email_verified_at' => null, // not verified, not implemented yet
        ]);

        return response()->json([
            'message' => 'Admin created successfully',
            'admin' => $admin
        ], 201);
    }

    public function update(Request $request, $id)
    {
        // Check super admin privilege
        if (!Auth::user()->super_admin) {
            return response()->json([
                'message' => 'Unauthorized. Only super admins can update admins.'
            ], 403);
        }

        $admin = Admin::findOrFail($id);

        // Validate
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('admins', 'email')->ignore($admin->id),
                function ($attribute, $value, $fail) {
                    if (\App\Models\User::where('email', $value)->exists()) {
                        $fail('The '.$attribute.' has already been taken.');
                    }
                },
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        // Update fields
        if (isset($validated['name'])) {
            $admin->name = $validated['name'];
        }

        if (isset($validated['email'])) {
            $admin->email = $validated['email'];
        }

        if (!empty($validated['password'])) {
            $admin->password = Hash::make($validated['password']);
        }

        $admin->save();

        return response()->json([
            'message' => 'Admin updated successfully',
            'admin' => $admin
        ]);
    }

    /**
     * Delete an admin.
     */
    public function destroy($id)
    {
        // Check super admin privilege
        if (!Auth::user()->super_admin) {
            return response()->json([
                'message' => 'Unauthorized. Only super admins can delete admins.'
            ], 403);
        }

        $admin = Admin::findOrFail($id);
        
        // Delete old photo from Cloudinary if stored
        if ($admin->profile_path && str_starts_with($admin->profile_path, 'https://res.cloudinary.com')) {
            $publicId = pathinfo(parse_url($admin->profile_path, PHP_URL_PATH), PATHINFO_FILENAME);
            (new Cloudinary())->uploadApi()->destroy('profile/' . $publicId);
        }

        // Prevent deleting themselves or another super admin
        if ($admin->id === Auth::id()) {
            return response()->json([
                'message' => 'You cannot delete your own account.'
            ], 403);
        }

        if ($admin->super_admin) {
            return response()->json([
                'message' => 'You cannot delete another super admin.'
            ], 403);
        }

        $admin->delete();

        return response()->json([
            'message' => 'Admin deleted successfully'
        ]);
    }

    public function setSuperAdmin(Request $request)
    {
        // Check super admin privilege
        //if (!Auth::user()->super_admin) {
        //    return response()->json([
        //        'message' => 'Unauthorized. Only super admins can set super admin status.'
        //    ], 403);
        //}

        $admin = Admin::findOrFail(Auth::id());


        $admin->super_admin = true;
        $admin->save();

        return response()->json([
            'message' => 'Super admin status updated successfully',
            'admin' => $admin
        ]);
    }

    public function checkIfSuperAdmin(){
        if (!Auth::user()->super_admin) {
            return response()->json([
                'message' => 'Unauthorized. Only super admins can view and manage this page'
            ], 401);
        }
    }
}
