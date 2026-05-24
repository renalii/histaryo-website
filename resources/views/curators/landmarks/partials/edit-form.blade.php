@php
    /** @var string $landmarkId */
    /** @var array $data */
    $d = isset($data) && is_array($data) ? $data : [];
    $modalSafe = $modalSafe ?? preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) ($landmarkId ?? 'landmark'));
    $fieldHash = md5($landmarkId);
@endphp
<form method="POST" action="{{ route('landmarks.update', $landmarkId) }}" enctype="multipart/form-data" class="lm-editor-form">
    @csrf
    @method('PUT')

    @include('curators.landmarks.partials.landmark-form-fields', [
        'landmarkId' => $landmarkId,
        'd' => $d,
        'modalSafe' => $modalSafe,
        'fieldHash' => $fieldHash,
        'mapboxToken' => $mapboxToken ?? null,
        'formContext' => 'modal',
    ])

    <div class="modal-cl__form-actions">
        <button type="button" class="modal-cl__btn-secondary" onclick="cuCloseModalCl('editModal_{{ $modalSafe }}')">Cancel</button>
        <button type="submit" class="modal-cl__btn-primary">Save changes</button>
    </div>
</form>
