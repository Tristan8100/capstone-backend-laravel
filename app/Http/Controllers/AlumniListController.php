<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AlumniList as Alumni;
use Illuminate\Support\Facades\Validator;

class AlumniListController extends Controller
{
    // Get all alumni
    public function index()
    {
        return response()->json(Alumni::all());
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
}
