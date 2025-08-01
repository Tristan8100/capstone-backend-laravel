<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class AnalyticsController extends Controller
{
    public function postAnalytics(Request $request)
    {
        $pendingCount = Post::where('status', 'pending')->count();

        $postsTrend = Post::select(
        DB::raw('DATE(created_at) as date'),
        DB::raw('count(*) as total')
        )
        ->groupBy('date')
        ->orderBy('date')
        ->limit(7)
        ->get();

        $topUsers = User::withCount('posts')
        ->orderByDesc('posts_count')
        ->limit(10)
        ->get();

        $commonWords = Post::select('title')
        ->get()
        ->flatMap(fn ($post) => str_word_count(strtolower($post->title), 1))
        ->countBy()
        ->sortDesc()
        ->take(10);

        $totalPosts = Post::count();
        $pendingRatio = ($pendingCount / $totalPosts) * 100;

        return response()->json([
            'pending_posts_count' => $pendingCount,
            'posts_trend' => $postsTrend,
            'top_users' => $topUsers,
            'common_words' => $commonWords,
            'pending_ratio' => $pendingRatio,
        ]);
    }
}
