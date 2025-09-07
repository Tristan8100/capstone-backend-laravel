<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Career;
use App\Models\Course;
use App\Models\Institute;
use App\Models\User;
use Carbon\Carbon;

class JobFitAnalysisController extends Controller
{
    public function overall()
    {
        $careers = Career::all();

        $stats = [
            'total' => $careers->count(),
            'related' => $careers->where('fit_category', 'Related')->count(),
            'not_related' => $careers->where('fit_category', 'Not Related')->count(),
            'recommended_jobs' => $careers->pluck('recommended_jobs')->flatten()->unique()->values(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats,
        ]);
    }

    public function perCourse($courseId)
    {
        $careers = Career::whereHas('user', fn($q) => $q->where('course_id', $courseId))->get();

        $stats = [
            'course' => Course::find($courseId)?->full_name ?? 'N/A',
            'total' => $careers->count(),
            'related' => $careers->where('fit_category', 'Related')->count(),
            'not_related' => $careers->where('fit_category', 'Not Related')->count(),
            'recommended_jobs' => $careers->pluck('recommended_jobs')->flatten()->unique()->values(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats,
        ]);
    }

    public function perInstitute($instituteId)
    {
        $careers = Career::whereHas('user.course', fn($q) => $q->where('institute_id', $instituteId))->get();

        $stats = [
            'institute' => Institute::find($instituteId)?->name ?? 'N/A',
            'total' => $careers->count(),
            'related' => $careers->where('fit_category', 'Related')->count(),
            'not_related' => $careers->where('fit_category', 'Not Related')->count(),
            'recommended_jobs' => $careers->pluck('recommended_jobs')->flatten()->unique()->values(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats,
        ]);
    }

    public function detailed(Request $request)
    {
        $query = Career::query();

        if ($request->has('course_id')) {
            $query->whereHas('user', fn($q) => $q->where('course_id', $request->course_id));
        }

        if ($request->has('institute_id')) {
            $query->whereHas('user.course', fn($q) => $q->where('institute_id', $request->institute_id));
        }

        $careers = $query->with(['user.course.institute'])->get();

        return response()->json([
            'status' => 'success',
            'data' => $careers,
        ]);
    }

    public function index(Request $request)
    {
        $courseId = $request->query('course_id');
        $instituteId = $request->query('institute_id');

        // Base query
        $query = Career::with(['user.course.institute']);

        // Apply filters
        if ($courseId) {
            $query->whereHas('user.course', fn($q) => $q->where('id', $courseId));
        } elseif ($instituteId) {
            $query->whereHas('user.course', fn($q) => $q->where('institute_id', $instituteId));
        }

        $careers = $query->get();

        $total = $careers->count();
        $relatedCount = $careers->where('fit_category', 'Related')->count();
        $notRelatedCount = $careers->where('fit_category', 'Not Related')->count();

        // 1. Fit distribution
        $fit_distribution = [
            'related' => $total ? round(($relatedCount / $total) * 100, 2) : 0,
            'not_related' => $total ? round(($notRelatedCount / $total) * 100, 2) : 0,
        ];

        // 2. Average recommended jobs
        $avgRecommendedJobs = $careers->pluck('recommended_jobs')
            ->map(fn($jobs) => is_array($jobs) ? count($jobs) : 0)
            ->avg();

        // 3. Effectiveness score (weighted)
        $effectiveness_score = $fit_distribution['related'] * 0.7 + ($avgRecommendedJobs / 10) * 0.3;

        // 4. Skills gap analysis
        $notRelatedSkills = $careers->where('fit_category', 'Not Related')
            ->pluck('skills_used')
            ->flatten()
            ->countBy()
            ->sortDesc()
            ->toArray();

        // 5. Top recommended roles
        $recommendedRoles = $careers->where('fit_category', 'Related')
            ->pluck('recommended_jobs')
            ->flatten()
            ->countBy()
            ->sortDesc()
            ->take(10)
            ->toArray();

        // 6. Average time to first job per batch
        $avgTimeToFirstJob = User::whereHas('careers')
            ->with(['careers' => fn($q) => $q->orderBy('start_date')])
            ->get()
            ->map(function ($user) {
                if (!$user->batch || $user->careers->isEmpty()) return null;

                $firstCareer = $user->careers->first();

                if (!$firstCareer->start_date) return null;

                // Assume graduation at June 1 of the batch year
                $gradDate = Carbon::create($user->batch, 6, 1);
                $firstJobDate = Carbon::parse($firstCareer->start_date);

                $months = max(0, $firstJobDate->diffInMonths($gradDate, false));

                return [
                    'batch' => $user->batch,
                    'months' => $months,
                ];
            })
            ->filter() // remove nulls
            ->groupBy('batch')
            ->map(function ($items) {
                return round(collect($items)->avg('months'), 1);
            })
            ->toArray();

        $avgTimeToFirstJob2 = User::whereHas('careers')
        ->with(['careers' => fn($q) => $q->orderBy('start_date')])
        ->get()
        ->map(function ($user) {
            if (!$user->batch || $user->careers->isEmpty()) return null;

            $firstCareer = $user->careers->first();
            if (!$firstCareer->start_date) return null;

            $batchYear = (int)$user->batch;
            $jobYear = (int)Carbon::parse($firstCareer->start_date)->format('Y');

            $yearsToJob = max(0, $jobYear - $batchYear);

            return [
                'batch' => $batchYear,
                'years' => $yearsToJob,
            ];
        })
        ->filter()
        ->groupBy('batch')
        ->map(fn($items) => round(collect($items)->avg('years'), 1))
        ->toArray();


        // 7. Per course breakdown
        $perCourse = $careers->groupBy(fn($career) => $career->user?->course_id)->map(function ($group) {
            $total = $group->count();
            $relatedCount = $group->where('fit_category', 'Related')->count();
            $avgRecommendedJobs = $group->pluck('recommended_jobs')
                ->map(fn($jobs) => is_array($jobs) ? count($jobs) : 0)
                ->avg();

            return [
                'course_name' => $group->first()->user->course?->name ?? 'N/A',
                'fit_distribution' => [
                    'related' => $total ? round(($relatedCount / $total) * 100, 2) : 0,
                    'not_related' => $total ? round(($total - $relatedCount) / $total * 100, 2) : 0,
                ],
                'effectiveness_score' => $total ? round($relatedCount / $total * 70 + ($avgRecommendedJobs / 10) * 30, 2) : 0,
            ];
        });

        // 8. Per institute breakdown
        $perInstitute = $careers->groupBy(fn($career) => $career->user?->course?->institute_id)->map(function ($group) {
            $total = $group->count();
            $relatedCount = $group->where('fit_category', 'Related')->count();
            $avgRecommendedJobs = $group->pluck('recommended_jobs')
                ->map(fn($jobs) => is_array($jobs) ? count($jobs) : 0)
                ->avg();

            return [
                'institute_name' => $group->first()->user->course->institute?->name ?? 'N/A',
                'fit_distribution' => [
                    'related' => $total ? round(($relatedCount / $total) * 100, 2) : 0,
                    'not_related' => $total ? round(($total - $relatedCount) / $total * 100, 2) : 0,
                ],
                'effectiveness_score' => $total ? round($relatedCount / $total * 70 + ($avgRecommendedJobs / 10) * 30, 2) : 0,
            ];
        });

        return response()->json([
            'total_careers' => $total,
            'overall' => [
                'fit_distribution' => $fit_distribution,
                'effectiveness_score' => round($effectiveness_score, 2),
                'skills_gap' => $notRelatedSkills,
                'top_recommended_roles' => $recommendedRoles,
                'avg_time_to_first_job_months' => $avgTimeToFirstJob,
                'avg_time_to_first_job_years' => $avgTimeToFirstJob2,
            ],
            'per_course' => $perCourse,
            'per_institute' => $perInstitute,
        ]);
    }

}
