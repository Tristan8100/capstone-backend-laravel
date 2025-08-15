<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Survey;
use App\Models\AnswerChoice;
use App\Models\Answer;
use App\Models\Response;
use Illuminate\Support\Facades\Auth;

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
        $userId = Auth::id();

        // Filters
        $status = $request->input('status', 'all'); // all | responded | not_responded
        $search = $request->input('search', null);
        $perPage = $request->input('per_page', 10);

        // Base query
        $query = Survey::with('course')->latest();

        // Search
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Get responded survey IDs for the user
        $respondedSurveyIds = Response::where('user_id', $userId)
            ->pluck('survey_id')
            ->toArray();

        // Status filtering
        if ($status === 'responded') {
            $query->whereIn('id', $respondedSurveyIds);
        } elseif ($status === 'not_responded') {
            $query->whereNotIn('id', $respondedSurveyIds);
        }

        // Paginate results
        $surveys = $query->paginate($perPage);

        // Append has_responded flag to each item
        $surveys->getCollection()->transform(function ($survey) use ($respondedSurveyIds) {
            $survey->has_responded = in_array($survey->id, $respondedSurveyIds);
            return $survey;
        });

        return $surveys;
    }


    public function index2(Request $request)
    {

        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
        ]);

        $userId = Auth::id();
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
            ->through(function ($survey) use ($userId) {
                $survey->has_responded = Response::where('survey_id', $survey->id)
                    ->where('user_id', $userId)
                    ->exists();
                return $survey;
            });

        return response()->json([
            'data' => $surveys->items(),
            'next_page_url' => $surveys->nextPageUrl(),
            'current_page' => $surveys->currentPage(),
            'last_page' => $surveys->lastPage()
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
            'course_id' => 'nullable|string|exists:courses,id'// Added new
        ]);

        return Survey::create($validated);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string',
            'description' => 'nullable|string',
            'course_id' => 'nullable|string|exists:courses,id' // Added new
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

        // Wrap the entire operation in a database transaction
        DB::transaction(function () use ($validated, $id, &$survey) {
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
        });

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
