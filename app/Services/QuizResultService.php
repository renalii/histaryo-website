<?php

namespace App\Services;

use Carbon\Carbon;
use Google\Cloud\Firestore\FieldValue;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final class QuizResultService
{
    public const COLLECTION = 'quiz_results';

    public function __construct(protected FirebaseService $firebase) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function save(array $data): array
    {
        $landmarkId = trim((string) $data['landmark_id']);
        $landmark = $this->firebase->getLandmarkById($landmarkId);

        if (! $landmark->exists()) {
            throw ValidationException::withMessages([
                'landmark_id' => 'The selected landmark does not exist.',
            ]);
        }

        $landmarkData = $landmark->data();
        $scorePoints = (float) ($data['score_points'] ?? $data['score']);
        $totalPoints = (float) ($data['total_points'] ?? 0);
        $scorePercentage = (float) ($data['score_percentage']
            ?? $data['percentage']
            ?? ($totalPoints > 0 ? ($scorePoints / $totalPoints) * 100 : 0));
        $correctAnswers = (int) $data['correct_answers'];
        $totalQuestions = (int) $data['total_questions'];
        $visitorName = trim((string) ($data['visitor_name'] ?? ''));
        $visitorEmail = trim((string) ($data['visitor_email'] ?? ''));

        $result = [
            'landmark_id' => $landmarkId,
            'landmark_name' => trim((string) ($landmarkData['name'] ?? '')) ?: trim((string) ($data['landmark_name'] ?? '')) ?: 'Unknown landmark',
            'visitor_id' => trim((string) $data['visitor_id']),
            'visitor_name' => $visitorName !== '' ? $visitorName : ($visitorEmail !== '' ? $visitorEmail : 'Visitor'),
            'score_percentage' => round($scorePercentage, 2),
            'score_points' => $scorePoints,
            'correct_answers' => $correctAnswers,
            'total_questions' => $totalQuestions,
            'completed_at' => FieldValue::serverTimestamp(),
        ];

        $collection = $this->firebase->firestore()->collection(self::COLLECTION);
        $document = null;

        foreach ($collection->where('landmark_id', '=', $landmarkId)->documents() as $candidate) {
            if (! $candidate->exists()) {
                continue;
            }

            $candidateData = $candidate->data();
            if (trim((string) ($candidateData['visitor_id'] ?? '')) === $result['visitor_id']) {
                $document = $collection->document($candidate->id());
                $document->set($result, ['merge' => true]);
                break;
            }
        }

        if ($document === null) {
            $document = $collection->add($result);
        }

        Log::info('Quiz result saved.', [
            'quiz_result_id' => $document->id(),
            'result' => array_merge($result, ['completed_at' => 'server_timestamp']),
        ]);

        return array_merge($result, [
            'id' => $document->id(),
            'completed_at' => now()->toIso8601String(),
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function forLandmark(string $landmarkId): array
    {
        $landmarkId = trim($landmarkId);
        if ($landmarkId === '') {
            return [];
        }

        $results = $this->visitorTriviaResults([$landmarkId => true]);

        Log::info('Dashboard quiz-result query completed.', [
            'landmark_ids' => [$landmarkId],
            'result_count' => count($results),
        ]);

        return $results;
    }

    /**
     * @param  list<string>  $landmarkIds
     * @return list<array<string, mixed>>
     */
    public function forLandmarks(array $landmarkIds): array
    {
        $landmarkSet = [];
        foreach ($landmarkIds as $landmarkId) {
            $landmarkId = trim((string) $landmarkId);
            if ($landmarkId !== '') {
                $landmarkSet[$landmarkId] = true;
            }
        }
        if ($landmarkSet === []) {
            return [];
        }

        $results = $this->visitorTriviaResults($landmarkSet);

        Log::info('Dashboard quiz-result query completed.', [
            'landmark_ids' => array_keys($landmarkSet),
            'result_count' => count($results),
        ]);

        return $results;
    }

    /**
     * @param  array<string, true>  $landmarkSet
     * @return list<array<string, mixed>>
     */
    private function visitorTriviaResults(array $landmarkSet): array
    {
        $results = [];

        foreach ($this->firebase->userCollection(FirebaseService::VISITOR_ROLE)->documents() as $visitorDocument) {
            if (! $visitorDocument->exists()) {
                continue;
            }

            $visitorData = $visitorDocument->data();
            $visitorId = trim((string) ($visitorData['uid'] ?? $visitorDocument->id()));
            $visitorName = trim((string) ($visitorData['fullName'] ?? $visitorData['name'] ?? ''));
            if ($visitorName === '') {
                $visitorName = trim((string) ($visitorData['email'] ?? $visitorId));
            }

            foreach ($visitorDocument->reference()->collection('triviaResults')->documents() as $resultDocument) {
                if (! $resultDocument->exists()) {
                    continue;
                }

                $result = $this->resultFromTriviaDocument($resultDocument, $visitorId, $visitorName, $landmarkSet);
                if ($result !== null) {
                    $results[] = $result;
                }
            }
        }

        return $results;
    }

    /**
     * @param  array<string, true>  $landmarkSet
     * @return array<string, mixed>|null
     */
    private function resultFromTriviaDocument(mixed $document, string $visitorId, string $visitorName, array $landmarkSet): ?array
    {
        $data = $document->data();
        $landmarkId = trim((string) ($data['landmarkId'] ?? $data['landmark_id'] ?? ''));
        if (! isset($landmarkSet[$landmarkId])) {
            return null;
        }

        $completedAt = $this->toCarbon($data['createdAt'] ?? $data['timestamp'] ?? null);

        return [
            'id' => $document->id(),
            'landmark_id' => $landmarkId,
            'landmark_name' => trim((string) ($data['landmarkName'] ?? $data['landmark_name'] ?? '')),
            'activity_type' => 'quiz_attempt',
            'visitor_key' => $visitorId,
            'visitor_name' => $visitorName !== '' ? $visitorName : 'Visitor',
            'quiz_score' => $data['totalScore'] ?? null,
            'quiz_total' => null,
            'correct_answers' => $data['correctCount'] ?? $data['correct'] ?? null,
            'total_questions' => $data['total'] ?? null,
            'occurred_at' => $completedAt?->toIso8601String(),
        ];
    }

    private function toCarbon(mixed $value): ?Carbon
    {
        try {
            if ($value instanceof \DateTimeInterface) {
                return Carbon::instance($value);
            }
            if (is_object($value) && method_exists($value, 'get')) {
                $value = $value->get();
            }

            return $value ? Carbon::parse($value) : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
