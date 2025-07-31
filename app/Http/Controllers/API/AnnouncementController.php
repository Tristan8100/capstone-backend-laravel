<?php

namespace App\Http\Controllers\API;
use App\Models\AnnouncementImage;
use App\Models\Announcement;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Cloudinary\Cloudinary;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Log;
use App\Models\AnnouncementLike;

use Illuminate\Support\Facades\File;

class AnnouncementController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $perPage = 3;

        $announcements = Announcement::with([
                'images',
                'comments.user:id,first_name,middle_name,last_name,profile_path',
                'comments.replies.user:id,first_name,middle_name,last_name,profile_path'
            ])
            ->withCount('likes as likes_count')
            ->addSelect([
                'is_liked' => AnnouncementLike::selectRaw('COUNT(*) > 0')
                    ->whereColumn('announcement_id', 'announcements.id')
                    ->where('user_id', $userId)
            ])
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'data' => $announcements->items(),
            'pagination' => [
                'current_page' => $announcements->currentPage(),
                'last_page' => $announcements->lastPage(),
                'per_page' => $announcements->perPage(),
                'total' => $announcements->total(),
                'next_page_url' => $announcements->nextPageUrl(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // Create announcement first
        $announcement = Announcement::create([
            'admin_id' => Auth::guard('admin-api')->id(),
            'title' => $request->title,
            'content' => $request->content,
        ]);

        if ($request->hasFile('images')) {
            $manager = new ImageManager(new Driver());
            $cloudinary = new Cloudinary();
            
            foreach ($request->file('images') as $imageFile) {
                if (!$imageFile->isValid()) {
                    continue; // Skip invalid uploads
                }

                try {
                    // Process image
                    $image = $manager->read($imageFile->getRealPath())->toJpeg(80);

                    //upload to Cloudinary with structured public ID
                    $publicId = 'announcement_' . $announcement->id . '_' . Str::random(8);
                    $upload = $cloudinary->uploadApi()->upload($image->toDataUri(), [
                        'folder' => 'announcements',
                        'public_id' => $publicId,
                        'overwrite' => true,
                    ]);

                    // Save image record with Cloudinary URL
                    AnnouncementImage::create([
                        'announcement_id' => $announcement->id,
                        'image_name' => $publicId,
                        'image_file' => $upload['secure_url'],
                    ]);
                } catch (\Exception $e) {
                    return response()->json([
                        'error' => 'Failed to process image.',
                        'details' => $e->getMessage()
                    ], 500);
                }
            }
        }

        return response()->json([
            'message' => 'Announcement created successfully.',
            'announcement' => $announcement
        ], 201);
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
        ]);

        // Update basic fields
        $announcement_updated = $announcement->update($request->only(['title', 'content']));

        return response()->json([
            'message' => 'Announcement updated',
            'announcement' => $announcement_updated
        ]);
    }

    public function destroy($id)
    {
        $announcement = Announcement::findOrFail($id);
        
        // Get all associated images
        $images = AnnouncementImage::where('announcement_id', $announcement->id)->get();
        
        if ($images->isNotEmpty()) {
            $cloudinary = new Cloudinary();
            
            foreach ($images as $image) {
                try {
                    // Extract public_id from URL (your consistent method)
                    $path = parse_url($image->image_file, PHP_URL_PATH);
                    $publicId = pathinfo($path, PATHINFO_FILENAME);
                    
                    // Delete from Cloudinary
                    $cloudinary->uploadApi()->destroy('announcements/' . $publicId);
                    
                    // Delete database record
                    $image->delete();
                } catch (\Exception $e) {
                    Log::error("Failed to delete post image {$image->id}: " . $e->getMessage());
                    continue;
                }
            }
        }
        
        // Delete the main announcement
        $announcement->delete();
        
        return response()->json([
            'message' => 'Announcement and all associated images deleted successfully.'
        ]);
    }
}
