@extends('layouts.sidebar')

@section('content')
    @php
        /** @var string $id */
        /** @var array $landmark */
        /** @var string|null $mapboxToken */
        $v = fn (string $key, $default = '') => old($key, $landmark[$key] ?? $default);
        $imageSrc = null;
        if (! empty($landmark['image_base64'])) {
            $mime = $landmark['image_mime'] ?? 'image/jpeg';
            $imageSrc = 'data:'.$mime.';base64,'.$landmark['image_base64'];
        }

        $coordLat = old('latitude');
        if ($coordLat === null || $coordLat === '') {
            foreach (['latitude', 'lati'] as $k) {
                $cv = $landmark[$k] ?? null;
                if ($cv !== null && $cv !== '' && is_numeric($cv)) {
                    $coordLat = (string) $cv;
                    break;
                }
            }
        }
        if ($coordLat === null) {
            $coordLat = '';
        }

        $coordLng = old('longitude');
        if ($coordLng === null || $coordLng === '') {
            foreach (['longitude', 'longti'] as $k) {
                $cv = $landmark[$k] ?? null;
                if ($cv !== null && $cv !== '' && is_numeric($cv)) {
                    $coordLng = (string) $cv;
                    break;
                }
            }
        }
        if ($coordLng === null) {
            $coordLng = '';
        }
    @endphp

    <style>
        .cl-edit-wrap { width:100%; margin:0 auto 2rem; }
        .cl-edit-head {
            margin-bottom:1.25rem;
            display:flex; flex-wrap:wrap; justify-content:space-between; gap:.75rem; align-items:flex-start;
        }
        .cl-edit-title {
            margin:0; font-size:clamp(1.45rem,3vw,1.85rem); font-weight:800; color:#7A2E1F; letter-spacing:-.02em;
        }
        .cl-edit-muted { margin:.35rem 0 0 0; color:#6b7280; font-size:.92rem; }
        .cl-back {
            padding:.55rem 1rem; border-radius:10px; border:1px solid #e5e7eb;
            background:#fff; color:#374151; font-weight:600; font-size:.88rem;
            text-decoration:none; transition:background .15s ease, border-color .15s ease;
        }
        .cl-back:hover { background:#f9fafb; border-color:#d1d5db; }
        .cl-card {
            background:#fff; border-radius:14px;
            border:1px solid #eceff3; box-shadow:0 8px 30px rgba(15,23,42,.06); overflow:hidden;
        }
        .cl-card-inner { padding:1.35rem 1.5rem 1.25rem; }
        @media (min-width:640px) { .cl-card-inner { padding:1.65rem 1.85rem 1.35rem; } }
        .cl-flash-ok {
            padding:.85rem 1.1rem; border-radius:12px;
            background:#ecfdf5; color:#166534; border:1px solid #bbf7d0; margin-bottom:1rem; font-weight:600; font-size:.92rem;
        }
        .cl-flash-err {
            padding:.85rem 1.1rem; border-radius:12px;
            background:#fef2f2; border:1px solid #fecaca; color:#991b1b; margin-bottom:1rem;
        }
        .cl-flash-err ul { margin:.25rem 0 0; padding-left:1.15rem; }
        .cl-section-title {
            margin:1.35rem 0 1rem; font-size:.78rem; font-weight:700; text-transform:uppercase;
            letter-spacing:.06em; color:#7A2E1F; padding-bottom:.45rem; border-bottom:1px solid #f1f5f9;
        }
        .cl-section-title:first-child { margin-top:0; }
        .cl-field { margin-bottom:1.05rem; }
        .cl-field label {
            display:block; font-weight:600; font-size:.89rem; color:#1f2937; margin-bottom:.35rem;
        }
        .cl-optional { font-weight:500; font-size:.73rem; color:#9ca3af; margin-left:.25rem; }
        .cl-input, .cl-select, .cl-textarea {
            width:100%; box-sizing:border-box; padding:.65rem .85rem;
            border:1px solid #e5e7eb; border-radius:10px; font-size:.95rem;
            background:#fafafa; color:#111827; transition:border-color .15s, box-shadow .15s, background .15s;
        }
        .cl-input:hover, .cl-select:hover, .cl-textarea:hover { border-color:#d1d5db; background:#fff; }
        .cl-input:focus, .cl-select:focus, .cl-textarea:focus {
            outline:none; border-color:#E8B34B; background:#fff;
            box-shadow:0 0 0 3px rgba(232,179,75,.22);
        }
        .cl-textarea { min-height:120px; resize:vertical; line-height:1.55; }
        .cl-grid-2 { display:grid; gap:.9rem 1rem; }
        @media (min-width:480px) { .cl-grid-2 { grid-template-columns:1fr 1fr; } }
        .cl-readonly {
            font-family: ui-monospace, monospace; font-size:.85rem; color:#4b5563;
            padding:.55rem .75rem; background:#f9fafb; border-radius:8px; border:1px solid #f3f4f6;
        }
        .cl-preview-img {
            max-width:320px; width:100%; height:auto; border-radius:12px;
            border:1px solid #e5e7eb; margin-top:.35rem;
        }
        .cl-file-hint { font-size:.78rem; color:#9ca3af; margin:.35rem 0 0 0; }
        .cl-actions {
            margin-top:1.5rem; padding-top:1.25rem; border-top:1px solid #f1f5f9;
            display:flex; flex-wrap:wrap; align-items:center; gap:.65rem;
        }
        .cl-btn-submit {
            padding:.78rem 1.35rem; border-radius:12px;
            border:1px solid #F3C96A;
            background:linear-gradient(180deg,#f3d073 0%,#E8B34B 100%);
            color:#461c14; font-weight:700; font-size:.95rem;
            cursor:pointer; box-shadow:0 4px 14px rgba(122,46,31,.12);
        }
        .cl-btn-submit:hover { filter:brightness(1.02); transform:translateY(-1px); }
        .cl-danger {
            margin-top:1.75rem; padding-top:1.25rem; border-top:1px dashed #fecaca;
        }
        .cl-btn-delete {
            padding:.55rem 1rem; border-radius:10px;
            border:1px solid #fecaca; background:#fef2f2; color:#991b1b;
            font-weight:600; font-size:.87rem; cursor:pointer;
        }
        .cl-btn-delete:hover { background:#fee2e2; }
        .cl-select {
            appearance:none;
            cursor:pointer;
            background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat:no-repeat; background-position:right .75rem center; background-size:1.05rem;
            padding-right:2.25rem;
        }
        .cl-map-wrap {
            margin:0 0 1rem;
            border-radius:12px;
            overflow:hidden;
            border:1px solid #e5e7eb;
            background:#e8edf2;
            min-height:260px;
            position:relative;
        }
        #cl-landmark-map { height:280px; width:100%; }
        .cl-map-caption {
            font-size:.8rem;
            color:#6b7280;
            margin:.35rem 0 1rem;
            line-height:1.45;
        }
        .mapboxgl-ctrl-attrib-inner { font-size:10px; }
    </style>
    @if (! empty(trim((string) ($mapboxToken ?? ''))))
    <link href="https://api.mapbox.com/mapbox-gl-js/v3.3.0/mapbox-gl.css" rel="stylesheet"/>
    @endif

    <div class="cl-edit-wrap">
        <div class="cl-edit-head">
            <div>
                <h1 class="cl-edit-title">My landmark</h1>
               
            </div>
            <a href="{{ route('landmarks.index') }}" class="cl-back">← Landmarks</a>
        </div>

        @if (session('success'))
            <p class="cl-flash-ok" role="status">{{ session('success') }}</p>
        @endif

        @if ($errors->any())
            <div class="cl-flash-err" role="alert">
                <strong>Please fix:</strong>
                <ul>
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="cl-card">
            <form method="POST" action="{{ route('landmarks.update', $id) }}" enctype="multipart/form-data" class="cl-card-inner">
                @csrf
                @method('PUT')

                <h2 class="cl-section-title">Basics</h2>
                @if (($code = trim((string) ($landmark['landmarkcode'] ?? ''))) !== '')
                    <div class="cl-field">
                        <span class="cl-readonly"><strong style="font-weight:600;color:#374151">Landmark code</strong> — {{ $code }} <span style="font-weight:500;color:#6b7280">(managed by Site Manager)</span></span>
                    </div>
                @endif

                <div class="cl-field">
                    <label for="name">Landmark name</label>
                    <input class="cl-input" type="text" id="name" name="name" required
                           autocomplete="organization"
                           value="{{ $v('name') }}">
                </div>

                <div class="cl-field">
                    <label for="category">Category</label>
                    <select id="category" name="category" class="cl-select" required>
                        @foreach (['Historical', 'Natural', 'Cultural', 'Religious', 'Modern'] as $cat)
                            <option value="{{ $cat }}" {{ (string) $v('category', 'Historical') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="cl-field">
                    <label for="description">Description <span class="cl-optional">optional</span></label>
                    <textarea id="description" name="description" class="cl-textarea" rows="5"
                              placeholder="What visitors should know">{{ $v('description') }}</textarea>
                </div>

                <h2 class="cl-section-title">Location <span class="cl-optional">(optional)</span></h2>

                @if (! empty(trim((string) ($mapboxToken ?? ''))))
                    <div class="cl-map-wrap"><div id="cl-landmark-map" role="application" aria-label="Site location map"></div></div>
                    <p class="cl-map-caption">Click the map or drag the pin to set coordinates. Fields below update automatically.</p>
                @else
                    <p class="cl-map-caption">
                        Add <strong>MAPBOX_TOKEN</strong> in <code>.env</code> to enable an interactive map here, or type latitude and longitude manually.
                        @if ($coordLat !== '' && $coordLng !== '')
                            (<a href="https://www.openstreetmap.org/#map=16/{{ $coordLat }}/{{ $coordLng }}" rel="noopener noreferrer" target="_blank">Preview on OpenStreetMap</a>)
                        @endif
                    </p>
                @endif

                <div class="cl-grid-2">
                    <div class="cl-field">
                        <label for="latitude">Latitude</label>
                        <input class="cl-input" type="text" id="latitude" name="latitude" inputmode="decimal"
                               placeholder="e.g. 10.3157" value="{{ $coordLat }}">
                    </div>
                    <div class="cl-field">
                        <label for="longitude">Longitude</label>
                        <input class="cl-input" type="text" id="longitude" name="longitude" inputmode="decimal"
                               placeholder="e.g. 123.8854" value="{{ $coordLng }}">
                    </div>
                </div>

                <h2 class="cl-section-title">Media</h2>
                <div class="cl-field">
                    <label for="video_url">Video URL <span class="cl-optional">optional</span></label>
                    <input class="cl-input" type="url" id="video_url" name="video_url"
                           placeholder="https://youtube.com/watch?v=…" value="{{ $v('video_url') }}">
                </div>
                <div class="cl-field">
                    <label for="image">Image <span class="cl-optional">optional, max 512 KB</span></label>
                    <input id="image" type="file" name="image" accept="image/*">
                    <p class="cl-file-hint">Upload replaces the landmark image shown to visitors.</p>
                    @if ($imageSrc)
                        <p class="cl-file-hint" style="color:#374151;margin-top:.5rem;"><strong>Current image</strong></p>
                        <img class="cl-preview-img" src="{{ $imageSrc }}" alt="Current landmark image">
                    @endif
                </div>

                <div class="cl-actions">
                    <button type="submit" class="cl-btn-submit">Save changes</button>
                </div>
            </form>

            <div class="cl-card-inner cl-danger">
                <h2 class="cl-section-title" style="margin-top:0;color:#991b1b;border-color:#fecaca;">Danger zone</h2>
                <p class="cl-edit-muted">Deleting removes this landmark record, QR links, trivia, and synced images tied to this id.</p>
                <form method="POST" action="{{ route('landmarks.destroy', $id) }}" style="margin-top:.85rem;"
                      onsubmit="return confirm('Delete this landmark permanently? This cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="cl-btn-delete">Delete landmark</button>
                </form>
            </div>
        </div>
    </div>

    @if (! empty(trim((string) ($mapboxToken ?? ''))))
    <script src="https://api.mapbox.com/mapbox-gl-js/v3.3.0/mapbox-gl.js"></script>
    <script>
    (function () {
        mapboxgl.accessToken = @json($mapboxToken);
        var latEl = document.getElementById('latitude');
        var lngEl = document.getElementById('longitude');

        function parseCoords() {
            var la = latEl ? parseFloat(String(latEl.value).replace(',', '.')) : NaN;
            var ln = lngEl ? parseFloat(String(lngEl.value).replace(',', '.')) : NaN;
            if (!isFinite(la) || !isFinite(ln)) return null;
            return { lat: la, lng: ln };
        }

        function syncInputs(lng, lat) {
            if (!latEl || !lngEl) return;
            latEl.value = Math.round(lat * 1e6) / 1e6;
            lngEl.value = Math.round(lng * 1e6) / 1e6;
        }

        var DEFAULT = { center: [123.8854, 10.3157], zoom: 12 };

        var map = new mapboxgl.Map({
            container: 'cl-landmark-map',
            style: 'mapbox://styles/mapbox/streets-v12',
            center: DEFAULT.center,
            zoom: DEFAULT.zoom,
        });

        map.addControl(new mapboxgl.NavigationControl(), 'top-right');
        map.addControl(new mapboxgl.FullscreenControl(), 'top-right');

        var marker = null;

        function setMarkerLngLat(lngLat) {
            if (!marker) {
                marker = new mapboxgl.Marker({ draggable: true, color: '#7A2E1F' })
                    .setLngLat(lngLat)
                    .addTo(map);
                marker.on('dragend', function () {
                    var p = marker.getLngLat();
                    syncInputs(p.lng, p.lat);
                });
            } else {
                marker.setLngLat(lngLat);
            }
            syncInputs(lngLat.lng, lngLat.lat);
        }

        var initial = parseCoords();
        if (initial) {
            map.jumpTo({
                center: [initial.lng, initial.lat],
                zoom: 14,
            });
            setMarkerLngLat(new mapboxgl.LngLat(initial.lng, initial.lat));
        }

        map.on('click', function (e) {
            setMarkerLngLat(e.lngLat);
        });
    })();
    </script>
    @endif
@endsection
