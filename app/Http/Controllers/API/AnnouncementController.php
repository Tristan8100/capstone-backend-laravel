<?php

namespace App\Http\Controllers\API;
use App\Models\AnnouncementImage;
use App\Models\Announcement;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\File;

class AnnouncementController extends Controller
{
    public function index()
    {
        return Announcement::with([
        'images',
        'comments.user:id,first_name,middle_name,last_name,profile_path', // only fetch what's needed
        'comments.replies.user:id,first_name,middle_name,last_name,profile_path' // Include user info for each reply
    ])->latest()->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $announcement = Announcement::create([
            'admin_id' => Auth::guard('admin-api')->id(),
            'title' => $request->title,
            'content' => $request->content,
        ]);

        if ($request->hasFile('images')) {
            $directory = public_path('announcement_images');

            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            $manager = new ImageManager(new Driver());
            $imageFiles = $request->file('images');

            if (!is_array($imageFiles)) {
                return response()->json(['error' => 'Uploaded images are not in an array format.'], 422);
            }

            foreach ($imageFiles as $imageFile) {
                if (!$imageFile->isValid()) {
                    continue; // Skip invalid uploads
                }

                $filename = time() . '_' . uniqid() . '.' . $imageFile->getClientOriginalExtension();
                $fullPath = $directory . DIRECTORY_SEPARATOR . $filename;
                $relativePath = 'announcement_images/' . $filename;

                try {
                    // Move original file
                    $imageFile->move($directory, $filename);

                    // Resize and compress
                    $image = $manager->read($fullPath);
                    $image->resize(800, 800, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    })->save($fullPath, 80);

                    // Save image record
                    AnnouncementImage::create([
                        'announcement_id' => $announcement->id,
                        'image_name' => $filename,
                        'image_file' => $relativePath,
                    ]);
                } catch (\Exception $e) {
                    return response()->json([
                        'error' => 'Failed to process image.',
                        'details' => $e->getMessage()
                    ], 500);
                }
            }
        }

        return response()->json(['message' => 'Announcement created successfully.'], 201);
    }


    public function show($id)
    {
        return Announcement::with('images', 'comments.replies')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $announcement = Announcement::findOrFail($id);

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'images.*' => 'image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $announcement->update($request->only(['title', 'content']));

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $imageFile) {
                $filename = time() . '_' . $imageFile->getClientOriginalName();
                $imageFile->move(public_path('announcement_images'), $filename);

                AnnouncementImage::create([
                    'announcement_id' => $announcement->id,
                    'image_name' => $filename,
                    'image_file' => 'announcement_images/' . $filename,
                ]);
            }
        }

        return response()->json(['message' => 'Announcement updated.']);
    }

    public function destroy($id)
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->delete();

        return response()->json(['message' => 'Announcement deleted.']);
    }
}
