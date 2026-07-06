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
        $this->assertNull($result['quiz_total']);
        $this->assertArrayNotHasKey('score_percentage', $result);
        $this->assertSame(5, $result['correct_answers']);
        $this->assertSame(5, $result['total_questions']);
        $this->assertSame('2026-07-02T08:30:59+00:00', $result['occurred_at']);
    }
}
