<?php

namespace App\Support;

use App\Services\LandmarkEvidenceStorage;
use Illuminate\Http\UploadedFile;

final class LandmarkEvidence
{
    /** @return array<int, string> */
    public static function validationRules(): array
    {
        return [
            'evidence_files' => ['required', 'array', 'min:1', 'max:5'],
            'evidence_files.*' => [
                'file',
                'mimes:pdf,jpg,jpeg,png,webp,doc,docx',
                'max:5120',
            ],
        ];
    }

    /** @return array<string, string> */
    public static function validationMessages(): array
    {
        return [
            'evidence_files.required' => 'Upload at least one evidence or supporting document (PDF, image, or Word file).',
            'evidence_files.min' => 'Upload at least one evidence or supporting document.',
            'evidence_files.max' => 'You may upload up to five evidence files per landmark.',
            'evidence_files.*.mimes' => 'Each evidence file must be a PDF, image (JPG, PNG, WebP), or Word document.',
            'evidence_files.*.max' => 'Each evidence file must be 5 MB or smaller.',
        ];
    }

    /**
     * @param  list<UploadedFile>  $files
     * @return list<array{filename: string, mime: string, storage_path: string, uploaded_at: string}>
     */
    public static function storeUploadedFiles(string $landmarkId, array $files): array
    {
        return LandmarkEvidenceStorage::storeForLandmark($landmarkId, $files);
    }

    /** URL for viewing a stored evidence entry (disk path or legacy base64). */
    public static function documentHref(array $document): ?string
    {
        $url = LandmarkEvidenceStorage::publicUrl($document);
        if ($url !== null) {
            return $url;
        }

        $b64 = (string) ($document['base64'] ?? '');
        if ($b64 === '') {
            return null;
        }

        $mime = (string) ($document['mime'] ?? 'application/octet-stream');

        return 'data:'.$mime.';base64,'.$b64;
    }

    public static function isActive(array $landmarkData): bool
    {
        $activation = strtolower((string) ($landmarkData['activation_status'] ?? 'active'));

        return $activation !== 'pending' && $activation !== 'rejected';
    }
}
