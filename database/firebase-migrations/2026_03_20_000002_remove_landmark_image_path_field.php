<?php

use App\Firebase\FirebaseMigration;
use Google\Cloud\Firestore\FieldValue;

return new class extends FirebaseMigration {
    public function up()
    {
        $landmarks = $this->firestore()->collection('landmarks')->documents();

        foreach ($landmarks as $doc) {
            if (!$doc->exists()) {
                continue;
            }

            $data = $doc->data();

            if (!array_key_exists('image_path', $data)) {
                continue;
            }

            $doc->reference()->update([
                ['path' => 'image_path', 'value' => FieldValue::deleteField()],
            ]);
        }
    }

    public function down()
    {
        // Intentionally left blank. This cleanup permanently removes a legacy field.
    }
};