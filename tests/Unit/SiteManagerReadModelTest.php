<?php

namespace Tests\Unit;

use App\Services\FirebaseService;
use App\Services\SiteManagerReadModel;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class SiteManagerReadModelTest extends TestCase
{
    public function test_managed_landmarks_are_scoped_and_cached(): void
    {
        Cache::flush();

        $document = Mockery::mock();
        $document->shouldReceive('exists')->once()->andReturnTrue();
        $document->shouldReceive('id')->twice()->andReturn('landmark-1');
        $document->shouldReceive('data')->once()->andReturn([
            'name' => 'Managed Landmark',
            'manager_uid' => 'manager-1',
        ]);

        $uidQuery = Mockery::mock();
        $uidQuery->shouldReceive('documents')->once()->andReturn([$document]);
        $camelQuery = Mockery::mock();
        $camelQuery->shouldReceive('documents')->once()->andReturn([]);

        $collection = Mockery::mock();
        $collection->shouldReceive('where')->once()->with('manager_uid', '==', 'manager-1')->andReturn($uidQuery);
        $collection->shouldReceive('where')->once()->with('managerUid', '==', 'manager-1')->andReturn($camelQuery);

        $firestore = Mockery::mock();
        $firestore->shouldReceive('collection')->twice()->with('landmarks')->andReturn($collection);

        $firebase = Mockery::mock(FirebaseService::class);
        $firebase->shouldReceive('firestore')->twice()->andReturn($firestore);

        $readModel = new SiteManagerReadModel($firebase);

        $this->assertSame($readModel->landmarks('manager-1'), $readModel->landmarks('manager-1'));
    }
}
