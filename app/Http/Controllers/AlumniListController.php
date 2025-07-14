<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AlumniList as Alumni;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\AlumniImport;
use Illuminate\Support\Facades\DB;

class AlumniListController extends Controller
{
    // Get paginated alumni with search
    public function index(Request $request)
    {
        $query = Alumni::query();

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('first_name', 'like', '%'.$searchTerm.'%')
                  ->orWhere('last_name', 'like', '%'.$searchTerm.'%')
                  ->orWhere('student_id', 'like', '%'.$searchTerm.'%');
            });
        }

        // Pagination
        $perPage = $request->per_page ?? 5; // Default to 5 items per page
        $alumni = $query->paginate($perPage);

        return response()->json([
            'data' => $alumni->items(),
            'meta' => [
                'current_page' => $alumni->currentPage(),
                'last_page' => $alumni->lastPage(),
                'per_page' => $alumni->perPage(),
                'total' => $alumni->total(),
            ]
        ]);
    }

    // Get one alumni by ID
    public function show($id)
    {
        $alumni = Alumni::find($id);

        if (!$alumni) {
            return response()->json(['message' => 'Alumni not found'], 404);
        }

        return response()->json($alumni);
    }

    // Create new alumni
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id'   => 'required|unique:alumni_list,student_id',
            'first_name'   => 'required|string',
            'middle_name'  => 'nullable|string',
            'last_name'    => 'required|string',
            'course'       => 'required|string',
            'batch'        => 'required|integer|digits:4',
            'status'       => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $alumni = Alumni::create($request->all());

        return response()->json($alumni, 201);
    }

    // Update alumni
    public function update(Request $request, $id)
    {
        $alumni = Alumni::find($id);

        if (!$alumni) {
            return response()->json(['message' => 'Alumni not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'student_id'   => 'sometimes|required|unique:alumni_list,student_id,' . $id,
            'first_name'   => 'sometimes|required|string',
            'middle_name'  => 'nullable|string',
            'last_name'    => 'sometimes|required|string',
            'course'       => 'sometimes|required|string',
            'batch'        => 'sometimes|required|integer|digits:4',
            'status'       => 'sometimes|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $alumni->update($request->all());

        return response()->json($alumni);
    }

    // Delete alumni
    public function destroy($id)
    {
        $alumni = Alumni::find($id);

        if (!$alumni) {
            return response()->json(['message' => 'Alumni not found'], 404);
        }

        $alumni->delete();

        return response()->json(['message' => 'Alumni deleted']);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,txt'
        ]);

        Excel::import(new AlumniImport, $request->file('file'));

        return response()->json(['message' => 'Alumni imported successfully']);
    }
}