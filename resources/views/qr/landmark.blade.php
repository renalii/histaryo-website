@php
    /** @var array $payload */
    /** @var array $landmark */
    /** @var string $videoEmbedUrl */
    /** @var string|null $mapUrl */
    /** @var string|null $mapboxToken */
    $lm = $payload['landmark'] ?? [];
    $name = $lm['name'] ?? 'Landmark';
    $description = trim((string) ($lm['description'] ?? ''));
    $category = trim((string) ($lm['category'] ?? ''));
    $videoUrl = trim((string) ($lm['video_url'] ?? ''));
    $locationLabel = trim((string) ($landmark['location'] ?? ''));
    $latRaw = $landmark['latitude'] ?? $landmark['lati'] ?? null;
    $lngRaw = $landmark['longitude'] ?? $landmark['longti'] ?? null;
    $lat = isset($latRaw) && is_numeric($latRaw) ? (float) $latRaw : null;
    $lng = isset($lngRaw) && is_numeric($lngRaw) ? (float) $lngRaw : null;
    $showMapEmbed = $lat !== null && $lng !== null;
    $mapboxEmbedToken = isset($mapboxToken) ? trim((string) $mapboxToken) : '';
    $useLeafletFallback = $showMapEmbed && $mapboxEmbedToken === '';
    $imageSrc = null;
    if (!empty($landmark['image_base64'])) {
        $imageMime = $landmark['image_mime'] ?? 'image/jpeg';
        $imageSrc = 'data:' . $imageMime . ';base64,' . $landmark['image_base64'];
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $name }} — Histaryo</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #e8e0ef;
            color: #1a1a1a;
            min-height: 100vh;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 28px;
            border-bottom: 1px solid rgba(110, 75, 58, 0.12);
        }
        .logo {
            font-size: 20px;
            font-weight: 700;
            color: #6e4b3a;
        }
        nav a {
            text-decoration: none;
            color: #a8744f;
            margin-left: 20px;
            font-weight: 500;
        }
        nav a:hover { color: #6e4b3a; }
        main {
            max-width: 640px;
            margin: 0 auto;
            padding: 28px 22px 48px;
        }
        .card {
            background: #fff;
            border-radius: 18px;
            padding: 28px 26px 30px;
            box-shadow: 0 14px 36px rgba(40, 22, 53, 0.12);
        }
        .tag {
            display: inline-block;
            background: #6e4b3a;
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 999px;
            margin-bottom: 14px;
        }
        h1 {
            margin: 0 0 12px;
            font-size: clamp(26px, 5vw, 34px);
            line-height: 1.15;
            color: #2a1810;
        }
        .lede {
            margin: 0 0 20px;
            font-size: 16px;
            line-height: 1.65;
            color: #3d3d3d;
        }
        .meta {
            margin: 0 0 10px;
            font-size: 15px;
            line-height: 1.55;
            color: #333;
        }
        .meta a {
            color: #6e4b3a;
            font-weight: 600;
        }
        .photo {
            margin: 22px 0 0;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid rgba(110, 75, 58, 0.15);
        }
        .photo img {
            display: block;
            width: 100%;
            max-height: 320px;
            object-fit: cover;
        }
        .video-block {
            margin-top: 26px;
        }
        .video-block h2 {
            margin: 0 0 12px;
            font-size: 17px;
            color: #2a1810;
        }
        .video-wrap {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            border-radius: 12px;
            overflow: hidden;
            background: #111;
        }
        .video-wrap iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
        }
        .video-fallback {
            margin-top: 8px;
        }
        .video-fallback a {
            color: #6e4b3a;
            font-weight: 600;
        }
        .qr-map-card {
            margin-top: 22px;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid rgba(110, 75, 58, 0.15);
            background: #e8ecf1;
            min-height: 200px;
        }
        #qr-landmark-map,
        #qr-landmark-map-osm { width: 100%; height: 240px; z-index: 0; }
        .qr-map-caption {
            font-size: 13px;
            color: #555;
            margin-top: 8px;
        }
        .mapboxgl-ctrl-attrib-inner { font-size: 10px; }
    </style>
    @if ($showMapEmbed && $mapboxEmbedToken !== '')
        <link href="https://api.mapbox.com/mapbox-gl-js/v3.3.0/mapbox-gl.css" rel="stylesheet"/>
    @elseif ($useLeafletFallback)
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    @endif
</head>
<body>
<header>
    <div class="logo">Histaryo</div>
    <nav>
        <a href="{{ route('home') }}">Home</a>
        <a href="{{ route('about') }}">About</a>
    </nav>
</header>
<main>
    <article class="card">
        @if ($category !== '')
            <span class="tag">{{ $category }}</span>
        @else
            <span class="tag">Landmark</span>
        @endif
        <h1>{{ $name }}</h1>
        @if ($description !== '')
            <p class="lede">{{ $description }}</p>
        @endif

        @if ($locationLabel !== '')
            <p class="meta">📍 <strong>Location:</strong> {{ $locationLabel }}</p>
        @elseif ($mapUrl)
            <p class="meta">📍 <strong>Location:</strong>
                <a href="{{ $mapUrl }}" rel="noopener noreferrer" target="_blank">Open larger map</a>
                <span style="color:#666;font-weight:400"> ({{ number_format((float) $lat, 5) }}, {{ number_format((float) $lng, 5) }})</span>
            </p>
        @elseif ($lat !== null && $lng !== null)
            <p class="meta">📍 <strong>Coordinates:</strong> {{ number_format((float) $lat, 5) }}, {{ number_format((float) $lng, 5) }}</p>
        @endif

        @if ($showMapEmbed && $mapboxEmbedToken !== '')
            <div class="video-block">
                <h2>Map</h2>
                <div class="qr-map-card"><div id="qr-landmark-map" aria-label="{{ $name }} on map"></div></div>
                @if (!$mapUrl)
                    <p class="qr-map-caption">Coordinates {{ number_format((float) $lat, 6) }}, {{ number_format((float) $lng, 6) }}</p>
                @endif
            </div>
        @elseif ($useLeafletFallback)
            <div class="video-block">
                <h2>Map</h2>
                <div class="qr-map-card"><div id="qr-landmark-map-osm" aria-label="{{ $name }} on map"></div></div>
                @if ($mapUrl)
                    <p class="qr-map-caption"><a href="{{ $mapUrl }}" rel="noopener noreferrer" target="_blank">Open larger map</a></p>
                @else
                    <p class="qr-map-caption">Coordinates {{ number_format((float) $lat, 6) }}, {{ number_format((float) $lng, 6) }}</p>
                @endif
            </div>
        @endif

        @if (!empty($imageSrc))
            <div class="photo">
                <img src="{{ $imageSrc }}" alt="{{ $name }}">
            </div>
        @endif

        @if ($videoEmbedUrl !== '')
            <div class="video-block">
                <h2>Video</h2>
                <div class="video-wrap">
                    <iframe src="{{ $videoEmbedUrl }}" title="Landmark video" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
            </div>
        @elseif ($videoUrl !== '')
            <div class="video-block video-fallback">
                <h2>Video</h2>
                <p><a href="{{ $videoUrl }}" rel="noopener noreferrer" target="_blank">Open video</a></p>
            </div>
        @endif
    </article>
</main>
@if ($showMapEmbed && $mapboxEmbedToken !== '')
<script src="https://api.mapbox.com/mapbox-gl-js/v3.3.0/mapbox-gl.js"></script>
<script>
(function () {
    mapboxgl.accessToken = @json($mapboxEmbedToken);
    var lng = @json((float) $lng);
    var lat = @json((float) $lat);
    var map = new mapboxgl.Map({
        container: 'qr-landmark-map',
        style: 'mapbox://styles/mapbox/streets-v12',
        center: [lng, lat],
        zoom: 14,
    });
    map.addControl(new mapboxgl.NavigationControl(), 'top-right');
    new mapboxgl.Marker({ color: '#6e4b3a' }).setLngLat([lng, lat]).addTo(map);
})();
</script>
@elseif ($useLeafletFallback)
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(function () {
    var lat = @json((float) $lat);
    var lng = @json((float) $lng);
    var map = L.map('qr-landmark-map-osm').setView([lat, lng], 14);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);
    L.marker([lat, lng]).addTo(map);
})();
</script>
@endif
</body>
</html>
