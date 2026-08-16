<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final class QuizResultService
{
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
            'completed_at' => now()->toIso8601String(),
        ];

        Log::info('Quiz result persistence skipped; Firestore quiz results collection is disabled.', [
            'landmark_id' => $landmarkId,
            'visitor_id' => $result['visitor_id'],
        ]);

        return array_merge($result, [
            'id' => '',
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function forLandmark(string $landmarkId): array
    {
        $landmarkId = trim($landmarkId);
        if ($landmarkId === '') {
            return [];
        }

        return $this->resultsForLandmarkSet([$landmarkId => true]);
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

        return $this->resultsForLandmarkSet($landmarkSet);
    }

    /**
     * @param  array<string, true>  $landmarkSet
     * @return list<array<string, mixed>>
     */
    private function resultsForLandmarkSet(array $landmarkSet): array
    {
        $cacheKey = 'quiz-results:visitor-trivia:v3:'.md5(implode('|', array_keys($landmarkSet)));

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($landmarkSet): array {
            $start = microtime(true);
            $results = $this->deduplicateResults($this->resultsFromVisitorProfiles($landmarkSet));

            Log::info('Timing Firestore query', [
                'query' => 'visitor_profiles.trivia_results_by_landmark',
                'landmark_count' => count($landmarkSet),
                'records' => count($results),
                'duration_ms' => (int) round((microtime(true) - $start) * 1000),
            ]);

            return $results;
        });
    }

    /**
     * Remove duplicate copies of the same completed quiz result while keeping
     * separate attempts by the same visitor.
     *
     * @param  list<array<string, mixed>>  $results
     * @return list<array<string, mixed>>
     */
    private function deduplicateResults(array $results): array
    {
        $unique = [];
        $seen = [];

        foreach ($results as $result) {
            $identity = implode('|', [
                strtolower(trim((string) ($result['visitor_key'] ?? $result['visitor_name'] ?? ''))),
                trim((string) ($result['landmark_id'] ?? '')),
                trim((string) ($result['quiz_score'] ?? '')),
                trim((string) ($result['quiz_total'] ?? '')),
                trim((string) ($result['score_percentage'] ?? '')),
                trim((string) ($result['occurred_at'] ?? '')),
            ]);

            if (isset($seen[$identity])) {
                continue;
            }

            $seen[$identity] = true;
            $unique[] = $result;
        }

        return $unique;
    }

    /**
     * @param  array<string, true>  $landmarkSet
     * @return list<array<string, mixed>>
     */
    private function resultsFromCollectionGroup(array $landmarkSet): array
    {
        try {
            $firestore = $this->firebase->firestore();
            if (! method_exists($firestore, 'collectionGroup')) {
                return [];
            }

            $results = [];
            $seen = [];
            $options = $this->firestoreDashboardOptions();
            foreach (['landmarkId', 'landmark_id'] as $field) {
                foreach (array_chunk(array_keys($landmarkSet), 30) as $chunk) {
                    $query = count($chunk) === 1
                        ? $firestore->collectionGroup('triviaResults')->where($field, '==', $chunk[0])
                        : $firestore->collectionGroup('triviaResults')->where($field, 'in', $chunk);

                    try {
                        foreach ($query->documents($options) as $document) {
                            if (! $document->exists()) {
                                continue;
                            }
                            $key = $document->id().':'.md5(json_encode($document->data()));
                            if (isset($seen[$key])) {
                                continue;
                            }
                            $seen[$key] = true;

                            $result = $this->resultFromTriviaData($document->id(), $document->data(), $landmarkSet);
                            if ($result !== null) {
                                $results[] = $result;
                            }
                        }
                    } catch (\Throwable $exception) {
                        Log::warning('Unable to load trivia results using collection group query.', [
                            'field' => $field,
                            'landmark_count' => count($chunk),
                            'exception' => $exception->getMessage(),
                        ]);
                    }
                }
            }

            return $results;
        } catch (\Throwable $exception) {
            Log::warning('Unable to load trivia results using collection group query.', [
                'exception' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    /** @return array<string, int|float> */
    private function firestoreDashboardOptions(): array
    {
        return [
            'maxRetries' => 0,
            'requestTimeout' => (float) config('services.firebase.dashboard_query_timeout', 3),
        ];
    }

    /**
     * @param  array<string, true>  $landmarkSet
     * @return list<array<string, mixed>>
     */
    private function resultsFromVisitorProfiles(array $landmarkSet): array
    {
        $start = microtime(true);
        $options = $this->firestoreDashboardOptions();
        $results = [];
        $visitorCount = 0;

        foreach ($this->firebase->userCollection(FirebaseService::VISITOR_ROLE)->documents($options) as $visitorDocument) {
            if (! $visitorDocument->exists()) {
                continue;
            }
            $visitorCount++;

            $visitorData = $visitorDocument->data();
            $visitorId = trim((string) ($visitorData['uid'] ?? $visitorDocument->id()));
            $visitorName = trim((string) ($visitorData['fullName'] ?? $visitorData['name'] ?? ''));
            if ($visitorName === '') {
                $visitorName = trim((string) ($visitorData['email'] ?? $visitorId));
            }

            foreach ($visitorDocument->reference()->collection('triviaResults')->documents($options) as $resultDocument) {
                if (! $resultDocument->exists()) {
                    continue;
                }

                $result = $this->resultFromTriviaDocument($resultDocument, $visitorId, $visitorName, $landmarkSet);
                if ($result !== null) {
                    $results[] = $result;
                }
            }
        }

        Log::info('Timing Firestore query', [
            'query' => 'visitor_profiles.trivia_results_subcollections',
            'visitor_count' => $visitorCount,
            'landmark_count' => count($landmarkSet),
            'records' => count($results),
            'duration_ms' => (int) round((microtime(true) - $start) * 1000),
        ]);

        return $results;
    }

    /** @param array<string, true> $landmarkSet */
    private function resultFromTriviaData(string $id, array $data, array $landmarkSet): ?array
    {
        $landmarkId = trim((string) ($data['landmarkId'] ?? $data['landmark_id'] ?? $data['siteId'] ?? $data['site_id'] ?? ''));
        if (! isset($landmarkSet[$landmarkId])) {
            return null;
        }

        $completedAt = $this->toCarbon($data['createdAt'] ?? $data['created_at'] ?? $data['completed_at'] ?? $data['timestamp'] ?? null);
        $visitorId = trim((string) ($data['visitor_id'] ?? $data['visitorId'] ?? $data['uid'] ?? $data['userId'] ?? ''));
        $visitorName = trim((string) ($data['visitor_name'] ?? $data['visitorName'] ?? $data['visitor_email'] ?? $data['name'] ?? $data['userName'] ?? $visitorId));

        return [
            'id' => $id,
            'landmark_id' => $landmarkId,
            'landmark_name' => trim((string) ($data['landmarkName'] ?? $data['landmark_name'] ?? '')),
            'activity_type' => 'quiz_attempt',
            'visitor_key' => $visitorId,
            'visitor_name' => $visitorName !== '' ? $visitorName : 'Visitor',
            'quiz_score' => $data['score_points'] ?? $data['score'] ?? $data['totalScore'] ?? null,
            'quiz_total' => $data['total_points'] ?? $data['maxScore'] ?? $data['quiz_total'] ?? $data['total'] ?? null,
            'score_percentage' => $data['score_percentage'] ?? $data['scorePercent'] ?? $data['percentage'] ?? null,
            'correct_answers' => $data['correct_answers'] ?? $data['correctCount'] ?? $data['correct'] ?? null,
            'total_questions' => $data['total_questions'] ?? $data['total'] ?? null,
            'occurred_at' => $completedAt?->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, true>  $landmarkSet
     * @return array<string, mixed>|null
     */
    private function resultFromTriviaDocument(mixed $document, string $visitorId, string $visitorName, array $landmarkSet): ?array
    {
        $data = $document->data();
        $landmarkId = trim((string) ($data['landmarkId'] ?? $data['landmark_id'] ?? $data['siteId'] ?? $data['site_id'] ?? ''));
        if (! isset($landmarkSet[$landmarkId])) {
            return null;
        }

        $completedAt = $this->toCarbon($data['createdAt'] ?? $data['created_at'] ?? $data['completed_at'] ?? $data['timestamp'] ?? null);
        $scorePercentage = $data['score_percentage'] ?? $data['scorePercent'] ?? $data['percentage'] ?? null;
        $score = $data['score_points'] ?? $data['score'] ?? $data['totalScore'] ?? null;
        $total = $data['total_points'] ?? $data['maxScore'] ?? $data['quiz_total'] ?? null;

        return [
            'id' => $document->id(),
            'landmark_id' => $landmarkId,
            'landmark_name' => trim((string) ($data['landmarkName'] ?? $data['landmark_name'] ?? '')),
            'activity_type' => 'quiz_attempt',
            'visitor_key' => $visitorId,
            'visitor_name' => $visitorName !== '' ? $visitorName : 'Visitor',
            'quiz_score' => $score,
            'quiz_total' => $total,
            'score_percentage' => $scorePercentage,
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
