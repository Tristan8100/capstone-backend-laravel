<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\AlumniList;
use App\Models\Institute;
use App\Models\Course;
use App\Models\Survey;
use App\Models\Response;

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
            'recent_grads_count' => AlumniList::where('batch', '>=', now()->subYears(2)->year)->count(),
            'batch_stats' => [
                'earliest' => AlumniList::min('batch'),
                'latest' => AlumniList::max('batch'),
                'average' => round(AlumniList::avg('batch')),
                'most_common_batch' => AlumniList::groupBy('batch')
                    ->select('batch', DB::raw('count(*) as total'))
                    ->orderByDesc('total')
                    ->first()
                    ->batch,
            ],
            'common_last_names' => AlumniList::groupBy('last_name')
                ->select('last_name', DB::raw('count(*) as total'))
                ->orderByDesc('total')
                ->limit(5)
                ->get()
        ]);
    }

    public function userAnalytics()
    {
        $totalUsers = User::count();

        $userGrowth = User::select([
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total')
            ])
            ->where('created_at', '>=', now()->subDays(30)) //last 30 days
            ->groupBy('date')
            ->orderBy('date')
            ->limit(30) //response limit
            ->get();

        // Course distribution
        $courseDistribution = User::withCount(['posts' => function($query) {
                $query->limit(1000); // Subquery limit
            }])
            ->select([
                'course_id',
                DB::raw('count(*) as user_count')
            ])
            ->with('course:id,name')
            ->groupBy('course_id')
            ->orderByDesc('user_count')
            ->limit(10) // Only top 10
            ->get()
            ->map(function ($item) {
                return [
                    'course' => $item->course?->name ?? 'No Course',
                    'user_count' => $item->user_count,
                ];
            });

        return response()->json([
            'total_users' => $totalUsers,
            'created_at_trend' => $userGrowth,
            'course_distribution' => $courseDistribution,
        ]);
    }

    public function instituteAnalytics()
    {
        $data = [
            'institute_stats' => Institute::withCount('courses')
                ->orderByDesc('courses_count')
                ->limit(5) // Top 5 institutes
                ->get()
                ->map(function ($institute) {
                    return [
                        'institute' => $institute->name,
                        'course_count' => $institute->courses_count,
                        'image' => $institute->image_path
                    ];
                }),
            
            //Course-to-Institute Ratio
            'course_distribution' => [
                'total_institutes' => Institute::count(),
                'total_courses' => Course::count(),
                'avg_courses_per_institute' => round(Course::count() / max(1, Institute::count()), 1)
            ],
            
            //
            'active_institutes' => Institute::whereHas('courses.users')
                ->withCount(['courses', 'courses as active_courses' => function($query) {
                    $query->has('users');
                }])
                ->orderByDesc('active_courses')
                ->limit(4)
                ->get()
        ];

        return response()->json($data);
    }

    public function surveyAnalytics()
    {
        return response()->json([

            'survey_counts' => [
                'total_surveys' => Survey::count(),
                'active_surveys' => Survey::has('questions')->count(),
                'draft_surveys' => Survey::doesntHave('questions')->count(),//no questions yet
            ],
            
            'response_overview' => [
                'total_responses' => Response::count(),
                'responses_last_week' => Response::where('created_at', '>=', now()->subWeek())->count(),
                'unique_respondents' => Response::distinct('user_id')->count('user_id')
            ],
            
            'recent_activity' => Survey::withCount(['questions', 'responses'])
                ->orderByDesc('updated_at')
                ->limit(5)
                ->get()
                ->map(function ($survey) {
                    return [
                        'id' => $survey->id,
                        'title' => $survey->title,
                        'question_count' => $survey->questions_count,
                        'response_count' => $survey->responses_count,
                        'last_updated' => $survey->updated_at->diffForHumans()
                    ];
                })
        ]);
    }
}
