<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\AlumniList;

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

    public function alumniAnalytics()
    {
        return response()->json([
            'status_counts' => AlumniList::groupBy('status')
                ->select('status', DB::raw('count(*) as total'))
                ->get(),
            'batch_distribution' => AlumniList::groupBy('batch')
                ->select('batch', DB::raw('count(*) as total'))
                ->orderBy('batch')
                ->get(),
            'course_distribution' => AlumniList::groupBy('course')
                ->select('course', DB::raw('count(*) as total'))
                ->orderByDesc('total')
                ->get(),
            'recent_grads_count' => AlumniList::where('batch', '>=', now()->subYears(5)->year)->count(),
            'batch_stats' => [
                'earliest' => AlumniList::min('batch'),
                'latest' => AlumniList::max('batch'),
                'average' => round(AlumniList::avg('batch')),
            ],
            'common_last_names' => AlumniList::groupBy('last_name')
                ->select('last_name', DB::raw('count(*) as total'))
                ->orderByDesc('total')
                ->limit(5)
                ->get()
        ]);
    }
}
