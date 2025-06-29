<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\PostComment;
use Illuminate\Support\Facades\Auth;
class PostCommentController extends Controller
{
    public function index($postId)
    {
        $post = Post::findOrFail($postId);

        return $post->comments()->with('user', 'replies.user')->whereNull('parent_id')->get();
    }

    public function store(Request $request, $postId)
    {
        $request->validate([
            'content'    => 'required|string',
            'parent_id'  => 'nullable|exists:post_comments,id',
        ]);

        $post = Post::findOrFail($postId);

        return PostComment::create([
            'post_id'   => $post->id,
            'user_id'   => Auth::id(),
            'content'   => $request->content,
            'parent_id' => $request->parent_id,
        ]);
    }
}
