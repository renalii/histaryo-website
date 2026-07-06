<?php

namespace Tests\Unit;

use App\Support\FirestoreCollectionCleaner;
use PHPUnit\Framework\TestCase;

class FirestoreCollectionCleanerTest extends TestCase
{
    public function test_it_deletes_documents_in_bounded_batches(): void
    {
        $collection = new FakeFirestoreCollection(range(1, 9));
        $firestore = new FakeFirestore($collection);

        $deleted = FirestoreCollectionCleaner::deleteAll($firestore, $collection, 4);

        $this->assertSame(9, $deleted);
        $this->assertSame([], $collection->documents);
        $this->assertSame([4, 4, 1], $firestore->committedBatchSizes);
        $this->assertSame([4, 4, 4], $collection->requestedLimits);
    }

    public function test_it_does_not_commit_an_empty_collection(): void
    {
        $collection = new FakeFirestoreCollection([]);
        $firestore = new FakeFirestore($collection);

        $this->assertSame(0, FirestoreCollectionCleaner::deleteAll($firestore, $collection));
        $this->assertSame([], $firestore->committedBatchSizes);
    }
}

class FakeFirestore
{
    public array $committedBatchSizes = [];

    public function __construct(private FakeFirestoreCollection $collection) {}

    public function batch(): FakeFirestoreWriter
    {
        return new FakeFirestoreWriter($this->collection, $this);
    }
}

class FakeFirestoreWriter
{
    private array $references = [];

    public function __construct(
        private FakeFirestoreCollection $collection,
        private FakeFirestore $firestore
    ) {}

    public function delete(int $reference): void
    {
        $this->references[] = $reference;
    }

    public function commit(): void
    {
        $this->collection->documents = array_values(array_diff(
            $this->collection->documents,
            $this->references
        ));
        $this->firestore->committedBatchSizes[] = count($this->references);
    }
}

class FakeFirestoreCollection
{
    public array $requestedLimits = [];

    private int $limit = 0;

    public function __construct(public array $documents) {}

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        $this->requestedLimits[] = $limit;

        return $this;
    }

    public function documents(): array
    {
        return array_map(
            fn (int $reference) => new FakeFirestoreDocument($reference),
            array_slice($this->documents, 0, $this->limit)
        );
    }
}

class FakeFirestoreDocument
{
    public function __construct(private int $reference) {}

    public function reference(): int
    {
        return $this->reference;
    }
}
