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

        $post = Post::create([
            'user_id' => Auth::id(),
            'title'   => $request->title,
            'content' => $request->content,
            'status'  => 'pending',
        ]);

        if ($request->hasFile('images')) {
            $directory = public_path('post_images');
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            $manager = new ImageManager(new Driver());
            foreach ($request->file('images') as $imageFile) {
                if (!$imageFile->isValid()) continue;

                $filename = time() . '_' . uniqid() . '.' . $imageFile->getClientOriginalExtension();
                $fullPath = $directory . DIRECTORY_SEPARATOR . $filename;
                $relativePath = 'post_images/' . $filename;

                $imageFile->move($directory, $filename);

                $image = $manager->read($fullPath);
                $image->resize(800, 800, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })->save($fullPath, 80);

                PostImage::create([
                    'post_id'    => $post->id,
                    'image_name' => $filename,
                    'image_file' => $relativePath,
                ]);
            }
        }

        return response()->json(['message' => 'Post created with images.'], 201);
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
            'images.*' => 'image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $post->update($request->only('title', 'content'));

        if ($request->hasFile('images')) {
            $directory = public_path('post_images');
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            $manager = new ImageManager(new Driver());
            foreach ($request->file('images') as $imageFile) {
                if (!$imageFile->isValid()) continue;

                $filename = time() . '_' . uniqid() . '.' . $imageFile->getClientOriginalExtension();
                $fullPath = $directory . DIRECTORY_SEPARATOR . $filename;
                $relativePath = 'post_images/' . $filename;

                $imageFile->move($directory, $filename);

                $image = $manager->read($fullPath);
                $image->resize(800, 800, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })->save($fullPath, 80);

                PostImage::create([
                    'post_id'    => $post->id,
                    'image_name' => $filename,
                    'image_file' => $relativePath,
                ]);
            }
        }

        return response()->json(['message' => 'Post updated.']);
    }


    public function destroy($id)
    {
        $post = Post::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $post->delete();

        return response()->json(['message' => 'Post deleted successfully']);
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
