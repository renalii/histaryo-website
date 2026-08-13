@extends('layouts.sidebar')

@section('content')
<style>
    html, body { height: 100%; overflow: hidden; }
    .sm-map-page {
        height: calc(100vh - 5rem);
        min-height: 560px;
        display: flex;
        flex-direction: column;
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
        max-width: calc(100% - 2rem);
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
    .sm-map-search select {
        height: 2rem;
        min-width: 132px;
        padding: 0 1.8rem 0 .65rem;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        background: #fff;
        color: #111827;
        font: 500 .78rem Inter, system-ui, sans-serif;
        outline: none;
    }
    .sm-map-search select:focus {
        border-color: #7A2E1F;
        box-shadow: 0 0 0 2px rgba(122, 46, 31, .12);
    }
    .map-custom-select { position:relative; display:block; width:150px; height:2rem; }
    .map-custom-select__native { position:absolute; inset:0; opacity:0; pointer-events:none; }
    .map-custom-select__field {
        box-sizing:border-box; width:100%; height:2rem; padding:0 2rem 0 .65rem;
        border:1px solid #d1d5db; border-radius:6px; background:#fff; color:#111827;
        font:500 .78rem Inter,system-ui,sans-serif; text-align:left; cursor:pointer;
    }
    .sm-map-search .custom-select-arrow {
        position:absolute; z-index:1; top:1px; right:1px; width:2.2rem; height:calc(2rem - 2px);
        padding:0; border:0; border-radius:0 5px 5px 0; background:transparent;
        color:#374151; cursor:pointer;
    }
    .sm-map-search .custom-select-arrow::after {
        content:''; position:absolute; top:50%; left:50%; width:.4rem; height:.4rem;
        border-right:2px solid currentColor; border-bottom:2px solid currentColor;
        transform:translate(-50%,-70%) rotate(45deg);
    }
    .sm-map-search .map-custom-select.is-open .custom-select-arrow::after { transform:translate(-50%,-30%) rotate(225deg); }
    .map-custom-select__menu {
        position:fixed; z-index:9999; box-sizing:border-box; max-height:322px; margin:0;
        padding:0; overflow-x:hidden; overflow-y:auto; border:1px solid #d1d5db;
        border-radius:6px; background:#fff; box-shadow:0 8px 20px rgba(0,0,0,.15);
        list-style:none;
    }
    .map-custom-select__menu[hidden] { display:none; }
    .map-custom-select__option {
        display:block; width:100%; height:40px; padding:0 .65rem; border:0;
        background:transparent; color:#111827; font:500 .78rem Inter,system-ui,sans-serif;
        line-height:40px; text-align:left; white-space:nowrap; overflow:hidden;
        text-overflow:ellipsis; cursor:pointer;
    }
    .map-custom-select__option:hover,
    .map-custom-select__option:focus,
    .map-custom-select__option[aria-selected="true"] { background:#f3f4f6; outline:none; }
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
    .sm-map-search input:not(.is-missing):focus,
    .sm-map-search input:not(.is-missing):focus-visible,
    .sm-map-search select:focus,
    .sm-map-search select:focus-visible,
    .sm-map-search button:focus,
    .sm-map-search button:focus-visible {
        outline: none !important;
        box-shadow: none !important;
        border-color: #d1d5db !important;
    }
    .sm-map-search input.is-missing:focus,
    .sm-map-search input.is-missing:focus-visible {
        outline: none !important;
        box-shadow: none !important;
    }
    @media (max-width: 760px) {
        .sm-map-search {
            right: 1rem;
            flex-wrap: wrap;
        }
        .sm-map-search input {
            flex: 1 1 100%;
            width: 100%;
        }
        .sm-map-search select {
            flex: 1 1 130px;
            min-width: 0;
        }
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
    .sm-map-user-location {
        position: relative;
        width: 32px;
        height: 32px;
        padding: 0;
        border: 0;
        background: transparent;
        cursor: pointer;
        overflow: visible;
    }
    .sm-map-user-location__pulse {
        position: absolute;
        inset: 4px;
        border: 2px solid rgba(229, 182, 74, .7);
        border-radius: 50%;
        pointer-events: none;
        animation: sm-map-user-location-pulse 1.8s ease-out infinite;
    }
    .sm-map-user-location__icon {
        position: absolute;
        inset: 3px;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        box-sizing: border-box;
        border: 3px solid #fff;
        border-radius: 50%;
        background: #e5b64a;
        color: #fff;
        box-shadow: 0 0 0 3px rgba(229, 182, 74, .2), 0 3px 10px rgba(122, 46, 31, .28);
        pointer-events: none;
    }
    .sm-map-user-location__icon svg {
        width: 14px;
        height: 14px;
        display: block;
        fill: currentColor;
    }
    @keyframes sm-map-user-location-pulse {
        0% {
            opacity: .85;
            transform: scale(.85);
        }
        75%, 100% {
            opacity: 0;
            transform: scale(1.8);
        }
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
    <div class="sm-map-shell">
        <div id="sm-map" aria-label="Managed landmarks map"></div>
        <form class="sm-map-search" id="sm-map-search" autocomplete="off">
            <input
                id="sm-map-search-input"
                type="text"
                placeholder="Search landmarks... e.g., Magellan's Cross"
                aria-label="Search landmarks">
            @isset($mapCategories, $mapCities)
                <span class="map-custom-select">
                    <select class="map-custom-select__native" id="sm-map-category-filter" aria-label="Filter landmarks by category">
                        <option value="">All Categories</option>
                        @foreach ($mapCategories as $category)
                            <option value="{{ $category }}">{{ $category }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="map-custom-select__field" aria-haspopup="listbox" aria-expanded="false"></button>
                    <button type="button" class="custom-select-arrow" aria-label="Toggle options" aria-expanded="false"></button>
                    <ul class="map-custom-select__menu" role="listbox" hidden></ul>
                </span>
                <span class="map-custom-select">
                    <select class="map-custom-select__native" id="sm-map-city-filter" aria-label="Filter landmarks by city">
                        <option value="">All Cities</option>
                        @foreach ($mapCities as $city)
                            <option value="{{ $city }}">{{ $city }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="map-custom-select__field" aria-haspopup="listbox" aria-expanded="false"></button>
                    <button type="button" class="custom-select-arrow" aria-label="Toggle options" aria-expanded="false"></button>
                    <ul class="map-custom-select__menu" role="listbox" hidden></ul>
                </span>
            @endisset
            <button type="submit">Go</button>
        </form>
        @if (count($landmarks) === 0)
            <div class="sm-map-empty">{{ $mapEmptyMessage ?? 'No managed landmarks with coordinates found.' }}</div>
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
    var userLocationEnabled = @json((bool) ($enableUserLocation ?? false));
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

        markersById[landmark.id || landmark.name] = { marker: marker, popup: popup, data: landmark, isVisible: true };
        bounds.extend([landmark.longitude, landmark.latitude]);
    });

    if (!bounds.isEmpty()) {
        map.fitBounds(bounds, { padding: 70, maxZoom: 15 });
    }

    if (userLocationEnabled && navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function (position) {
            var userCoordinates = [position.coords.longitude, position.coords.latitude];
            var userMarkerElement = document.createElement('button');
            userMarkerElement.type = 'button';
            userMarkerElement.className = 'sm-map-user-location';
            userMarkerElement.setAttribute('aria-label', 'Your current location');
            userMarkerElement.innerHTML = ''
                + '<span class="sm-map-user-location__pulse" aria-hidden="true"></span>'
                + '<span class="sm-map-user-location__icon" aria-hidden="true">'
                + '<svg viewBox="0 0 24 24" focusable="false"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 2c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Z"/></svg>'
                + '</span>';

            new mapboxgl.Marker({ element: userMarkerElement })
                .setLngLat(userCoordinates)
                .addTo(map);

            map.flyTo({
                center: userCoordinates,
                zoom: 14,
                essential: true
            });
        }, function () {
            // Permission denial or lookup failure keeps the existing map view.
        }, {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 60000
        });
    }

    var searchForm = document.getElementById('sm-map-search');
    var searchInput = document.getElementById('sm-map-search-input');
    var categoryFilter = document.getElementById('sm-map-category-filter');
    var cityFilter = document.getElementById('sm-map-city-filter');
    var mapDropdowns = [];

    function closeMapDropdowns(exceptMenu) {
        mapDropdowns.forEach(function (dropdown) {
            if (dropdown.menu !== exceptMenu) dropdown.close();
        });
    }

    document.querySelectorAll('.map-custom-select').forEach(function (root) {
        var select = root.querySelector('.map-custom-select__native');
        var field = root.querySelector('.map-custom-select__field');
        var arrow = root.querySelector('.custom-select-arrow');
        var menu = root.querySelector('.map-custom-select__menu');
        var isOpen = false;
        if (!select || !field || !arrow || !menu) return;

        function sync() {
            var selected = select.options[select.selectedIndex] || select.options[0];
            field.textContent = selected ? selected.textContent : '';
            menu.querySelectorAll('[role="option"]').forEach(function (option) {
                option.setAttribute('aria-selected', option.dataset.value === select.value ? 'true' : 'false');
            });
        }
        function closeDropdown() {
            isOpen = false;
            menu.hidden = true;
            root.classList.remove('is-open');
            field.setAttribute('aria-expanded', 'false');
            arrow.setAttribute('aria-expanded', 'false');
        }
        function openDropdown() {
            closeMapDropdowns(menu);
            isOpen = true;
            var rect = field.getBoundingClientRect();
            var gap = 4;
            var padding = 8;
            var maxHeight = 322;
            var below = Math.max(0, window.innerHeight - rect.bottom - gap - padding);
            var above = Math.max(0, rect.top - gap - padding);
            menu.style.width = rect.width + 'px';
            menu.style.maxHeight = maxHeight + 'px';
            menu.hidden = false;
            var desired = Math.min(menu.scrollHeight + 2, maxHeight);
            var opensUp = below < desired && above > below;
            menu.style.maxHeight = Math.min(maxHeight, opensUp ? above : below) + 'px';
            var height = menu.getBoundingClientRect().height;
            menu.style.top = (opensUp ? rect.top - gap - height : rect.bottom + gap) + 'px';
            menu.style.left = rect.left + 'px';
            root.classList.add('is-open');
            field.setAttribute('aria-expanded', 'true');
            arrow.setAttribute('aria-expanded', 'true');
        }
        Array.from(select.options).forEach(function (nativeOption) {
            var item = document.createElement('li');
            var option = document.createElement('button');
            option.type = 'button';
            option.className = 'map-custom-select__option';
            option.setAttribute('role', 'option');
            option.dataset.value = nativeOption.value;
            option.textContent = nativeOption.textContent;
            option.addEventListener('click', function () {
                select.value = nativeOption.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                sync();
                closeDropdown();
                field.focus();
            });
            item.appendChild(option);
            menu.appendChild(item);
        });
        document.body.appendChild(menu);
        mapDropdowns.push({ menu: menu, close: closeDropdown });
        sync();

        field.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            if (!isOpen) openDropdown();
        });
        arrow.addEventListener('mousedown', function (event) {
            event.preventDefault();
            event.stopPropagation();
        });
        arrow.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            if (isOpen) {
                closeDropdown();
            } else {
                openDropdown();
                field.focus({ preventScroll: true });
            }
        });
        menu.addEventListener('click', function (event) { event.stopPropagation(); });
    });

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.map-custom-select') && !event.target.closest('.map-custom-select__menu')) {
            closeMapDropdowns();
        }
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeMapDropdowns();
    });
    window.addEventListener('resize', function () { closeMapDropdowns(); });
    window.addEventListener('scroll', function (event) {
        if (!event.target.closest || !event.target.closest('.map-custom-select__menu')) closeMapDropdowns();
    }, true);

    searchForm.addEventListener('submit', function (event) {
        event.preventDefault();
        var query = normalizeSearch(searchInput.value);
        var filtersChangedMarkers = categoryFilter && cityFilter;

        if (filtersChangedMarkers) {
            applyFilters();
        }

        var match = findLandmark(query, filtersChangedMarkers);

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

    function applyFilters() {
        var query = normalizeSearch(searchInput.value);
        var category = normalizeSearch(categoryFilter.value);
        var city = normalizeSearch(cityFilter.value);
        var filteredBounds = new mapboxgl.LngLatBounds();

        if (activePopup) {
            activePopup.remove();
            activePopup = null;
        }

        Object.keys(markersById).forEach(function (key) {
            var item = markersById[key];
            var matchesSearch = !query || normalizeSearch(item.data.name).indexOf(query) !== -1;
            var matchesCategory = !category || normalizeSearch(item.data.category) === category;
            var matchesCity = !city || normalizeSearch(item.data.city) === city;
            var shouldShow = matchesSearch && matchesCategory && matchesCity;

            if (shouldShow && !item.isVisible) {
                item.marker.addTo(map);
            } else if (!shouldShow && item.isVisible) {
                item.marker.remove();
            }

            item.isVisible = shouldShow;
            if (shouldShow) {
                filteredBounds.extend([item.data.longitude, item.data.latitude]);
            }
        });

        searchInput.classList.toggle('is-missing', query !== '' && filteredBounds.isEmpty());
        if (!filteredBounds.isEmpty() && query === '') {
            map.fitBounds(filteredBounds, { padding: 70, maxZoom: 15 });
        }
    }

    function findLandmark(query, onlyVisible) {
        if (!query) {
            return null;
        }

        var exact = null;
        var partial = null;
        Object.keys(markersById).some(function (key) {
            var item = markersById[key];
            if (onlyVisible && !item.isVisible) {
                return false;
            }
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
