<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Response;
use App\Models\Answer;
use App\Models\AnswerChoice;
use App\Models\Survey;

class ResponseController extends Controller
{
    //List all responses of the current user
    public function index()
    {
        return Response::with('answers.answerChoices')->where('user_id', Auth::id())->get();
    }

    // View a specific response
    public function show($id)
    {
        return Response::with('answers.answerChoices')
            ->where('user_id', Auth::id())
            ->findOrFail($id);
    }

    public function showBasedSurvey($id)
    {
        return Response::with('answers.answerChoices')
            ->where('user_id', Auth::id())
            ->where('survey_id', $id)
            ->get();
    }

    // Submit a survey response or Update (all in, will iterate all)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'survey_id' => 'required|exists:surveys,id',
            'answers' => 'required|array|min:1',
            'answers.*.question_id' => 'required|exists:questions,id',
            'answers.*.answer_text' => 'nullable|string',
            'answers.*.choice_ids' => 'nullable|array',
            'answers.*.choice_ids.*' => 'exists:choices,id',
        ]);

        $survey = Survey::findOrFail($validated['survey_id']);

        //check
        if ($survey->course_id && $survey->course_id !== Auth::user()->course_id) {
            return response()->json([
                'message' => 'This survey is restricted to '.$survey->course->name.' students'
            ], 403);
        }

        // Update or create the response (one per user per survey)
        $response = Response::updateOrCreate(
            [
                'survey_id' => $validated['survey_id'],
                'user_id' => Auth::id(),
            ],
            []
        );

        // Delete old answers & choices (for clean replace)
        $response->answers()->each(function ($answer) {
            $answer->answerChoices()->delete();
            $answer->delete();
        });

        // Insert new answers and choices
        foreach ($validated['answers'] as $data) {
            $answer = Answer::create([
                'response_id' => $response->id,
                'question_id' => $data['question_id'],
                'answer_text' => $data['answer_text'] ?? null,
            ]);

            if (!empty($data['choice_ids'])) {
                foreach ($data['choice_ids'] as $choiceId) {
                    AnswerChoice::create([
                        'answer_id' => $answer->id,
                        'choice_id' => $choiceId,
                    ]);
                }
            }
        }

        return response()->json(['message' => 'Survey submitted successfully.']);
    }

    // allow delete (for admin or user re-submit logic)
    public function destroy($id)
    {
        $response = Response::where('user_id', Auth::id())->findOrFail($id);
        $response->delete();

        return response()->json(['message' => 'Response deleted.']);
    }
}
