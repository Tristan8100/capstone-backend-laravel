<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AlumniList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Models\EmailVerification;
use Illuminate\Support\Str;
use App\Models\Course;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\File;
class AuthenticationController extends Controller
{
    /**
     * Register a new account.
     */
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'student_id'  => 'required|integer',
                'first_name'  => 'required|string|min:2',
                'middle_name' => 'nullable|string|min:1',
                'last_name'   => 'required|string|min:2',
                'email'       => 'required|string|email|max:255|unique:users',
                'password'    => 'required|string|min:8',
                'batch'       => 'required|integer|digits:4',
                'course_name' => 'required|string|exists:courses,name',
            ]);

            // Find matching alumni record with course_name check
            $alumniMatch = AlumniList::where('first_name', strtoupper($validated['first_name']))
            ->where('last_name', strtoupper($validated['last_name']))
            ->where('batch', $validated['batch'])
            ->where('student_id', $validated['student_id'])
            ->where('course', strtoupper($validated['course_name']))
            ->first();

            if (!$alumniMatch) {
                return response()->json([
                    'response_code' => 403,
                    'status'        => 'error',
                    'message'       => 'You are not listed as an alumni or course does not match. Registration is restricted.',
                ], 403);
            }

            // Find course by name from Course model to get FK
            $course = Course::where('name', $validated['course_name'])->first();

            if (!$course) {
                return response()->json([
                    'response_code' => 404,
                    'status'        => 'error',
                    'message'       => 'Course not found.',
                ], 404);
            }

            // Create user with course_id foreign key
            $user = User::create([
                'id'          => $alumniMatch->student_id, // keep your ID logic
                'first_name'  => $alumniMatch->first_name,
                'middle_name' => $alumniMatch->middle_name ?? null,
                'last_name'   => $alumniMatch->last_name,
                'email'       => $validated['email'],
                'password'    => Hash::make($validated['password']),
                'course_id'   => $course->id, // save FK here
            ]);

            // Generate QR code URL
            $frontendUrl = env('FRONTEND_URL'); // don't forget on env ah
            $qrUrl = $frontendUrl . '/' . $user->id;

            // Build file path
            $folder = public_path('qrcodes');
            File::ensureDirectoryExists($folder); // ensure folder exists

            $filename = $user->id . '.svg';
            $filepath = $folder . '/' . $filename;//can be changed

            // Generate and save the QR code
            QrCode::format('svg')->size(300)->generate($qrUrl, $filepath);

            // Save the relative path to the user
            $user->qr_code_path = 'qrcodes/' . $filename;
            $user->save();

            // Generate 6-digit OTP
            $otp = (string) rand(100000, 999999);

            // Create or update OTP record
            EmailVerification::updateOrCreate(
                ['email' => $user->email],
                [
                    'otp_hash'       => Hash::make($otp),
                    'verified'  => false,
                    'created_at'=> now(),
                    'updated_at'=> now(),
                ]
            );

            // Send OTP email
            Mail::raw("Your verification OTP is: $otp. It expires in 10 minutes.", function ($message) use ($user) {
                $message->to($user->email)
                        ->subject('Email Verification OTP');
            });

            return response()->json(['message' => 'OTP sent to your email.', 'email' => $user->email]);

        } catch (ValidationException $e) {
            return response()->json([
                'response_code' => 422,
                'status'        => 'error',
                'message'       => 'Validation failed',
                'errors'        => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Registration Error: ' . $e->getMessage());

            return response()->json([
                'response_code' => 500,
                'status'        => 'error',
                'message'       => 'Registration failed',
            ], 500);
        }
    }

    public function findprofile(Request $request)
    {
        $validated = $request->validate([
            'student_id'  => 'required|integer',
            'first_name'  => 'required|string|min:2',
            'middle_name' => 'nullable|string|min:1',
            'last_name'   => 'required|string|min:2',
            'batch'       => 'required|integer|digits:4',
            'course_name' => 'required|string|exists:courses,name',
        ]);

        $alumniMatch = AlumniList::where('first_name', strtoupper($validated['first_name']))
            ->where('last_name', strtoupper($validated['last_name']))
            ->where('batch', $validated['batch'])
            ->where('student_id', $validated['student_id'])
            ->where('course', strtoupper($validated['course_name']))
            ->first();

        if (!$alumniMatch) {
            return response()->json([
                'response_code' => 403,
                'status'        => 'error',
                'message'       => 'You are not listed as an alumni or course does not match. Registration is restricted.',
            ], 403);
        }

        return response()->json([
            'response_code' => 200,
            'status'        => 'success',
            'message'       => 'Profile found',
            'data'          => [
                'student_id'  => $alumniMatch->student_id,
                'first_name'  => $alumniMatch->first_name,
                'middle_name' => $alumniMatch->middle_name,
                'last_name'   => $alumniMatch->last_name,
                'batch'       => $alumniMatch->batch,
                'course'      => $alumniMatch->course,
            ],
        ]);
    }


    /**
     * Login and return auth token.
     */
    public function login(Request $request)
    {
        try {
            $credentials = $request->validate([
                'email'    => 'required|email',
                'password' => 'required|string',
            ]);

            $user = User::where('email', $credentials['email'])->first();

            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                return response()->json([
                    'response_code' => 401,
                    'status' => 'error',
                    'message' => 'Unauthorized',
                ], 401);
            }

            if (!$user->email_verified_at) {
                return response()->json([
                    'response_code' => 401,
                    'status'        => 'error',
                    'message'       => 'Email not verified',
                ], 401);
            }

            $user->tokens()->delete();
            $token = $user->createToken('authToken')->plainTextToken;

            return response()->json([
                'response_code' => 200,
                'status'        => 'success',
                'message'       => 'Login successful',
                'token'       => $token,
                'token_type'  => 'Bearer',
                'user_info'   => [
                    'id'    => $user->id,
                    'name'  => $user->first_name . ' ' . $user->last_name,
                    'email' => $user->email,
                    'course' => $user->course ? $user->course->name : null,
                    'qr_code_path' => $user->qr_code_path, // include QR code path
                    'profile_path' => $user->profile_path,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'response_code' => 422,
                'status'        => 'error',
                'message'       => 'Validation failed',
                'errors'        => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Login Error: ' . $e->getMessage());

            return response()->json([
                'response_code' => 500,
                'status'        => 'error',
                'message'       => 'Login failed',
            ], 500);
        }
    }

    /**
     * Get list of users (paginated) — protected route.
     */
    public function userInfo()
    {
        try {
            $users = User::latest()->paginate(10);

            return response()->json([
                'response_code'  => 200,
                'status'         => 'success',
                'message'        => 'Fetched user list successfully',
                'data_user_list' => $users,
            ]);
        } catch (\Exception $e) {
            Log::error('User List Error: ' . $e->getMessage());

            return response()->json([
                'response_code' => 500,
                'status'        => 'error',
                'message'       => 'Failed to fetch user list',
            ], 500);
        }
    }

    /**
     * Logout user and revoke tokens — protected route.
     */
    public function logOut(Request $request)
    {
        try {
            $user = $request->user();

            if ($user) {
                $user->tokens()->delete();

                return response()->json([
                    'response_code' => 200,
                    'status'        => 'success',
                    'message'       => 'Successfully logged out',
                ]);
            }

            return response()->json([
                'response_code' => 401,
                'status'        => 'error',
                'message'       => 'User not authenticated',
            ], 401);
        } catch (\Exception $e) {
            Log::error('Logout Error: ' . $e->getMessage());

            return response()->json([
                'response_code' => 500,
                'status'        => 'error',
                'message'       => 'An error occurred during logout',
            ], 500);
        }
    }

    public function verifyToken()
    {
        try {
            if (Auth::check()) {

                return response()->json([
                    'response_code' => 200,
                    'status'        => 'success',
                    'user_info'   => [
                        'id'    => Auth::user()->id,
                        'name'  => Auth::user()->full_name, //from getFullNameAttribute() both models, idk it's weird
                        'email' => Auth::user()->email,
                        'course' => Auth::user()->course ? Auth::user()->course->name : null,
                        'qr_code_path' => Auth::user()->qr_code_path, // include QR code path
                        'profile_path' => Auth::user()->profile_path,
                    ],
                ]);
            }

            return response()->json([
                'response_code' => 401,
                'status'        => 'error',
                'message'       => 'User not authenticated',
            ], 401);
        } catch (\Exception $e) {
            Log::error('Verify Error: ' . $e->getMessage());

            return response()->json([
                'response_code' => 500,
                'status'        => 'error',
                'message'       => 'An error occurred during Verifying token',
            ], 500);
        }
    }
}