<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Survey;
use App\Models\AnswerChoice;
use App\Models\Answer;
use App\Models\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\Course;
use App\Models\Institute;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\DB;
class SurveyController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $surveys = Survey::with('course')->latest()->get();
        
        // Get all response statuses in one query
        $respondedSurveyIds = Response::whereIn('survey_id', $surveys->pluck('id'))
            ->where('user_id', $userId)
            ->pluck('survey_id')
            ->toArray();
        
        return $surveys->each(function ($survey) use ($respondedSurveyIds) {
            $survey->has_responded = in_array($survey->id, $respondedSurveyIds);
        });
    }

    public function indexAlumni(Request $request)
{
    $user = Auth::user(); // get the ALUMNI

    // Filters
    $status = $request->input('status', 'all'); // all | responded | not_responded
    $search = $request->input('search', null);
    $perPage = $request->input('per_page', 10);

    // Base query
    $query = Survey::with('course')
        ->where('status', 'active') // only active surveys
        ->latest();

    // Search
    if (!empty($search)) {
        $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    // Get responded survey IDs for this user
    $respondedSurveyIds = Response::where('user_id', $user->id)
        ->pluck('survey_id')
        ->toArray();

    // Status filter
    if ($status === 'responded') {
        $query->whereIn('id', $respondedSurveyIds);
    } elseif ($status === 'not_responded') {
        $query->whereNotIn('id', $respondedSurveyIds);
    }

    // Paginate results
    $surveys = $query->paginate($perPage);

    // Apply limits and append has_responded flag
    $surveys->setCollection(
        $surveys->getCollection()->filter(function ($survey) use ($respondedSurveyIds, $user) {
            $survey->has_responded = in_array($survey->id, $respondedSurveyIds);

            // Decode limits
            $limits = is_array($survey->limits) ? $survey->limits : json_decode($survey->limits, true) ?? [];
            $allowedCourses = $limits['courses'] ?? [];
            $allowedInstitutes = $limits['institutes'] ?? [];
            $allowedBatches = $limits['batches'] ?? [];

            $userCourseId = $user->course?->id;
            $userInstituteId = $user->course?->institute_id;

            // Batch filter first (overrides everything)
        if (!empty($allowedBatches) && !in_array($user->batch, $allowedBatches)) {
            return false;
        }

        // No limits at all → visible
        if (empty($allowedCourses) && empty($allowedInstitutes)) {
            return true;
        }

        // Course explicitly allowed → always include
        if (!empty($allowedCourses) && in_array($userCourseId, $allowedCourses)) {
            return true;
        }

        // Institute limit → include if user's institute is allowed
        if (!empty($allowedInstitutes) && in_array($userInstituteId, $allowedInstitutes)) {
            return true;
        }

        // Otherwise, exclude
        return false;
        })->values()
    );

    return $surveys;
}




    public function index2(Request $request)
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
        ]);

        $perPage = 10; // Items per page
        $query = Survey::with('course');

        if ($search = $validated['search'] ?? null) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', '%' . $search . '%')
                ->orWhere('description', 'LIKE', '%' . $search . '%');
            });
        }

        $surveys = $query->latest()
            ->paginate($perPage)
            ->through(function ($survey) {
                // Map limits JSON to readable names
                $limits = $survey->limits ?? [];

                $courses = collect($limits['courses'] ?? [])
                    ->map(fn($courseId) => Course::find($courseId)?->name ?? $courseId)
                    ->toArray();

                $institutes = collect($limits['institutes'] ?? [])
                    ->map(fn($instId) => \App\Models\Institute::find($instId)?->name ?? $instId)
                    ->toArray();

                $survey->limits = [
                    'courses' => $courses,
                    'institutes' => $institutes,
                    'batches' => $limits['batches'] ?? [], // Added batch filtering
                ];

                return $survey;
            });

        return response()->json([
            'data' => $surveys->items(),
            'next_page_url' => $surveys->nextPageUrl(),
            'current_page' => $surveys->currentPage(),
            'last_page' => $surveys->lastPage(),
        ]);
    }

    public function show($id)
    {
        $survey = Survey::with('questions.choices')
            ->findOrFail($id);
        
        // Check if user has responded
        $survey->has_responded = Response::where('survey_id', $id)
            ->where('user_id', Auth::id())
            ->exists();
        
        return $survey;
    }



    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'course_id' => 'nullable|string|exists:courses,id', // legacy support
            'limits' => 'nullable|array',
            'limits.courses' => 'nullable|array',
            'limits.courses.*' => 'string|exists:courses,id',
            'limits.institutes' => 'nullable|array',
            'limits.institutes.*' => 'string|exists:institutes,id',
            'limits.batches' => 'nullable|array', //also array
            'limits.batches.*' => 'integer', //added batch filtering
        ]);

        $validated['status'] = 'pending';

        $survey = Survey::create($validated);

        // limits structuring
        $survey->limits = [
            'courses' => Course::whereIn('id', $validated['limits']['courses'] ?? [])->pluck('name')->toArray(),
            'institutes' => Institute::whereIn('id', $validated['limits']['institutes'] ?? [])->pluck('name')->toArray(),
            'batches' => $validated['limits']['batches'] ?? [],
        ];

        return $survey;
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:pending,active',
            //'course_id' => 'nullable|string|exists:courses,id' // Added new
        ]);

        $survey = Survey::findOrFail($id);
        $survey->update($validated);
        
        return response()->json([
            'message' => 'Survey updated successfully',
            'survey' => $survey
        ]);
    }

    public function destroy($id)
    {
        Survey::findOrFail($id)->delete();
        return response()->json(['message' => 'Survey deleted.']);
    }

    public function storeOrUpdate(Request $request, $id = null)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'questions' => 'required|array|min:1',
            'questions.*.question_text' => 'required|string',
            'questions.*.question_type' => 'required|in:text,radio,checkbox',
            'questions.*.choices' => 'nullable|array',
            'questions.*.choices.*.choice_text' => 'required_with:questions.*.choices|string',
        ]);

        // Wrap the entire operation in a database transaction and return the survey
        $survey = DB::transaction(function () use ($validated, $id) {
            if ($id) {
                // Update existing survey
                $survey = Survey::findOrFail($id);
                $survey->update([
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                ]);

                $survey->questions()->delete();

            } else {
                // Create a new survey
                $survey = Survey::create([
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                ]);
            }

            // Create new questions and their choices
            foreach ($validated['questions'] as $q) {
                $question = $survey->questions()->create([
                    'question_text' => $q['question_text'],
                    'question_type' => $q['question_type'],
                ]);

                if (!empty($q['choices'])) {
                    foreach ($q['choices'] as $choice) {
                        $question->choices()->create([
                            'choice_text' => $choice['choice_text'],
                        ]);
                    }
                }
            }

            // return the survey
            return $survey;
        });

        // Guard not null
        if (!$survey || !isset($survey->id)) {
            return response()->json(['message' => 'Failed to create or update survey.'], 500);
        }

        $survey = Survey::with('questions.choices')->findOrFail($survey->id);

        return response()->json($survey);
    }

    public function showResults($surveyId)
    {
        $survey = Survey::with('questions.choices')->findOrFail($surveyId);

        $results = $survey->questions->map(function ($question) use ($surveyId) {
            if ($question->question_type === 'text') {
                $textResponseCount = Answer::where('question_id', $question->id)
                    ->whereNotNull('answer_text')
                    ->where('answer_text', '!=', '')
                    ->count();

                return [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'question_type' => $question->question_type,
                    'response_count' => $textResponseCount,
                ];
            }

            $choicesWithCount = $question->choices->map(function ($choice) use ($surveyId) {
                $responseCount = AnswerChoice::where('choice_id', $choice->id)
                    ->whereHas('answer.response', function ($query) use ($surveyId) {
                        $query->where('survey_id', $surveyId);
                    })
                    ->count();

                return [
                    'id' => $choice->id,
                    'text' => $choice->choice_text,
                    'response_count' => $responseCount,
                ];
            });

            return [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'question_type' => $question->question_type,
                'choices' => $choicesWithCount,
            ];
        });

        return response()->json([
            'survey_id' => $survey->id,
            'title' => $survey->title,
            'description' => $survey->description ?? '',
            'results' => $results,
        ]);
    }

    public function getTextResponses(Request $request, $questionId)
    {
        $limit = $request->input('limit', 20);
        $responses = Answer::where('question_id', $questionId)
                        ->whereNotNull('answer_text')
                        ->paginate($limit);

        return response()->json([
            'question_id' => $questionId,
            'responses' => $responses->items(),
            'totalPages' => $responses->lastPage(),
        ]);
    }

    public function checkResponse($surveyId)
    {
        $value = Survey::findOrFail($surveyId);
        if (!$value) { // Another checking if ever
            return response()->json(['message' => 'Survey not found.'], 404);
        }

        $hasResponded = Response::where('survey_id', $surveyId)->exists();

        return response()->json([
            'message' => 'Survey found.',
            'has_responded' => $hasResponded
        ]);
    }





}
