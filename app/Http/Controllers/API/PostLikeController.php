<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\PostLike;
use Illuminate\Support\Facades\Auth;

class PostLikeController extends Controller
{
    public function toggleLike(Request $request, $id)
    {
        $post = Post::findOrFail($id);
        $userId = Auth::id();

        // Check if like exists
        $like = PostLike::where('post_id', $id)
                       ->where('user_id', $userId)
                       ->first();

        if ($like) {
            $like->delete();
            $action = 'unliked';
        } else {
            PostLike::create([
                'post_id' => $id,
                'user_id' => $userId
            ]);
            $action = 'liked';
        }

        return response()->json([
            'status' => 'success',
            'action' => $action,
            'likes_count' => $post->postLikes()->count(),
            'is_liked' => $action === 'liked'
        ]);
    }

    // Get like status
    public function getLikeStatus($postId)
    {
        $post = Post::findOrFail($postId);

        return response()->json([
            'likes_count' => $post->postLikes()->count(),
            'is_liked' => $post->postLikes()
                             ->where('user_id', Auth::id())
                             ->exists()
        ]);
    }
}
