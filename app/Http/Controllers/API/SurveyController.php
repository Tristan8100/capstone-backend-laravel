<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Survey;
use App\Models\AnswerChoice;
use Illuminate\Support\Facades\DB;
class SurveyController extends Controller
{
    public function index()
    {
        return Survey::latest()->get();
    }

    public function show($id)
    {
        return Survey::with('questions.choices')->findOrFail($id);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
        ]);

        return Survey::create($validated);
    }

    public function update(Request $request, $id)
    {
        $survey = Survey::findOrFail($id);
        $survey->update($request->only(['title', 'description']));
        return response()->json(['message' => 'Survey updated.']);
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

                // Delete old questions and their choices
                // If your migrations have onDelete('cascade') on question_id in choices table,
                // and survey_id in questions table, then deleting questions will cascade to choices.
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

        // Load nested relations for complete response after the transaction commits
        // The $survey variable needs to be passed by reference to be accessible outside the closure
        // or re-fetched after the transaction. Re-fetching is safer if the transaction
        // might modify the $survey object in ways not immediately reflected.
        $survey = Survey::with('questions.choices')->findOrFail($survey->id);

        return response()->json($survey);
    }

    public function showResults($id)
    {
        $survey = Survey::with(['questions.choices'])->findOrFail($id);

        $results = $survey->questions->map(function ($question) {
            $data = [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'question_type' => $question->question_type,
            ];

            if ($question->question_type === 'text') {
                $data['responses'] = 'text';
            } else {
                $data['choices'] = $question->choices->map(function ($choice) {
                    $responseCount = AnswerChoice::where('choice_id', $choice->id)->count();

                    return [
                        'id' => $choice->id,
                        'text' => $choice->choice_text,
                        'response_count' => $responseCount,
                    ];
                });
            }

            return $data;
        });

        return response()->json([
            'survey_id' => $survey->id,
            'title' => $survey->title,
            'results' => $results,
        ]);
    }

}
