<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use Illuminate\Support\Facades\Auth;
use App\Models\PostImage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Cloudinary\Cloudinary;
use Illuminate\Support\Facades\Log;


class PostController extends Controller
{
    public function index()
    {
        return Post::with('images', 'user',
        'comments.user:id,first_name,middle_name,last_name,profile_path', // only fetch what's needed
        'comments.replies.user:id,first_name,middle_name,last_name,profile_path' // Include user info for each reply
        )->latest()->get();
    }

    public function indexStatus($status)
    {
        if (!in_array($status, ['pending', 'accepted', 'declined'])) {
            return response()->json(['error' => 'Invalid status'], 400);
        }

        return Post::with('images', 'user',
        'comments.user:id,first_name,middle_name,last_name,profile_path', // only fetch what's needed
        'comments.replies.user:id,first_name,middle_name,last_name,profile_path' // Include user info for each reply
        )->where('status', $status)->latest()->get();
    }

    public function indexStatusMyPost($status)
    {
        if (!in_array($status, ['pending', 'accepted', 'declined'])) {
            return response()->json(['error' => 'Invalid status'], 400);
        }

        return Post::with('images', 'user',
        'comments.user:id,first_name,middle_name,last_name,profile_path', // only fetch what's needed
        'comments.replies.user:id,first_name,middle_name,last_name,profile_path' // Include user info for each reply
        )->where('status', $status)->where('user_id', Auth::id())->latest()->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
            'images'  => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // Create post first
        $post = Post::create([
            'user_id' => Auth::id(),
            'title'   => $request->title,
            'content' => $request->content,
            'status'  => 'pending',
        ]);

        if ($request->hasFile('images')) {
            $manager = new ImageManager(new Driver());
            $cloudinary = new Cloudinary();

            foreach ($request->file('images') as $imageFile) {
                if (!$imageFile->isValid()) {
                    continue;
                }

                try {
                    // Process image (resize + optimize)
                    $image = $manager->read($imageFile->getRealPath())
                        ->resize(800, 800, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        })
                        ->toJpeg(80);

                    // Generate structured public ID
                    $publicId = 'post_' . $post->id . '_' . Str::random(8);
                    
                    // Upload to Cloudinary
                    $upload = $cloudinary->uploadApi()->upload($image->toDataUri(), [
                        'folder' => 'posts',
                        'public_id' => $publicId,
                        'overwrite' => true,
                    ]);

                    // Save image record
                    PostImage::create([
                        'post_id'    => $post->id,
                        'image_name' => $publicId,
                        'image_file' => $upload['secure_url'],
                    ]);
                } catch (\Exception $e) {
                    return response()->json([
                        'error' => 'Failed to upload image',
                        'message' => $e->getMessage()
                    ], 500);
                }
            }
        }

        return response()->json([
            'message' => 'Post created successfully',
            'post' => $post->load('images')
        ], 201);
    }

    public function show($id)
    {
        return Post::with('user', 'comments.replies')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $post = Post::where('id', $id)->where('user_id', Auth::id())->firstOrFail();

        $request->validate([
            'title'   => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
        ]);

        $post->update($request->only('title', 'content'));

        return response()->json(['message' => 'Post updated.']);
    }


    public function destroy($id)
    {
        $post = Post::with('images')
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Delete all associated images from Cloudinary and database
        if ($post->images->isNotEmpty()) {
            $cloudinary = new Cloudinary();
            
            foreach ($post->images as $image) {
                try {
                    // Extract public_id from URL (consistent with your pattern)
                    $path = parse_url($image->image_file, PHP_URL_PATH);
                    $publicId = pathinfo($path, PATHINFO_FILENAME);
                    
                    // Delete from Cloudinary
                    $cloudinary->uploadApi()->destroy('posts/' . $publicId);
                    
                    // Delete database record
                    $image->delete();
                } catch (\Exception $e) {
                    Log::error("Failed to delete post image {$image->id}: " . $e->getMessage());
                    continue;
                }
            }
        }

        // Delete the main post record
        $post->delete();

        return response()->json([
            'message' => 'Post and all associated images deleted successfully'
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,accepted,declined',
        ]);

        $post = Post::findOrFail($id);

        $post->status = $request->status;
        $post->save();

        return response()->json(['message' => 'Post status updated', 'post' => $post]);
    }
}
