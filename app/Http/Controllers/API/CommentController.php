<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth;
class CommentController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'announcement_id' => 'required|exists:announcements,id',
            'content' => 'required|string',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        $comment = Comment::create([
            'announcement_id' => $request->announcement_id,
            'user_id' => Auth::id(), // Assumes user is logged in
            'parent_id' => $request->parent_id,
            'content' => $request->content,
        ]);

        return response()->json($comment, 201);
    }

    public function destroy($id)
    {
        $comment = Comment::findOrFail($id);
        $comment->delete();

        return response()->json(['message' => 'Comment deleted.']);
    }
}
