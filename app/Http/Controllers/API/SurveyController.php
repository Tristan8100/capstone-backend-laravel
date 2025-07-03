<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Survey;
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
}
