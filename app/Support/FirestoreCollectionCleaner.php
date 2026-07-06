<?php

namespace App\Support;

final class FirestoreCollectionCleaner
{
    /**
     * Delete a collection in bounded batches to avoid one network request per document.
     */
    public static function deleteAll(object $firestore, object $collection, int $batchSize = 400): int
    {
        $batchSize = max(1, min($batchSize, 500));
        $totalDeleted = 0;

        do {
            $writer = $firestore->batch();
            $deleted = 0;

            foreach ($collection->limit($batchSize)->documents() as $document) {
                $writer->delete($document->reference());
                $deleted++;
            }

            if ($deleted > 0) {
                $writer->commit();
                $totalDeleted += $deleted;
            }
        } while ($deleted === $batchSize);

        return $totalDeleted;
    }
}
