<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\QuizResultService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final class QuizResultController extends Controller
{
    public function __construct(protected QuizResultService $quizResults) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'landmark_id' => ['required', 'string', 'max:255'],
            'landmark_name' => ['nullable', 'string', 'max:255'],
            'visitor_id' => ['required', 'string', 'max:255'],
            'visitor_name' => ['nullable', 'string', 'max:255', 'required_without:visitor_email'],
            'visitor_email' => ['nullable', 'email', 'max:255', 'required_without:visitor_name'],
            'score_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'score_points' => ['nullable', 'numeric', 'min:0'],
            'correct_answers' => ['required', 'integer', 'min:0', 'lte:total_questions'],
            'total_questions' => ['required', 'integer', 'gt:0'],
            // Legacy aliases remain accepted while mobile clients move to the result contract above.
            'score' => ['nullable', 'numeric', 'min:0'],
            'total_points' => ['nullable', 'numeric', 'gt:0'],
            'percentage' => ['nullable', 'numeric', 'between:0,100'],
        ]);

        if (! isset($validated['score_points']) && ! isset($validated['score'])) {
            throw ValidationException::withMessages(['score_points' => 'The score points field is required.']);
        }
        if (! isset($validated['score_percentage']) && ! isset($validated['percentage'])) {
            $totalPoints = (float) ($validated['total_points'] ?? 0);
            if ($totalPoints <= 0) {
                throw ValidationException::withMessages(['score_percentage' => 'The score percentage field is required.']);
            }
        }

        Log::info('Quiz completed event received.', [
            'landmark_id' => trim((string) $validated['landmark_id']),
            'visitor_id' => $validated['visitor_id'],
            'score_percentage' => $validated['score_percentage'] ?? $validated['percentage'] ?? null,
            'score_points' => $validated['score_points'] ?? $validated['score'] ?? null,
        ]);

        $result = $this->quizResults->save($validated);

        return response()->json([
            'message' => 'Quiz result recorded.',
            'data' => $result,
        ], 201);
    }
}
