<?php

use App\Firebase\FirebaseMigration;
use Google\Cloud\Firestore\FieldValue;

return new class extends FirebaseMigration {
    public function up()
    {
        foreach ($this->firestore()->collection('landmarks')->documents() as $doc) {
            if (! $doc->exists()) {
                continue;
            }

            $data = $doc->data();
            $url = trim((string) ($data['image_url'] ?? ''));
            if ($url === '') {
                $url = trim((string) ($data['image_path'] ?? ''));
            }
            if (! array_key_exists('image_url', $data)) {
                continue;
            }

            $updates = [
                ['path' => 'image_url', 'value' => FieldValue::deleteField()],
            ];
            if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL) !== false) {
                $updates[] = ['path' => 'image_path', 'value' => $url];
            }

            $doc->reference()->update($updates);
        }
    }

    public function down()
    {
        // This cleanup intentionally does not restore the removed legacy field.
    }
};
