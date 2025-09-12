<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Course;
use App\Models\Institute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin;

class AccountController extends Controller
{
    /**
     * Display a listing of the alumni.
     */
    public function index(Request $request)
    {
        $query = User::query()->with(['course.institute']);

        // Search by name or ID
        if ($searchTerm = $request->query('search')) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('id', 'like', "%{$searchTerm}%")
                  ->orWhere('first_name', 'like', "%{$searchTerm}%")
                  ->orWhere('last_name', 'like', "%{$searchTerm}%");
            });
        }

        // Filter by institute name
        if ($instituteName = $request->query('institute')) {
            $query->whereHas('course.institute', function ($q) use ($instituteName) {
                $q->where('name', $instituteName);
            });
        }

        // Filter by course name
        if ($courseName = $request->query('course')) {
            $query->whereHas('course', function ($q) use ($courseName) {
                $q->where('name', $courseName);
            });
        }

        // Pagination
        $perPage = $request->query('per_page', 5); // Default to 5 items per page, matches frontend
        $alumni = $query->paginate($perPage);

        // Manually format the data to match frontend's expectation
        $formattedAlumni = $alumni->map(function ($alumnus) {
            return [
                'id' => $alumnus->id,
                'firstName' => $alumnus->first_name,
                'middleName' => $alumnus->middle_name,
                'lastName' => $alumnus->last_name,
                'email' => $alumnus->email,
                'batch' => $alumnus->batch,
                'profile_path' => $alumnus->profile_path,
                'course' => $alumnus->course->name ?? null, // Get course name, or null if no course
                'institute' => $alumnus->course->institute->name ?? null, // Get institute name via course, or null
            ];
        });

        // Return the paginated data with meta information
        return response()->json([
            'data' => $formattedAlumni,
            'current_page' => $alumni->currentPage(),
            'last_page' => $alumni->lastPage(),
            'per_page' => $alumni->perPage(),
            'total' => $alumni->total(),
            //add later possible idk
        ]);
    }

    /**
     * Get a list of all unique institutes.
     */
    public function institutes()
    {
        $institutes = Institute::select('id', 'name')->get(); // Select only necessary fields
        return response()->json($institutes);
    }

    /**
     * Get a list of courses, optionally filtered by institute ID.
     */
    public function courses(Request $request)
    {
        $query = Course::select('id', 'name');

        if ($instituteId = $request->query('institute_id')) {
            $query->where('institute_id', $instituteId);
        }

        $courses = $query->get();
        return response()->json($courses);
    }

    /**
     * Get a single alumni record by ID.
     */
    public function show(string $id)
    {
        $alumnus = User::with(['course.institute'])->findOrFail($id);

        // Manually format the data
        $formattedAlumnus = [
            'id' => $alumnus->id,
            'firstName' => $alumnus->first_name,
            'lastName' => $alumnus->last_name,
            'email' => $alumnus->email,
            'course' => $alumnus->course->name ?? null,
            'institute' => $alumnus->course->institute->name ?? null,
            //add later possible idk
        ];

        return response()->json($formattedAlumnus);
    }

    public function ForceChangePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:8',
            'user_id' => 'required|exists:admins,id',
        ]);

        Admin::where('id', $request->user_id)->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Password changed successfully.',
            'success' => true,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Validation rules
        $validated = $request->validate([
            'first_name'   => ['nullable', 'string', 'max:255'],
            'middle_name'  => ['nullable', 'string', 'max:255'],
            'last_name'    => ['nullable', 'string', 'max:255'],
        ]);

        // Update user
        $user->update($validated);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user,
        ], 200);
    }
}