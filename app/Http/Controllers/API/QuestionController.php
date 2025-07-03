<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Question;
class QuestionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'survey_id' => 'required|exists:surveys,id',
            'question_text' => 'required|string',
            'question_type' => 'required|in:text,radio,checkbox',
        ]);

        return Question::create($validated); //can be hardcoded the unitled data here or at the client side idk
    }

    public function update(Request $request, $id)
    {
        $question = Question::findOrFail($id);
        $question->update($request->only(['question_text', 'question_type']));
        return response()->json(['message' => 'Question updated.']);
    }

    public function destroy($id)
    {
        Question::findOrFail($id)->delete();
        return response()->json(['message' => 'Question deleted.']);
    }
}
