<?php

namespace Tests\Unit;

use App\Http\Controllers\Curator\TipReviewController;
use App\Services\FirebaseService;
use App\Support\FirestoreTipCollections;
use Mockery;
use Tests\TestCase;

class CuratorTipReviewControllerTest extends TestCase
{
    public function test_landmark_tips_use_bounded_landmark_scoped_queries(): void
    {
        config(['services.firebase.tip_query_timeout' => 2]);

        $query = Mockery::mock();
        $query->shouldReceive('documents')
            ->times(count(FirestoreTipCollections::names()) * 2)
            ->with(Mockery::on(fn (array $options): bool => $options === [
                'maxRetries' => 0,
                'requestTimeout' => 2.0,
                'retries' => 0,
            ]))
            ->andReturn([]);

        $collection = Mockery::mock();
        $collection->shouldReceive('where')
            ->times(count(FirestoreTipCollections::names()))
            ->with('landmark_id', '==', 'landmark-1')
            ->andReturn($query);
        $collection->shouldReceive('where')
            ->times(count(FirestoreTipCollections::names()))
            ->with('landmarkId', '==', 'landmark-1')
            ->andReturn($query);
        $collection->shouldNotReceive('documents');

        $firestore = Mockery::mock();
        $firestore->shouldReceive('collection')
            ->times(count(FirestoreTipCollections::names()) * 2)
            ->andReturn($collection);

        $firebase = Mockery::mock(FirebaseService::class);
        $firebase->shouldReceive('firestore')->once()->andReturn($firestore);

        $controller = new TipReviewController($firebase);

        $this->assertSame([], $controller->tipsForLandmark('landmark-1'));
    }

    public function test_landmark_tips_include_mobile_documents_using_camel_case_landmark_id(): void
    {
        $tipDocument = Mockery::mock();
        $tipDocument->shouldReceive('exists')->once()->andReturnTrue();
        $tipDocument->shouldReceive('id')->times(2)->andReturn('tip-1');
        $tipDocument->shouldReceive('data')->once()->andReturn([
            'landmarkId' => 'landmark-1',
            'title' => 'A Place of Faith and Miracles',
            'content' => 'Visitor tip content',
            'status' => 'pending',
            'submittedBy' => 'visitor@example.com',
        ]);

        $emptyQuery = Mockery::mock();
        $emptyQuery->shouldReceive('documents')->andReturn([]);

        $camelCaseQuery = Mockery::mock();
        $camelCaseQuery->shouldReceive('documents')->once()->andReturn([$tipDocument]);

        $tipCollection = Mockery::mock();
        $tipCollection->shouldReceive('where')
            ->once()
            ->with('landmark_id', '==', 'landmark-1')
            ->andReturn($emptyQuery);
        $tipCollection->shouldReceive('where')
            ->once()
            ->with('landmarkId', '==', 'landmark-1')
            ->andReturn($camelCaseQuery);

        $emptyCollection = Mockery::mock();
        $emptyCollection->shouldReceive('where')->andReturn($emptyQuery);

        $firestore = Mockery::mock();
        $firestore->shouldReceive('collection')
            ->twice()
            ->with('crowdsourced_tips')
            ->andReturn($tipCollection);
        foreach (array_diff(FirestoreTipCollections::names(), ['crowdsourced_tips']) as $collectionName) {
            $firestore->shouldReceive('collection')
                ->twice()
                ->with($collectionName)
                ->andReturn($emptyCollection);
        }

        $firebase = Mockery::mock(FirebaseService::class);
        $firebase->shouldReceive('firestore')->once()->andReturn($firestore);

        $controller = new TipReviewController($firebase);
        $tips = $controller->tipsForLandmark('landmark-1');

        $this->assertCount(1, $tips);
        $this->assertSame('tip-1', $tips[0]['id']);
        $this->assertSame('landmark-1', $tips[0]['landmark_id']);
        $this->assertSame('A Place of Faith and Miracles', $tips[0]['title']);
        $this->assertSame('pending', $tips[0]['status']);
    }
}
