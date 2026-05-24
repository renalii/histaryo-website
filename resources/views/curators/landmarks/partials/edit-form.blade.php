@php
    /** @var string $landmarkId */
    /** @var array $data */
    $d = isset($data) && is_array($data) ? $data : [];
@endphp
<form method="POST" action="{{ route('landmarks.update', $landmarkId) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <label for="cl-name-{{ md5($landmarkId) }}">Name</label>
    <input id="cl-name-{{ md5($landmarkId) }}" type="text" name="name" value="{{ $d['name'] ?? '' }}" required>

    @if (! empty($d['landmarkcode'] ?? ''))
        <p class="cl-modal-readonly">Landmark code <span class="mono">{{ $d['landmarkcode'] }}</span></p>
    @endif

    <label for="cl-cat-{{ md5($landmarkId) }}">Category</label>
    <select id="cl-cat-{{ md5($landmarkId) }}" name="category" required>
        @foreach (['Unspecified', 'Historical', 'Natural', 'Cultural', 'Religious', 'Modern'] as $cat)
            <option value="{{ $cat }}" {{ (($d['category'] ?? '') == $cat) ? 'selected' : '' }}>{{ $cat }}</option>
        @endforeach
    </select>

    <label for="cl-desc-{{ md5($landmarkId) }}">Description</label>
    <textarea id="cl-desc-{{ md5($landmarkId) }}" name="description" rows="4">{{ $d['description'] ?? '' }}</textarea>

    <label for="cl-lat-{{ md5($landmarkId) }}">Latitude</label>
    <input id="cl-lat-{{ md5($landmarkId) }}" type="text" name="latitude" inputmode="decimal"
           value="{{ $d['latitude'] ?? '' }}" placeholder="Optional">

    <label for="cl-lng-{{ md5($landmarkId) }}">Longitude</label>
    <input id="cl-lng-{{ md5($landmarkId) }}" type="text" name="longitude" inputmode="decimal"
           value="{{ $d['longitude'] ?? '' }}" placeholder="Optional">

    <label for="cl-video-{{ md5($landmarkId) }}">Video URL</label>
    <input id="cl-video-{{ md5($landmarkId) }}" type="url" name="video_url" value="{{ $d['video_url'] ?? '' }}">

    <label for="cl-img-{{ md5($landmarkId) }}">Replace image</label>
    <input id="cl-img-{{ md5($landmarkId) }}" type="file" name="image" accept="image/*">

    <button type="submit">Update</button>
</form>
