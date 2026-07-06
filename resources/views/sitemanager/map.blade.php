@extends('layouts.sidebar')

@section('content')
<style>
    html, body { height: 100%; overflow: hidden; }
    .sm-map-page {
        height: calc(100vh - 5rem);
        min-height: 560px;
        display: flex;
        flex-direction: column;
        gap: .85rem;
    }
    .sm-map-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .sm-map-title {
        margin: 0;
        color: #7A2E1F;
        font-size: 1.9rem;
        font-weight: 800;
    }
    .sm-map-shell {
        position: relative;
        flex: 1;
        min-height: 0;
        overflow: hidden;
        border-radius: 12px;
        border: 1px solid rgba(122, 46, 31, .08);
        box-shadow: 0 12px 32px rgba(15, 23, 42, .08);
        background: #e5e7eb;
    }
    #sm-map {
        width: 100%;
        height: 100%;
    }
    .sm-map-search {
        position: absolute;
        top: 1rem;
        left: 1rem;
        z-index: 3;
        display: flex;
        align-items: center;
        gap: .45rem;
        max-width: min(440px, calc(100% - 2rem));
        padding: .45rem;
        border-radius: 8px;
        background: rgba(255, 255, 255, .94);
        border: 1px solid rgba(122, 46, 31, .10);
        box-shadow: 0 8px 22px rgba(15, 23, 42, .14);
    }
    .sm-map-search input {
        width: min(320px, 58vw);
        height: 2rem;
        padding: 0 .65rem;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        color: #111827;
        font: 500 .78rem Inter, system-ui, sans-serif;
        outline: none;
    }
    .sm-map-search input:focus {
        border-color: #7A2E1F;
        box-shadow: 0 0 0 2px rgba(122, 46, 31, .12);
    }
    .sm-map-search input.is-missing {
        border-color: #dc2626;
        box-shadow: 0 0 0 2px rgba(220, 38, 38, .14);
    }
    .sm-map-search button {
        height: 2rem;
        padding: 0 .75rem;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        background: #fff;
        color: #111827;
        font: 700 .78rem Inter, system-ui, sans-serif;
        cursor: pointer;
    }
    .sm-map-search button:hover {
        background: #f9fafb;
    }
    .sm-map-empty,
    .sm-map-error {
        position: absolute;
        top: 4rem;
        left: 1rem;
        z-index: 2;
        max-width: min(420px, calc(100% - 2rem));
        padding: .75rem 1rem;
        border-radius: 8px;
        background: #fff;
        color: #7A2E1F;
        border: 1px solid #f3d5bf;
        box-shadow: 0 6px 18px rgba(15, 23, 42, .12);
        font-weight: 700;
        font-size: .9rem;
    }
    .sm-map-marker {
        width: 50px;
        height: 50px;
        border-radius: 999px;
        border: 3px solid #fff;
        background: #7A2E1F;
        box-shadow: 0 6px 18px rgba(15, 23, 42, .28);
        cursor: pointer;
        overflow: hidden;
        padding: 0;
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .sm-map-marker:hover {
        transform: scale(1.08);
        box-shadow: 0 8px 22px rgba(15, 23, 42, .34);
    }
    .sm-map-marker img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        pointer-events: none;
    }
    .mapboxgl-popup-content {
        border-radius: 4px;
        padding: .5rem;
        color: #111827;
        font-family: Inter, system-ui, sans-serif;
        width: 250px;
        text-align: left;
    }
    .sm-map-popup {
        display: flex;
        flex-direction: column;
        gap: .35rem;
    }
    .sm-map-popup__thumb,
    .sm-map-popup__thumb-fallback {
        width: 100%;
        height: 105px;
        border-radius: 3px;
        flex: 0 0 auto;
    }
    .sm-map-popup__thumb {
        object-fit: cover;
        display: block;
        background: #f3f4f6;
    }
    .sm-map-popup__thumb-fallback {
        display: flex;
        align-items: center;
        justify-content: center;
        background: #7A2E1F;
        color: #fff7ed;
        font-weight: 800;
        font-size: 2rem;
    }
    .sm-map-popup__name {
        margin: .25rem 0 0;
        color: #111827;
        font-weight: 800;
        font-size: .9rem;
        line-height: 1.25;
    }
    .sm-map-popup__description {
        margin: 0;
        color: #374151;
        font-size: .78rem;
        line-height: 1.3;
        max-height: 190px;
        overflow-y: auto;
        white-space: pre-wrap;
    }
</style>

<link href="https://api.mapbox.com/mapbox-gl-js/v3.3.0/mapbox-gl.css" rel="stylesheet">

<div class="sm-map-page">
    <div class="sm-map-header">
        <div>
            <h1 class="sm-map-title">Map</h1>
        </div>
    </div>

    <div class="sm-map-shell">
        <div id="sm-map" aria-label="Managed landmarks map"></div>
        <form class="sm-map-search" id="sm-map-search" autocomplete="off">
            <input
                id="sm-map-search-input"
                type="text"
                placeholder="Search landmarks... e.g., Magellan's Cross"
                aria-label="Search landmarks">
            <button type="submit">Go</button>
        </form>
        @if (count($landmarks) === 0)
            <div class="sm-map-empty">No managed landmarks with coordinates found.</div>
        @endif
        @if (empty($mapboxToken))
            <div class="sm-map-error">Missing MAPBOX_TOKEN in the environment.</div>
        @endif
    </div>
</div>

<script src="https://api.mapbox.com/mapbox-gl-js/v3.3.0/mapbox-gl.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var token = @json($mapboxToken);
    var landmarks = @json(array_values($landmarks));
    var defaultCenter = [123.8854, 10.3157];

    if (!token) {
        return;
    }

    mapboxgl.accessToken = token;
    var map = new mapboxgl.Map({
        container: 'sm-map',
        style: 'mapbox://styles/mapbox/streets-v12',
        center: defaultCenter,
        zoom: 11
    });

    map.addControl(new mapboxgl.NavigationControl(), 'top-right');
    map.addControl(new mapboxgl.FullscreenControl(), 'top-right');

    var bounds = new mapboxgl.LngLatBounds();
    var activePopup = null;
    var markersById = {};

    landmarks.forEach(function (landmark) {
        if (typeof landmark.longitude !== 'number' || typeof landmark.latitude !== 'number') {
            return;
        }

        var el = document.createElement('button');
        el.type = 'button';
        el.className = 'sm-map-marker';
        el.setAttribute('aria-label', landmark.name || 'Landmark marker');

        if (landmark.imageSrc) {
            var markerImage = document.createElement('img');
            markerImage.src = landmark.imageSrc;
            markerImage.alt = '';
            markerImage.decoding = 'async';
            el.appendChild(markerImage);
        }

        var thumbnail = landmark.imageSrc
            ? '<img class="sm-map-popup__thumb" src="' + escapeAttr(landmark.imageSrc) + '" alt="">'
            : '<div class="sm-map-popup__thumb-fallback" aria-hidden="true">' + escapeHtml(markerInitial(landmark.name)) + '</div>';
        var description = landmark.description
            ? escapeHtml(landmark.description)
            : 'No description available.';
        var html = ''
            + '<div class="sm-map-popup">'
            + thumbnail
            + '<p class="sm-map-popup__name">' + escapeHtml(landmark.name || 'Untitled') + '</p>'
            + '<p class="sm-map-popup__description">' + description + '</p>'
            + '</div>';
        var popup = new mapboxgl.Popup({ closeButton: false, offset: 24, maxWidth: '270px' }).setHTML(html);

        popup.on('open', function () {
            if (activePopup && activePopup !== popup) {
                activePopup.remove();
            }
            activePopup = popup;
        });

        var marker = new mapboxgl.Marker({ element: el, anchor: 'bottom' })
            .setLngLat([landmark.longitude, landmark.latitude])
            .setPopup(popup)
            .addTo(map);

        markersById[landmark.id || landmark.name] = { marker: marker, popup: popup, data: landmark };
        bounds.extend([landmark.longitude, landmark.latitude]);
    });

    if (!bounds.isEmpty()) {
        map.fitBounds(bounds, { padding: 70, maxZoom: 15 });
    }

    var searchForm = document.getElementById('sm-map-search');
    var searchInput = document.getElementById('sm-map-search-input');

    searchForm.addEventListener('submit', function (event) {
        event.preventDefault();
        var query = normalizeSearch(searchInput.value);
        var match = findLandmark(query);

        searchInput.classList.toggle('is-missing', !match && query !== '');

        if (!match) {
            return;
        }

        if (activePopup) {
            activePopup.remove();
        }

        map.flyTo({
            center: [match.data.longitude, match.data.latitude],
            zoom: Math.max(map.getZoom(), 16),
            essential: true
        });
        match.marker.togglePopup();
    });

    searchInput.addEventListener('input', function () {
        searchInput.classList.remove('is-missing');
    });

    function findLandmark(query) {
        if (!query) {
            return null;
        }

        var exact = null;
        var partial = null;
        Object.keys(markersById).some(function (key) {
            var item = markersById[key];
            var name = normalizeSearch(item.data.name);

            if (name === query) {
                exact = item;
                return true;
            }

            if (!partial && name.indexOf(query) !== -1) {
                partial = item;
            }

            return false;
        });

        return exact || partial;
    }

    function normalizeSearch(value) {
        return String(value || '').trim().toLowerCase();
    }

    function markerInitial(value) {
        var text = String(value || '').trim();
        return text.length ? text.charAt(0).toUpperCase() : '?';
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function escapeAttr(value) {
        return escapeHtml(value).replace(/`/g, '&#096;');
    }
});
</script>
@endsection
