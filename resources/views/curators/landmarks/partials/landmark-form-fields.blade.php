@php
    /** @var string $landmarkId */
    /** @var array $d */
    $d = is_array($d ?? null) ? $d : [];
    $fieldHash = $fieldHash ?? md5($landmarkId);
    $modalSafe = $modalSafe ?? preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) ($landmarkId ?? 'landmark'));
    $mapId = 'lm-map-' . $modalSafe;
    $v = fn (string $key, $default = '') => old($key, $d[$key] ?? $default);

    $coordLat = old('latitude');
    if ($coordLat === null || $coordLat === '') {
        foreach (['latitude', 'lati'] as $k) {
            $cv = $d[$k] ?? null;
            if ($cv !== null && $cv !== '' && is_numeric($cv)) {
                $coordLat = (string) $cv;
                break;
            }
        }
    }
    $coordLat = $coordLat ?? '';

    $coordLng = old('longitude');
    if ($coordLng === null || $coordLng === '') {
        foreach (['longitude', 'longti'] as $k) {
            $cv = $d[$k] ?? null;
            if ($cv !== null && $cv !== '' && is_numeric($cv)) {
                $coordLng = (string) $cv;
                break;
            }
        }
    }
    $coordLng = $coordLng ?? '';

    $imageSrc = null;
    if (! empty($d['image_url'])) {
        $imageSrc = $d['image_url'];
    } elseif (! empty($d['image_base64'])) {
        $mime = $d['image_mime'] ?? 'image/jpeg';
        $imageSrc = str_starts_with($d['image_base64'], 'data:')
            ? $d['image_base64']
            : 'data:' . $mime . ';base64,' . $d['image_base64'];
    }

    $hasMap = ! empty(trim((string) ($mapboxToken ?? '')));
    $categories = ['Historical', 'Natural', 'Cultural', 'Religious', 'Modern'];
    if (($formContext ?? 'modal') === 'modal') {
        array_unshift($categories, 'Unspecified');
    }
@endphp

<div class="lm-editor-grid">
    <div class="lm-editor-col">
        <section class="lm-editor-card">
            <header class="lm-editor-card__head">
                <h4 class="lm-editor-card__title">Landmark details</h4>
            </header>
            <div class="lm-editor-card__body">
                @if (($code = trim((string) ($d['landmarkcode'] ?? ''))) !== '')
                    <div class="lm-editor-code-pill">
                        <span class="lm-editor-code-pill__label">Landmark code</span>
                        <span class="lm-editor-code-pill__value">{{ $code }}</span>
                        <span class="lm-editor-code-pill__note">Managed by Site Manager</span>
                    </div>
                @endif

                <div class="lm-editor-field">
                    <label for="cl-name-{{ $fieldHash }}">Landmark name</label>
                    <input class="lm-editor-input" type="text" id="cl-name-{{ $fieldHash }}" name="name"
                           autocomplete="organization" required
                           placeholder="Official or common name"
                           value="{{ $v('name') }}">
                </div>

                <div class="lm-editor-field">
                    <label for="cl-cat-{{ $fieldHash }}">Category</label>
                    <select class="lm-editor-select" id="cl-cat-{{ $fieldHash }}" name="category" required>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}" {{ (string) $v('category', 'Historical') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lm-editor-field lm-editor-field--last">
                    <label for="cl-desc-{{ $fieldHash }}">Description <span class="lm-editor-optional">optional</span></label>
                    <textarea class="lm-editor-textarea" id="cl-desc-{{ $fieldHash }}" name="description" rows="4"
                              placeholder="What visitors should know about this site">{{ $v('description') }}</textarea>
                </div>
            </div>
        </section>

        <section class="lm-editor-card">
            <header class="lm-editor-card__head">
                <h4 class="lm-editor-card__title">Media</h4>
            </header>
            <div class="lm-editor-card__body">
                <div class="lm-editor-field lm-editor-field--last">
                    <label for="cl-img-{{ $fieldHash }}">Hero image</label>
                    <div class="lm-editor-file-zone">
                        <input id="cl-img-{{ $fieldHash }}" type="file" name="image" accept="image/*">
                    </div>
                    @if ($imageSrc)
                        <div class="lm-editor-img-preview">
                            <img src="{{ $imageSrc }}" alt="Current landmark image">
                            <span class="lm-editor-img-preview__label">Current image</span>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </div>

    <div class="lm-editor-col">
        <section class="lm-editor-card lm-editor-card--location">
            <header class="lm-editor-card__head">
                <h4 class="lm-editor-card__title">Location</h4>
            </header>
            <div class="lm-editor-card__body">
                @if ($hasMap)
                    <div class="lm-editor-map-wrap">
                        <div id="{{ $mapId }}" class="lm-editor-map" role="application" aria-label="Landmark location map"></div>
                    </div>
                @else
                    <p class="lm-editor-map-fallback">
                        Add <strong>MAPBOX_TOKEN</strong> in <code>.env</code> for an interactive map, or enter coordinates manually.
                        @if ($coordLat !== '' && $coordLng !== '')
                            (<a href="https://www.openstreetmap.org/#map=16/{{ $coordLat }}/{{ $coordLng }}" rel="noopener noreferrer" target="_blank">Preview on OpenStreetMap</a>)
                        @endif
                    </p>
                @endif

                <div class="lm-editor-coords">
                    <div class="lm-editor-coord-display" id="lm-coord-display-{{ $modalSafe }}" aria-live="polite">
                        <span class="lm-editor-coord-display__item">
                            <span class="lm-editor-coord-display__k">Lat</span>
                            <span class="lm-editor-coord-display__v" data-coord="lat">{{ $coordLat !== '' ? $coordLat : '—' }}</span>
                        </span>
                        <span class="lm-editor-coord-display__item">
                            <span class="lm-editor-coord-display__k">Lng</span>
                            <span class="lm-editor-coord-display__v" data-coord="lng">{{ $coordLng !== '' ? $coordLng : '—' }}</span>
                        </span>
                    </div>

                    <div class="lm-editor-coord-fields">
                        <div class="lm-editor-field">
                            <label for="cl-lat-{{ $fieldHash }}">Latitude</label>
                            <input class="lm-editor-input lm-editor-input--mono" type="text" id="cl-lat-{{ $fieldHash }}"
                                   name="latitude" inputmode="decimal"
                                   placeholder="e.g. 10.3157" value="{{ $coordLat }}"
                                   data-lm-coord-input="lat">
                        </div>
                        <div class="lm-editor-field lm-editor-field--last">
                            <label for="cl-lng-{{ $fieldHash }}">Longitude</label>
                            <input class="lm-editor-input lm-editor-input--mono" type="text" id="cl-lng-{{ $fieldHash }}"
                                   name="longitude" inputmode="decimal"
                                   placeholder="e.g. 123.8854" value="{{ $coordLng }}"
                                   data-lm-coord-input="lng">
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

@if ($hasMap)
    @once
        <link href="https://api.mapbox.com/mapbox-gl-js/v3.3.0/mapbox-gl.css" rel="stylesheet"/>
        <script src="https://api.mapbox.com/mapbox-gl-js/v3.3.0/mapbox-gl.js"></script>
    @endonce
    <script>
    (function () {
        var mapId = @json($mapId);
        var modalSafe = @json($modalSafe);
        var mapboxToken = @json($mapboxToken);
        var modalId = 'editModal_' + modalSafe;

        function initLandmarkEditorMap() {
            if (window.__lmMapInited && window.__lmMapInited[mapId]) {
                if (window.__lmMapInstances && window.__lmMapInstances[mapId]) {
                    window.__lmMapInstances[mapId].resize();
                }
                return;
            }
            var container = document.getElementById(mapId);
            if (!container || typeof mapboxgl === 'undefined') {
                return;
            }

            window.__lmMapInited = window.__lmMapInited || {};
            window.__lmMapInstances = window.__lmMapInstances || {};
            if (window.__lmMapInited[mapId]) {
                return;
            }
            window.__lmMapInited[mapId] = true;

            mapboxgl.accessToken = mapboxToken;
            var latEl = document.getElementById('cl-lat-' + @json($fieldHash));
            var lngEl = document.getElementById('cl-lng-' + @json($fieldHash));
            var display = document.getElementById('lm-coord-display-' + modalSafe);
            var latDisplay = display ? display.querySelector('[data-coord="lat"]') : null;
            var lngDisplay = display ? display.querySelector('[data-coord="lng"]') : null;

            function parseCoords() {
                var la = latEl ? parseFloat(String(latEl.value).replace(',', '.')) : NaN;
                var ln = lngEl ? parseFloat(String(lngEl.value).replace(',', '.')) : NaN;
                if (!isFinite(la) || !isFinite(ln)) {
                    return null;
                }
                return { lat: la, lng: ln };
            }

            function formatCoord(n) {
                return Math.round(n * 1e6) / 1e6;
            }

            function syncDisplays(lng, lat) {
                var latStr = String(formatCoord(lat));
                var lngStr = String(formatCoord(lng));
                if (latEl) latEl.value = latStr;
                if (lngEl) lngEl.value = lngStr;
                if (latDisplay) latDisplay.textContent = latStr;
                if (lngDisplay) lngDisplay.textContent = lngStr;
            }

            var DEFAULT = { center: [123.8854, 10.3157], zoom: 12 };
            var map = new mapboxgl.Map({
                container: mapId,
                style: 'mapbox://styles/mapbox/streets-v12',
                center: DEFAULT.center,
                zoom: DEFAULT.zoom,
            });
            window.__lmMapInstances[mapId] = map;

            map.addControl(new mapboxgl.NavigationControl(), 'top-right');

            var marker = null;

            function setMarkerLngLat(lngLat) {
                if (!marker) {
                    marker = new mapboxgl.Marker({ draggable: true, color: '#7A2E1F' })
                        .setLngLat(lngLat)
                        .addTo(map);
                    marker.on('dragend', function () {
                        var p = marker.getLngLat();
                        syncDisplays(p.lng, p.lat);
                    });
                } else {
                    marker.setLngLat(lngLat);
                }
                syncDisplays(lngLat.lng, lngLat.lat);
            }

            map.on('click', function (e) {
                setMarkerLngLat(e.lngLat);
            });

            var initial = parseCoords();
            if (initial) {
                map.jumpTo({ center: [initial.lng, initial.lat], zoom: 14 });
                setMarkerLngLat(new mapboxgl.LngLat(initial.lng, initial.lat));
            }

            var inputTimer = null;
            function onCoordInputChange() {
                clearTimeout(inputTimer);
                inputTimer = setTimeout(function () {
                    var coords = parseCoords();
                    if (!coords) {
                        if (latDisplay) latDisplay.textContent = latEl && latEl.value ? latEl.value : '—';
                        if (lngDisplay) lngDisplay.textContent = lngEl && lngEl.value ? lngEl.value : '—';
                        return;
                    }
                    setMarkerLngLat(new mapboxgl.LngLat(coords.lng, coords.lat));
                    map.easeTo({ center: [coords.lng, coords.lat], zoom: Math.max(map.getZoom(), 14) });
                }, 400);
            }

            if (latEl) latEl.addEventListener('input', onCoordInputChange);
            if (lngEl) lngEl.addEventListener('input', onCoordInputChange);

            setTimeout(function () {
                map.resize();
            }, 120);
        }

        function tryInit() {
            var modal = document.getElementById(modalId);
            if (modal && modal.classList.contains('is-open')) {
                initLandmarkEditorMap();
                return;
            }
            if (!modal) {
                initLandmarkEditorMap();
            }
        }

        document.addEventListener('DOMContentLoaded', tryInit);

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.js-lm-edit-open');
            if (btn && btn.getAttribute('data-modal-id') === modalId) {
                setTimeout(initLandmarkEditorMap, 150);
            }
            if (e.target.closest('[onclick*="cuOpenModalCl(\'' + modalId + '\')"]')) {
                setTimeout(initLandmarkEditorMap, 150);
            }
        });

        var modalEl = document.getElementById(modalId);
        if (modalEl) {
            var observer = new MutationObserver(function () {
                if (modalEl.classList.contains('is-open')) {
                    setTimeout(initLandmarkEditorMap, 150);
                }
            });
            observer.observe(modalEl, { attributes: true, attributeFilter: ['class'] });
        } else {
            tryInit();
        }
    })();
    </script>
@endif
