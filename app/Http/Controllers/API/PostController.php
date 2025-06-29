<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use APP\Models\Post;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    public function index()
    {
        return Post::with('user', 'comments')->latest()->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        return Post::create([
            'user_id' => Auth::id(),
            'title'   => $request->title,
            'content' => $request->content,
            'status'  => 'pending',
        ]);
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

        return $post;
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

    // Optionally restrict who can update status here, e.g. only admins or post owner
    // For example, if admin-only, check Auth::guard('admin-api')->check()

    $post->status = $request->status;
    $post->save();

    return response()->json(['message' => 'Post status updated', 'post' => $post]);
}
}
