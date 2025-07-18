<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Choice;

class ChoiceController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'question_id' => 'required|exists:questions,id',
            'choice_text' => 'required|string',
        ]);

        return Choice::create($validated);
    }

    public function update(Request $request, $id)
    {
        $choice = Choice::findOrFail($id);
        $choice->update($request->only('choice_text'));
        return response()->json(['message' => 'Choice updated.']);
    }

    public function destroy($id)
    {
        Choice::findOrFail($id)->delete();
        return response()->json(['message' => 'Choice deleted.']);
    }

    public function destroyByQuestion($questionId) //to prevent n+1 query on frontend stuff
{
    Choice::where('question_id', $questionId)->delete();
    return response()->json(['message' => 'All choices deleted for this question.']);
}

}
