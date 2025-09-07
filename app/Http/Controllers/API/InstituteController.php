<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Institute;
use Illuminate\Support\Str;
use Cloudinary\Cloudinary;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Log;

class InstituteController extends Controller
{
    public function index(Request $request)
    {
        $query = Institute::query()
            ->withCount('courses')
            ->orderBy('created_at', 'desc');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Pagination (default 15 per page, max 100)
        $perPage = min((int) $request->input('per_page', 15), 100);
        $institutes = $query->paginate($perPage);

        return response()->json([
            'data' => $institutes->items(),
            'meta' => [
                'current_page' => $institutes->currentPage(),
                'last_page' => $institutes->lastPage(),
                'per_page' => $institutes->perPage(),
                'total' => $institutes->total(),
            ],
        ]);
    }

    public function general()
    {
        return Institute::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:institutes,name',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $id = Str::uuid();
        $imagePath = null;

        if ($request->hasFile('image')) {
            $manager = new ImageManager(new Driver());
            $image = $manager->read($request->file('image')->getRealPath())
                            ->resize(600, 600, function ($constraint) {
                                $constraint->aspectRatio();
                                $constraint->upsize();
                            })
                            ->toJpeg();

            $cloudinary = new Cloudinary();
            $upload = $cloudinary->uploadApi()->upload($image->toDataUri(), [
                'folder' => 'institutes',
                'public_id' => 'institute_' . $id,
                'overwrite' => true,
            ]);

            $imagePath = $upload['secure_url'];
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
            // Delete old image from Cloudinary if stored there
            if ($institute->image_path && str_starts_with($institute->image_path, 'https://res.cloudinary.com')) {
                $publicId = pathinfo(parse_url($institute->image_path, PHP_URL_PATH), PATHINFO_FILENAME);
                (new Cloudinary())->uploadApi()->destroy('institutes/' . $publicId);
            }

            // Resize and upload new image to Cloudinary
            $manager = new ImageManager(new Driver());
            $image = $manager->read($request->file('image')->getRealPath())->cover(500, 500)->toJpeg();

            $cloudinary = new Cloudinary();
            $upload = $cloudinary->uploadApi()->upload($image->toDataUri(), [
                'folder' => 'institutes',
                'public_id' => 'institute_' . $institute->id . '_' . time(),
                'overwrite' => true,
            ]);

            $validated['image_path'] = $upload['secure_url'];
        }

        $institute->update($validated);

        return response()->json([
            'message' => 'Institute updated successfully.',
            'institute' => $institute,
        ]);
    }

    public function destroy($id)
    {
        $institute = Institute::findOrFail($id);

        if ($institute->image_path && str_starts_with($institute->image_path, 'https://res.cloudinary.com')) {
            try{
                $publicId = pathinfo(parse_url($institute->image_path, PHP_URL_PATH), PATHINFO_FILENAME);
                (new Cloudinary())->uploadApi()->destroy('institutes/' . $publicId);
            }catch (\Exception $e) {
                Log::error("Failed to delete post image {$institute->id}: " . $e->getMessage());
            }
        }

        $institute->delete();

        return response()->noContent();
    }
}
