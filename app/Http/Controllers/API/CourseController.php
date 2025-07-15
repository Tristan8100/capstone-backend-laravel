<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use Illuminate\Support\Str;
class CourseController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $instituteId = $request->query('institute_id');
        $perPage = $request->query('per_page', 10);

        $query = Course::with('institute');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', '%' . $search . '%')
                ->orWhere('full_name', 'LIKE', '%' . $search . '%');
            });
        }

        if ($instituteId) {
            $query->where('institute_id', $instituteId);
        }

        $courses = $query->paginate($perPage);

        return response()->json($courses);
    }

    public function index2()
    {
        return Course::with('institute')->get();
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:courses,name',
            'full_name' => 'required|string|max:255',
            'institute_id' => 'required|exists:institutes,id',
        ]);

        $course = Course::create([
            'id' => Str::uuid(),
            'name' => $validated['name'],
            'full_name' => $validated['full_name'],
            'institute_id' => $validated['institute_id'],
        ]);

        return response()->json($course, 201);
    }

    public function show($id)
    {
        $course = Course::with('institute')->findOrFail($id);
        return $course;
    }

    public function update(Request $request, $id)
    {
        $course = Course::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:50',
            'full_name' => 'sometimes|required|string|max:255',
            'institute_id' => 'sometimes|required|exists:institutes,id',
        ]);

        $course->update($validated);
        return $course;
    }

    public function destroy($id)
    {
        $course = Course::findOrFail($id);
        $course->delete();

        return response()->noContent();
    }
}
