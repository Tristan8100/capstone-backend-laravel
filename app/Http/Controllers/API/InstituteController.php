<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Institute;
use Illuminate\Support\Str;
class InstituteController extends Controller
{
    public function index()
    {
        return Institute::all();
    }

    public function store(Request $request)
    {
       $validated = $request->validate([
        'name' => 'required|string|max:255',
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
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $filename = $institute->id . '.' . $request->image->extension();
            $path = $request->image->storeAs('institute_images', $filename, 'public');
            $validated['image_path'] = 'storage/' . $path;
        }

        $institute->update($validated);
        return $institute;
    }

    public function destroy($id)
    {
        $institute = Institute::findOrFail($id);
        $institute->delete();

        return response()->noContent();
    }
}
