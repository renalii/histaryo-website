<?php

use App\Firebase\FirebaseMigration;
use Google\Cloud\Firestore\FieldValue;

return new class extends FirebaseMigration {
    public function up()
    {
        foreach ($this->firestore()->collection('landmarks')->documents() as $doc) {
            if (! $doc->exists() || ! array_key_exists('image_base64', $doc->data())) {
                continue;
            }

            $doc->reference()->update([
                ['path' => 'image_base64', 'value' => FieldValue::deleteField()],
            ]);
        }
    }

    public function down()
    {
        // This cleanup intentionally cannot restore removed image data.
    }
};
