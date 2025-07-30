<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Announcement;
use App\Models\AnnouncementLike;
use Illuminate\Support\Facades\Auth;

class AnnouncementLikeController extends Controller
{
    public function toggleLike(Request $request, $announcementId)
    {
        $announcement = Announcement::findOrFail($announcementId);
        $userId = Auth::id();

        $like = AnnouncementLike::where('announcement_id', $announcementId)
                               ->where('user_id', $userId)
                               ->first();

        if ($like) {
            $like->delete();
            $action = 'unliked';
        } else {
            AnnouncementLike::create([
                'announcement_id' => $announcementId,
                'user_id' => $userId
            ]);
            $action = 'liked';
        }

        return response()->json([
            'status' => 'success',
            'action' => $action,
            'likes_count' => $announcement->likes()->count(),
            'is_liked' => $action === 'liked'
        ]);
    }
}
