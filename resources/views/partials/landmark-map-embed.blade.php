@php
    $latRaw = $latitude ?? null;
    $lngRaw = $longitude ?? null;
    $lat = ($latRaw !== null && $latRaw !== '' && is_numeric($latRaw)) ? (float) $latRaw : null;
    $lng = ($lngRaw !== null && $lngRaw !== '' && is_numeric($lngRaw)) ? (float) $lngRaw : null;
    $showMap = $lat !== null && $lng !== null;
    $mapContainerId = $mapContainerId ?? ('lm-map-' . substr(md5((string) $lat . ',' . (string) $lng . ($landmarkName ?? '')), 0, 10));
    $mapboxEmbedToken = trim((string) ($mapboxToken ?? config('services.mapbox.token')));
    $useMapbox = $showMap && $mapboxEmbedToken !== '';
    $useLeaflet = $showMap && ! $useMapbox;
    $mapTitle = trim((string) ($landmarkName ?? 'Landmark'));
@endphp

@if ($showMap)
    @once
        <style>
            .lm-map-embed {
                border-radius: 12px;
                overflow: hidden;
                border: 1px solid #dbeafe;
                background: #e8edf2;
            }
            .lm-map-embed__canvas {
                width: 100%;
                height: 240px;
            }
            @media (min-width: 640px) {
                .lm-map-embed__canvas { height: 280px; }
            }
            .lm-map-embed .mapboxgl-ctrl-attrib-inner { font-size: 10px; }
        </style>
        <script>
        window.lmInitMapWhenVisible = window.lmInitMapWhenVisible || function (containerId, initFn) {
            function hostVisible(host) {
                if (!host) return true;
                return host.classList.contains('is-open') || host.style.display === 'flex';
            }
            function run() {
                var el = document.getElementById(containerId);
                if (!el) return;
                var host = el.closest('.lm-view-modal, .modal-cl');
                if (host && !hostVisible(host)) {
                    var observer = new MutationObserver(function () {
                        if (hostVisible(host)) {
                            setTimeout(initFn, 120);
                            observer.disconnect();
                        }
                    });
                    observer.observe(host, { attributes: true, attributeFilter: ['style', 'class'] });
                    return;
                }
                initFn();
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', run);
            } else {
                run();
            }
        };
        </script>
    @endonce

    <div class="lm-map-embed">
        <div id="{{ $mapContainerId }}" class="lm-map-embed__canvas" role="img" aria-label="{{ $mapTitle }} location map"></div>
    </div>

    @if ($useMapbox)
        @once
            <link href="https://api.mapbox.com/mapbox-gl-js/v3.3.0/mapbox-gl.css" rel="stylesheet"/>
            <script src="https://api.mapbox.com/mapbox-gl-js/v3.3.0/mapbox-gl.js"></script>
        @endonce
        <script>
        (function () {
            var containerId = @json($mapContainerId);
            var lat = @json($lat);
            var lng = @json($lng);
            function initMap() {
                var el = document.getElementById(containerId);
                if (!el || typeof mapboxgl === 'undefined') return;
                if (el.dataset.lmMapReady === '1') {
                    if (el._lmMap && typeof el._lmMap.resize === 'function') {
                        el._lmMap.resize();
                    }
                    return;
                }
                el.dataset.lmMapReady = '1';
                mapboxgl.accessToken = @json($mapboxEmbedToken);
                var map = new mapboxgl.Map({
                    container: containerId,
                    style: 'mapbox://styles/mapbox/streets-v12',
                    center: [lng, lat],
                    zoom: 14,
                    interactive: true,
                });
                el._lmMap = map;
                map.addControl(new mapboxgl.NavigationControl(), 'top-right');
                new mapboxgl.Marker({ color: '#7A2E1F' }).setLngLat([lng, lat]).addTo(map);
                setTimeout(function () { map.resize(); }, 80);
            }
            window.lmInitMapWhenVisible(containerId, initMap);
        })();
        </script>
    @elseif ($useLeaflet)
        @once
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        @endonce
        <script>
        (function () {
            var containerId = @json($mapContainerId);
            var lat = @json($lat);
            var lng = @json($lng);
            function initMap() {
                var el = document.getElementById(containerId);
                if (!el || el.dataset.lmMapReady === '1' || typeof L === 'undefined') return;
                el.dataset.lmMapReady = '1';
                var map = L.map(containerId, { scrollWheelZoom: true }).setView([lat, lng], 14);
                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap',
                }).addTo(map);
                L.marker([lat, lng]).addTo(map);
                setTimeout(function () { map.invalidateSize(); }, 80);
            }
            window.lmInitMapWhenVisible(containerId, initMap);
        })();
        </script>
    @endif
@endif
