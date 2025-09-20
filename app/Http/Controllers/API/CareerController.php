<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Career;
use Illuminate\Support\Facades\Auth;
use Prism\Prism\Prism;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Illuminate\Support\Facades\Log;

class CareerController extends Controller
{

    public function create(Request $request)
    {
        // Validate user input (exclude fit_category since AI will fill it)
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'company'     => 'required|string|max:255',
            'description' => 'nullable|string',
            'skills_used' => 'nullable|array',
            'start_date'  => 'required|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
        ]);

        $validated['user_id'] = Auth::id();
        $user = Auth::user();

        // Safe course info
        $courseName = $user->course?->name ?? 'N/A';
        $courseFull = $user->course?->full_name ?? 'N/A';

        // Build AI schema
        $schema = new ObjectSchema(
            name: 'career_analysis',
            description: 'Determine career fit based on user course, title, description, and skills',
            properties: [
                new StringSchema('fit_category', 'Either Related or Not Related'),
                new StringSchema('recommended_jobs', 'JSON array of suggested job titles or roles'),
                new StringSchema('analysis_notes', 'Short explanation of reasoning for the fit_category'),
            ],
            requiredFields: ['fit_category', 'recommended_jobs', 'analysis_notes']
        );

        // Build prompt for AI
        $prompt = "User course: {$courseName} ({$courseFull})\n";
        $prompt .= "Job title: {$validated['title']}\n";
        $prompt .= "Company: {$validated['company']}\n";
        $prompt .= "Description: " . ($validated['description'] ?? 'N/A') . "\n";
        $prompt .= "Skills used: " . (!empty($validated['skills_used']) ? implode(', ', $validated['skills_used']) : 'N/A') . "\n";
        $prompt .= "Based on this, determine the fit_category (Related/Not Related), recommend other jobs, and provide a short explanation.";

        // Call AI using Prism inside try/catch
        try {
            $response = Prism::structured()
                ->using(Provider::Gemini, 'gemini-2.0-flash')
                ->withSchema($schema)
                ->withPrompt($prompt)
                ->asStructured();

            // Fill AI-generated fields
            $validated['fit_category']     = $response->structured['fit_category'] ?? null;
            $validated['recommended_jobs'] = $response->structured['recommended_jobs'] ?? null;
            $validated['analysis_notes']   = $response->structured['analysis_notes'] ?? null;

        } catch (\Exception $e) {
            Log::error('Prism AI error on career create: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'title' => $validated['title'],
                'company' => $validated['company'],
            ]);

            // Optional: fallback values if AI fails
            $validated['fit_category']     = null;
            $validated['recommended_jobs'] = null;
            $validated['analysis_notes']   = null;
        }

        // Create career record
        $career = Career::create($validated);

        return response()->json([
            'status' => 'success',
            'data'   => $career
        ], 201);
    }


    public function update(Request $request, $id) 
    {
        // Find the career record for the authenticated user
        $career = Career::where('id', $id)
                        ->where('user_id', Auth::id())
                        ->firstOrFail();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'description' => 'nullable|string',
            'skills_used' => 'nullable|array',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $user = Auth::user();

        // Safe course info
        $courseName = $user->course?->name ?? 'N/A';
        $courseFull = $user->course?->full_name ?? 'N/A';

        // Build AI schema
        $schema = new ObjectSchema(
            name: 'career_analysis',
            description: 'Determine career fit based on user course, title, description, and skills',
            properties: [
                new StringSchema('fit_category', 'Either Related or Not Related'),
                new StringSchema('recommended_jobs', 'JSON array of suggested job titles or roles'),
                new StringSchema('analysis_notes', 'Short explanation of reasoning for the fit_category'),
            ],
            requiredFields: ['fit_category', 'recommended_jobs', 'analysis_notes']
        );

        // Build prompt for AI
        $prompt = "User course: {$courseName} ({$courseFull})\n";
        $prompt .= "Job title: {$validated['title']}\n";
        $prompt .= "Company: {$validated['company']}\n";
        $prompt .= "Description: " . ($validated['description'] ?? 'N/A') . "\n";
        $prompt .= "Skills used: " . (!empty($validated['skills_used']) ? implode(', ', $validated['skills_used']) : 'N/A') . "\n";
        $prompt .= "Based on this, determine the fit_category (Related/Not Related), recommend other jobs, and provide a short explanation.";

        // Call AI using Prism inside try/catch
        try {
            $response = Prism::structured()
                ->using(Provider::Gemini, 'gemini-2.0-flash')
                ->withSchema($schema)
                ->withPrompt($prompt)
                ->asStructured();

            // Fill AI-generated fields
            $validated['fit_category']     = trim($response->structured['fit_category'] ?? '');
            $validated['recommended_jobs'] = $response->structured['recommended_jobs'] ?? [];
            if (is_string($validated['recommended_jobs'])) {
                $validated['recommended_jobs'] = explode(',', $validated['recommended_jobs']);
            }
            $validated['recommended_jobs'] = array_map('trim', $validated['recommended_jobs']);
            $validated['analysis_notes']   = trim($response->structured['analysis_notes'] ?? '');
        } catch (\Exception $e) {
            Log::error('Prism AI error on career update: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'title' => $validated['title'],
                'company' => $validated['company'],
            ]);

            // Optional: fallback values if AI fails
            $validated['fit_category']     = $career->fit_category; // keep old value
            $validated['recommended_jobs'] = $career->recommended_jobs;
            $validated['analysis_notes']   = $career->analysis_notes;
        }

        // Update career record
        $career->update($validated);

        return response()->json([
            'status' => 'success',
            'data' => $career
        ], 200);
    }


    public function delete($id)
    {
        $career = Career::where('id', $id)
                        ->where('user_id', Auth::id())
                        ->firstOrFail();

        // Delete
        $career->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Career record deleted successfully.'
        ], 200);
    }

    // Get (Authenticated user's) career records
    public function index()
    {
        $careers = Career::where('user_id', Auth::id())
                         ->orderBy('start_date', 'desc')
                         ->get();

        return response()->json([
            'status' => 'success',
            'data' => $careers
        ], 200);
    }

    public function indexPaginated()
    {
        $careers = Career::where('user_id', Auth::id())
                        ->orderBy('start_date', 'desc')
                        ->paginate(5); // Add pagination with 10 items per page

        return response()->json([
            'status' => 'success',
            'data' => $careers->items(), // Get just the data items
            'pagination' => [
                'current_page' => $careers->currentPage(),
                'last_page' => $careers->lastPage(),
                'per_page' => $careers->perPage(),
                'total' => $careers->total(),
                'has_more' => $careers->hasMorePages()
            ]
        ], 200);
    }

    public function indexPaginatedbyId($id)
    {
        $careers = Career::where('user_id', $id)
                        ->orderBy('start_date', 'desc')
                        ->paginate(5); // Add pagination with 10 items per page

        return response()->json([
            'status' => 'success',
            'data' => $careers->items(), // Get just the data items
            'pagination' => [
                'current_page' => $careers->currentPage(),
                'last_page' => $careers->lastPage(),
                'per_page' => $careers->perPage(),
                'total' => $careers->total(),
                'has_more' => $careers->hasMorePages(),
                'count' => $careers->count()
            ]
        ], 200);
    }

    // Show a single career record of the authenticated user
    public function show($career_id)
    {
        $career = Career::where('id', $career_id)
                        ->where('user_id', Auth::id())
                        ->firstOrFail();

        return response()->json([
            'status' => 'success',
            'data' => $career
        ], 200);
    }

}
