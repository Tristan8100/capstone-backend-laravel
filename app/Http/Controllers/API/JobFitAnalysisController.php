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
    $year = $request->query('year');
    $referenceMonthDay = $request->query('reference_date', '06-01');

    // Helper: flatten, trim, lowercase, count
    $normalizeAndCount = function ($items) {
        return collect($items)
            ->flatMap(fn($value) => is_string($value) ? array_map('trim', explode(',', $value)) : (array)$value)
            ->map(fn($item) => strtolower($item))
            ->filter(fn($item) => !empty($item))
            ->countBy()
            ->sortDesc()
            ->toArray();
    };

    // Base query
    $query = Career::query()->with(['user.course.institute']);
    if ($courseId) $query->whereHas('user.course', fn($q) => $q->where('id', $courseId));
    elseif ($instituteId) $query->whereHas('user.course', fn($q) => $q->where('institute_id', $instituteId));
    if ($year) $query->whereHas('user', fn($q) => $q->where('batch', $year));

    // Counts
    $total = (clone $query)->count();
    $relatedCount = (clone $query)->where('fit_category', 'Related')->count();
    $notRelatedCount = $total - $relatedCount;

    $fit_distribution = [
        'related' => $total ? round(($relatedCount / $total) * 100, 2) : 0,
        'not_related' => $total ? round(($notRelatedCount / $total) * 100, 2) : 0,
    ];

    $avgRecommendedJobs = (clone $query)
        ->selectRaw("AVG(JSON_LENGTH(recommended_jobs)) as avg_jobs")
        ->value('avg_jobs');

    $effectiveness_score = $fit_distribution['related'] * 0.7 + ($avgRecommendedJobs / 10) * 0.3;

    // Skills, roles, titles, companies
    $notRelatedSkills = array_slice($normalizeAndCount((clone $query)->where('fit_category', 'Not Related')->pluck('skills_used')), 0, 20);
    $relatedSkills = array_slice($normalizeAndCount((clone $query)->where('fit_category', 'Related')->pluck('skills_used')), 0, 20);
    $recommendedRoles = array_slice($normalizeAndCount((clone $query)->where('fit_category', 'Related')->pluck('recommended_jobs')), 0, 10);
    $topTitles = array_slice($normalizeAndCount((clone $query)->pluck('title')), 0, 10);
    $topCompanies = array_slice($normalizeAndCount((clone $query)->pluck('company')), 0, 10);

    // Time to first job
    $avgTimeToFirstJob = (clone $query)
        ->join('users as u', 'careers.user_id', '=', 'u.id')
        ->selectRaw('u.batch, 
            ROUND(AVG(TIMESTAMPDIFF(
                MONTH, 
                STR_TO_DATE(CONCAT(u.batch, "-", ?), "%Y-%m-%d"), 
                careers.start_date
            )), 1) as avg_months', [$referenceMonthDay])
        ->when($year, fn($q) => $q->where('u.batch', $year))
        ->whereNotNull('u.batch')
        ->whereNotNull('careers.start_date')
        ->groupBy('u.batch')
        ->pluck('avg_months', 'u.batch')
        ->toArray();

    $avgTimeToFirstJob2 = collect($avgTimeToFirstJob)->map(fn($months) => round($months / 12, 1))->toArray();

    // ======================
    // SIZE COMMENT HELPER
    // ======================
    $sizeComment = function($count) {
        if ($count < 10) return "Data is limited; interpret insights cautiously. ";
        if ($count < 50) return "Dataset is small; interpret insights cautiously. ";
        if ($count < 200) return "Dataset is moderate; insights reasonably reliable. ";
        return "Dataset is large; insights are strong. ";
    };

    // ======================
    // OVERALL ANALYSIS
    // ======================
    $analysis_fit = $sizeComment($total); // start with size warning
    if ($fit_distribution['related'] >= 60) {
        $analysis_fit .= "Most graduates ({$fit_distribution['related']}%) are in relevant fields; the program is performing well and can be retained with minor improvements.";
    } elseif ($fit_distribution['related'] >= 40) {
        $analysis_fit .= "Graduates ({$fit_distribution['related']}%) are moderately aligned; consider reviewing program content or enhancing skill alignment.";
    } else {
        $analysis_fit .= "Many graduates ({$fit_distribution['related']}%) are in unrelated fields; review the program and consider restructuring to improve relevance.";
    }

    $analysis_skills = count($notRelatedSkills)
        ? $sizeComment($total) . "The most common skills gaps are: " . implode(', ', array_keys($notRelatedSkills)) . "."
        : $sizeComment($total) . "Not enough skills data to generate a detailed analysis.";

    $analysis_related_skills = count($relatedSkills)
        ? $sizeComment($total) . "Top utilized skills among graduates are: " . implode(', ', array_keys($relatedSkills)) . "."
        : $sizeComment($total) . "Not enough skills data to generate a detailed analysis.";

    if (count($recommendedRoles)) {
        $topRoles = array_keys($recommendedRoles);
        $last = array_pop($topRoles);
        $roleList = count($topRoles) ? implode(', ', $topRoles) . ' and ' . $last : $last;
        $analysis_recommended_roles = $sizeComment($total) . "The top recommended roles for graduates include: {$roleList}.";
    } else {
        $analysis_recommended_roles = $sizeComment($total) . "No recommended roles data available.";
    }

    if(count($topTitles)) {
        $topTitlesList = implode(', ', array_keys($topTitles));
        $analysis_top_titles = $sizeComment($total) . "Most graduates work as: {$topTitlesList}.";
    } else {
        $analysis_top_titles = $sizeComment($total) . "No title data available.";
    }

    if(count($topCompanies)) {
        $topCompaniesList = implode(', ', array_keys($topCompanies));
        $analysis_top_companies = $sizeComment($total) . "Most graduates are employed at: {$topCompaniesList}.";
    } else {
        $analysis_top_companies = $sizeComment($total) . "No company data available.";
    }

    $analysis_time_to_job = [];
    foreach ($avgTimeToFirstJob2 as $batch => $years) {
        if ($years < 1) $analysis_time_to_job[$batch] = $sizeComment($total) . "Graduates of batch {$batch} quickly secured their first job (<1 year).";
        elseif ($years <= 2) $analysis_time_to_job[$batch] = $sizeComment($total) . "Graduates of batch {$batch} took moderate time (~{$years} years) to get their first job.";
        else $analysis_time_to_job[$batch] = $sizeComment($total) . "Graduates of batch {$batch} took longer (>2 years) to secure their first job.";
    }

    // ======================
    // PER COURSE ANALYSIS - FIXED
    // ======================
    // Fetch all courses if institute filter exists
    $courses = $instituteId 
        ? Course::where('institute_id', $instituteId)->get() 
        : Course::all();

    $perCourse = [];
    foreach ($courses as $course) {
        $group = (clone $query)->whereHas('user.course', fn($q) => $q->where('id', $course->id))->get();
        $totalCourse = $group->count();
        $relatedCount = $group->where('fit_category', 'Related')->count();
        $avgRecommendedJobs = $group->pluck('recommended_jobs')->map(fn($jobs) => is_array($jobs) ? count($jobs) : 0)->avg();
        $fitPercent = $totalCourse ? round(($relatedCount / $totalCourse) * 100, 2) : 0;

        $insight = $fitPercent >= 70 ? "Most graduates are well-aligned."
            : ($fitPercent >= 40 ? "Moderate alignment." : "Low alignment.");
        $insight = $sizeComment($totalCourse) . $insight;

        $perCourse[$course->id] = [
            'course_name' => $course->name,
            'fit_distribution' => [
                'related' => $fitPercent,
                'not_related' => $totalCourse ? round(($totalCourse - $relatedCount) / $totalCourse * 100, 2) : 0,
            ],
            'effectiveness_score' => $totalCourse ? round($relatedCount / $totalCourse * 70 + ($avgRecommendedJobs / 10) * 30, 2) : 0,
            'insight_sentence' => "The {$course->name} course has {$fitPercent}% of graduates in relevant fields.",
            'analysis_fit' => $insight
        ];
    }

    // ======================
    // PER INSTITUTE ANALYSIS
    // ======================
    $institutes = $instituteId ? Institute::where('id', $instituteId)->get() : Institute::all();
    $perInstitute = [];
    foreach ($institutes as $inst) {
        $group = (clone $query)->whereHas('user.course', fn($q) => $q->where('institute_id', $inst->id))->get();
        $totalInst = $group->count();
        $relatedCount = $group->where('fit_category', 'Related')->count();
        $avgRecommendedJobs = $group->pluck('recommended_jobs')->map(fn($jobs) => is_array($jobs) ? count($jobs) : 0)->avg();
        $fitPercent = $totalInst ? round(($relatedCount / $totalInst) * 100, 2) : 0;

        $insight = $fitPercent >= 70 ? "Most graduates are well-aligned."
            : ($fitPercent >= 40 ? "Moderate alignment." : "Low alignment.");
        $insight = $sizeComment($totalInst) . $insight;

        $perInstitute[$inst->id] = [
            'institute_name' => $inst->name,
            'fit_distribution' => [
                'related' => $fitPercent,
                'not_related' => $totalInst ? round(($totalInst - $relatedCount) / $totalInst * 100, 2) : 0,
            ],
            'effectiveness_score' => $totalInst ? round($relatedCount / $totalInst * 70 + ($avgRecommendedJobs / 10) * 30, 2) : 0,
            'insight_sentence' => "The {$inst->name} institute has {$fitPercent}% of graduates in relevant fields.",
            'analysis_fit' => $insight
        ];
    }

    return response()->json([
        'total_careers' => $total,
        'overall' => [
            'fit_distribution' => $fit_distribution,
            'effectiveness_score' => round($effectiveness_score, 2),
            'skills_gap' => $notRelatedSkills,
            'related_skills' => $relatedSkills,
            'top_recommended_roles' => $recommendedRoles,
            'top_titles' => $topTitles,
            'top_companies' => $topCompanies,
            'avg_time_to_first_job_months' => $avgTimeToFirstJob,
            'avg_time_to_first_job_years' => $avgTimeToFirstJob2,
            'analysis_fit_distribution' => $analysis_fit,
            'analysis_skills_gap' => $analysis_skills,
            'analysis_related_skills' => $analysis_related_skills,
            'analysis_top_recommended_roles' => $analysis_recommended_roles,
            'analysis_top_titles' => $analysis_top_titles,
            'analysis_top_companies' => $analysis_top_companies,
            'analysis_time_to_first_job' => $analysis_time_to_job,
        ],
        'per_course' => $perCourse,
        'per_institute' => $perInstitute,
    ]);
}





}
