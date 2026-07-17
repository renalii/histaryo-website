<?php

namespace Tests\Unit;

use App\Services\FirebaseService;
use App\Services\QuizResultService;
use App\Support\ArrayDocumentSnapshot;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class QuizResultServiceTest extends TestCase
{
    public function test_it_normalizes_nested_visitor_trivia_results(): void
    {
        $service = new QuizResultService($this->createMock(FirebaseService::class));
        $method = new ReflectionMethod(QuizResultService::class, 'resultFromTriviaDocument');
        $method->setAccessible(true);

        $result = $method->invoke($service, new ArrayDocumentSnapshot('result-1', [
            'landmarkId' => '4aad73e1ea35411291b2',
            'landmarkName' => 'Osmena Peak',
            'scorePercent' => 83,
            'totalScore' => 4143,
            'maxScore' => 5000,
            'correctCount' => 5,
            'total' => 5,
            'createdAt' => '2026-07-02T08:30:59.955Z',
        ]), 'visitor-1', 'Rena Olivo', ['4aad73e1ea35411291b2' => true]);

        $this->assertSame('quiz_attempt', $result['activity_type']);
        $this->assertSame('visitor-1', $result['visitor_key']);
        $this->assertSame('Rena Olivo', $result['visitor_name']);
        $this->assertSame('4aad73e1ea35411291b2', $result['landmark_id']);
        $this->assertSame('Osmena Peak', $result['landmark_name']);
        $this->assertSame(4143, $result['quiz_score']);
        $this->assertSame(5000, $result['quiz_total']);
        $this->assertSame(83, $result['score_percentage']);
        $this->assertSame(5, $result['correct_answers']);
        $this->assertSame(5, $result['total_questions']);
        $this->assertSame('2026-07-02T08:30:59+00:00', $result['occurred_at']);
    }

    public function test_it_matches_nested_trivia_results_by_site_id(): void
    {
        $service = new QuizResultService($this->createMock(FirebaseService::class));
        $method = new ReflectionMethod(QuizResultService::class, 'resultFromTriviaDocument');
        $method->setAccessible(true);

        $result = $method->invoke($service, new ArrayDocumentSnapshot('result-2', [
            'siteId' => 'site-1',
            'scorePercent' => 91,
            'totalScore' => 4550,
            'maxScore' => 5000,
            'createdAt' => '2026-07-10T08:00:00.000Z',
        ]), 'visitor-2', 'Mara', ['site-1' => true]);

        $this->assertSame('site-1', $result['landmark_id']);
        $this->assertSame(4550, $result['quiz_score']);
        $this->assertSame(5000, $result['quiz_total']);
        $this->assertSame(91, $result['score_percentage']);
    }
}
