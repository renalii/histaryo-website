@extends('layouts.sidebar')

@section('content')
<style>
    html, body { height: 100%; margin: 0; overflow: hidden; }
    #map {
        position: relative; height: calc(100vh - 100px); width: 100%;
        border-radius: 1rem; overflow: hidden; background: #e5e7eb;
    }
    .no-landmarks {
        position: absolute; top: 1rem; left: 1rem;
        background-color: #fef2f2; color: #b91c1c; padding: 1rem; border-radius: 0.5rem;
        z-index: 1000; box-shadow: 0 4px 12px rgba(0,0,0,.1);
    }
    .mapboxgl-popup { max-width: 300px; font: 14px/1.4 system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial; }
    .mapboxgl-popup-content a { text-decoration: underline; }

    
    .landmark-search {
        position: absolute; top: 10px; left: 10px; z-index: 1001;
        display: flex; gap: .5rem; align-items: center;
        background: #fff; padding: .5rem; border-radius: .5rem; box-shadow: 0 4px 12px rgba(0,0,0,.1);
    }
    .landmark-search input {
        width: 320px; padding: .4rem .6rem; border: 1px solid #e5e7eb; border-radius: .375rem;
        font-size: 14px; transition: border-color .15s ease;
    }
    .landmark-search input.ring {
        border-color: #ef4444 !important; 
    }
    .landmark-search button {
        padding: .4rem .6rem; border: 1px solid #e5e7eb; border-radius: .375rem; background: #f9fafb; cursor: pointer;
    }
</style>

{{-- Mapbox CSS --}}
<link href="https://api.mapbox.com/mapbox-gl-js/v3.3.0/mapbox-gl.css" rel="stylesheet" />

<div id="map">
    {{-- Private landmark search overlay --}}
    <div class="landmark-search">
        <input id="landmarkSearch"
               type="text"
               placeholder="Search landmarks… e.g., Magellan’s Cross"
               autocomplete="off" />
        <button id="landmarkGo">Go</button>
    </div>
</div>

@if(count($landmarks) === 0)
    <div class="no-landmarks">No landmarks found with valid coordinates. Please add some!</div>
@endif

{{-- Mapbox JS --}}
<script src="https://api.mapbox.com/mapbox-gl-js/v3.3.0/mapbox-gl.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    mapboxgl.accessToken = @json($mapboxToken);
    if (!mapboxgl.accessToken) { alert('Missing MAPBOX_TOKEN in .env. Run php artisan config:clear after setting it.'); return; }

    const DEFAULT_CENTER = [123.8854, 10.3157]; 
    const DEFAULT_ZOOM = 12;

    const map = new mapboxgl.Map({
        container: 'map',
        style: 'mapbox://styles/mapbox/streets-v12',
        center: DEFAULT_CENTER,
        zoom: DEFAULT_ZOOM
    });

    map.addControl(new mapboxgl.NavigationControl(), 'top-right');
    map.addControl(new mapboxgl.FullscreenControl(), 'top-right');

    const landmarks = @json(array_values($landmarks));

    
    const markersById = new Map();
    const bounds = new mapboxgl.LngLatBounds();

    landmarks.forEach(l => {
        if (typeof l.longitude !== 'number' || typeof l.latitude !== 'number') return;

        const popupHtml = `
            <div>
                <strong>${escapeHtml(l.name ?? 'Untitled')}</strong><br>
                ${l.description ? escapeHtml(l.description) + '<br>' : ''}
                ${l.video_url ? `<a href="${escapeAttr(l.video_url)}" target="_blank" rel="noopener">🎥 Watch Video</a><br>` : ''}
                
            </div>
        `;

    

        const marker = new mapboxgl.Marker()
            .setLngLat([l.longitude, l.latitude])
            .setPopup(new mapboxgl.Popup({ offset: 25 }).setHTML(popupHtml))
            .addTo(map);

        markersById.set(l.id, { marker, data: l });
        bounds.extend([l.longitude, l.latitude]);
    });

    if (!bounds.isEmpty()) map.fitBounds(bounds, { padding: 60, maxZoom: 15 });

    
    const focusId = @json($focusId ?? null);
    if (focusId) {
        const lm = landmarks.find(x => x.id === focusId);
        if (lm && typeof lm.longitude === 'number' && typeof lm.latitude === 'number') {
            map.flyTo({ center: [lm.longitude, lm.latitude], zoom: 16 });
            const ref = markersById.get(lm.id);
            if (ref && ref.marker) ref.marker.togglePopup();
        }
    }

    
    const input = document.getElementById('landmarkSearch');
    const btnGo = document.getElementById('landmarkGo');

    
    function nudgeNotFound() {
        input.classList.add('ring');
        input.style.borderColor = '#ef4444';
        setTimeout(() => {
            input.classList.remove('ring');
            input.style.borderColor = '#e5e7eb';
        }, 600);
    }

    
    function tryCoords(q) {
        if (!q) return null;
        const m = q.trim().match(/^(-?\d+(\.\d+)?)[,\s]+(-?\d+(\.\d+)?)$/);
        if (!m) return null;
        
        const lat = parseFloat(m[1]);
        const lng = parseFloat(m[3]);
        if (isFinite(lat) && isFinite(lng)) return { latitude: lat, longitude: lng, id: '__coords__' };
        return null;
    }

    function findLandmarkByName(q) {
        if (!q) return null;
        const needle = q.trim().toLowerCase();
        
        let hit = landmarks.find(l => (l.name || '').toLowerCase() === needle);
        if (!hit) hit = landmarks.find(l => (l.name || '').toLowerCase().includes(needle));
        return hit || null;
    }

    function focusLandmark(lm) {
        if (!lm) return;
        const { longitude: lng, latitude: lat } = lm;
        if (typeof lng !== 'number' || typeof lat !== 'number') return;
        map.flyTo({ center: [lng, lat], zoom: 16 });
        if (lm.id !== '__coords__') {
            const ref = markersById.get(lm.id);
            if (ref && ref.marker) ref.marker.togglePopup();
        }
    }

    
    btnGo.addEventListener('click', () => {
        const lm = tryCoords(input.value) || findLandmarkByName(input.value);
        if (lm) { focusLandmark(lm); } else { nudgeNotFound(); }
    });
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            const lm = tryCoords(input.value) || findLandmarkByName(input.value);
            if (lm) { focusLandmark(lm); } else { nudgeNotFound(); }
        }
    });

    
    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
    function escapeAttr(str) {
        return escapeHtml(str).replace(/"/g, '&quot;');
    }
});
</script>
@endsection
