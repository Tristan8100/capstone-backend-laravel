<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Institute;
use Illuminate\Support\Str;
class InstituteController extends Controller
{

    

public function index(Request $request)
{
    $query = Institute::query();
    
    // Simple search
    if ($request->has('search')) {
        $search = $request->search;
        $query->where('name', 'LIKE', "%{$search}%")
              ->orWhere('description', 'LIKE', "%{$search}%");
    }
    
    // Pagination (15 items per page)
    $institutes = $query->paginate(15);
    
    // Make sure to return JSON response
    return response()->json($institutes);
}
    public function store(Request $request)
    {
       $validated = $request->validate([
        'name' => 'required|string|max:255|unique:institutes,name',
        'description' => 'nullable|string',
        'image' => 'nullable|image|max:2048',
        ]);

        $id = Str::uuid();
        $imagePath = null;

        if ($request->hasFile('image')) {
        $filename = $id . '.' . $request->image->extension();
        $destinationPath = public_path('institute_images');

        // Ensure directory exists
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        // Move image to public folder
        $request->image->move($destinationPath, $filename);

        // Store relative path to DB
        $imagePath = 'institute_images/' . $filename;
    }

    $institute = Institute::create([
        'id' => $id,
        'name' => $validated['name'],
        'description' => $validated['description'] ?? null,
        'image_path' => $imagePath,
    ]);

    return response()->json($institute, 201);
    }

    public function show($id)
    {
        $institute = Institute::findOrFail($id);
        return $institute;
    }

    public function update(Request $request, $id)
    {
        $institute = Institute::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|file|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($institute->image_path && file_exists(public_path($institute->image_path))) {
                unlink(public_path($institute->image_path));
            }

            $filename = $institute->id . '_' . time() . '.' . $request->file('image')->extension();
            $destinationPath = public_path('institute_images');

            // Ensure the directory exists
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $request->file('image')->move($destinationPath, $filename);
            $validated['image_path'] = 'institute_images/' . $filename;
        }

        $institute->update($validated);

        return response()->json([
            'message' => 'Institute updated successfully',
            'institute' => $institute,
        ]);
    }

    public function destroy($id)
    {
        $institute = Institute::findOrFail($id);
        $institute->delete();

        return response()->noContent();
    }
}
