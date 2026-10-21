@php
    /** @var array $payload */
    /** @var array $landmark */
    /** @var string|null $mapUrl */
    /** @var string|null $mapboxToken */
    $lm = $payload['landmark'] ?? [];
    $name = $lm['name'] ?? 'Landmark';
    $description = trim((string) ($lm['description'] ?? ''));
    $category = trim((string) ($lm['category'] ?? ''));
    $locationLabel = trim((string) ($landmark['location'] ?? ''));
    $latRaw = $landmark['latitude'] ?? $landmark['lati'] ?? null;
    $lngRaw = $landmark['longitude'] ?? $landmark['longti'] ?? null;
    $lat = isset($latRaw) && is_numeric($latRaw) ? (float) $latRaw : null;
    $lng = isset($lngRaw) && is_numeric($lngRaw) ? (float) $lngRaw : null;
    $showMapEmbed = $lat !== null && $lng !== null;
    $mapboxEmbedToken = isset($mapboxToken) ? trim((string) $mapboxToken) : '';
    $useLeafletFallback = $showMapEmbed && $mapboxEmbedToken === '';
    $imageSrc = null;
    if (!empty($landmark['image_path'])) {
        $imageSrc = $landmark['image_path'];
    }
    $exhibits = is_array($exhibits ?? null) ? $exhibits : [];
    $quiz = is_array($quiz ?? null) ? $quiz : [];
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
        .exhibit-section, .quiz-section { margin-top: 28px; }
        .section-heading { margin: 0 0 12px; color: #2a1810; font-size: 20px; }
        .exhibit-grid { display: grid; gap: 14px; }
        .exhibit-card { border: 1px solid rgba(110, 75, 58, .15); border-radius: 14px; padding: 16px; background: #fffaf6; }
        .exhibit-card h3 { margin: 0 0 6px; color: #6e4b3a; font-size: 18px; }
        .exhibit-meta { margin: 0 0 8px; color: #8a6a58; font-size: 13px; font-weight: 600; }
        .exhibit-card p { margin: 0; color: #4b3b34; line-height: 1.55; }
        .exhibit-image { display: block; width: 100%; max-height: 180px; object-fit: cover; border-radius: 10px; margin-bottom: 12px; }
        .quiz-section { padding: 20px; border-radius: 16px; background: #f8f1e9; border: 1px solid #ead8c9; }
        .quiz-intro { margin: -4px 0 16px; color: #5c4a40; line-height: 1.5; }
        .quiz-question { margin: 0 0 18px; padding: 0 0 18px; border-bottom: 1px solid #ead8c9; }
        .quiz-question:last-of-type { border-bottom: 0; }
        .quiz-question legend { padding: 0; margin-bottom: 10px; color: #2a1810; font-weight: 700; line-height: 1.45; }
        .quiz-option { display: block; margin: 8px 0; color: #4b3b34; cursor: pointer; }
        .quiz-option input { margin-right: 8px; accent-color: #6e4b3a; }
        .quiz-submit { border: 0; border-radius: 999px; padding: 11px 18px; background: #6e4b3a; color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
        .quiz-submit:disabled { opacity: .6; cursor: wait; }
        .quiz-result { margin: 14px 0 0; padding: 12px 14px; border-radius: 10px; background: #fff; color: #6e4b3a; font-weight: 700; line-height: 1.45; }
        .quiz-empty { margin: 0; color: #5c4a40; }
        .qr-section-block {
            margin-top: 26px;
        }
        .qr-section-block h2 {
            margin: 0 0 12px;
            font-size: 17px;
            color: #2a1810;
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
            <div class="qr-section-block">
                <h2>Map</h2>
                <div class="qr-map-card"><div id="qr-landmark-map" aria-label="{{ $name }} on map"></div></div>
                @if (!$mapUrl)
                    <p class="qr-map-caption">Coordinates {{ number_format((float) $lat, 6) }}, {{ number_format((float) $lng, 6) }}</p>
                @endif
            </div>
        @elseif ($useLeafletFallback)
            <div class="qr-section-block">
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

        @if (count($exhibits) > 0)
            <section class="exhibit-section" aria-labelledby="exhibits-heading">
                <h2 id="exhibits-heading" class="section-heading">Explore the exhibits</h2>
                <div class="exhibit-grid">
                    @foreach ($exhibits as $exhibit)
                        <article class="exhibit-card">
                            @if (!empty($exhibit['images'][0]))
                                <img class="exhibit-image" src="{{ $exhibit['images'][0] }}" alt="{{ $exhibit['name'] }}">
                            @endif
                            <h3>{{ $exhibit['name'] }}</h3>
                            @if (($exhibit['category'] ?? '') !== '' || ($exhibit['year_period'] ?? '') !== '')
                                <p class="exhibit-meta">{{ collect([$exhibit['category'] ?? '', $exhibit['year_period'] ?? ''])->filter()->implode(' · ') }}</p>
                            @endif
                            @if (($exhibit['description'] ?? '') !== '')
                                <p>{{ $exhibit['description'] }}</p>
                            @elseif (($exhibit['historical_info'] ?? '') !== '')
                                <p>{{ $exhibit['historical_info'] }}</p>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        @if (count($quiz) > 0)
            <section class="quiz-section" aria-labelledby="quiz-heading">
                <h2 id="quiz-heading" class="section-heading">Test your knowledge</h2>
                <p class="quiz-intro">You’ve explored {{ $name }}. See what you remember from this exhibit.</p>
                <form id="exhibit-quiz-form">
                    @foreach ($quiz as $index => $question)
                        <fieldset class="quiz-question">
                            <legend>{{ $index + 1 }}. {{ $question['question'] }}</legend>
                            @foreach ($question['choices'] as $choice)
                                <label class="quiz-option">
                                    <input type="radio" name="question_{{ $index }}" value="{{ $choice }}" required>
                                    {{ $choice }}
                                </label>
                            @endforeach
                        </fieldset>
                    @endforeach
                    <button class="quiz-submit" type="submit">Check my answers</button>
                    <p id="quiz-result" class="quiz-result" hidden role="status"></p>
                </form>
            </section>
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
@if (count($quiz) > 0)
<script>
(function () {
    var form = document.getElementById('exhibit-quiz-form');
    var result = document.getElementById('quiz-result');
    var questions = @json($quiz);
    var landmarkId = @json($lm['id'] ?? '');
    var landmarkName = @json($name);

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        var correct = 0;
        questions.forEach(function (question, index) {
            var answer = form.querySelector('input[name="question_' + index + '"]:checked');
            if (answer && answer.value === question.correct_answer) correct++;
        });
        var percentage = Math.round((correct / questions.length) * 100);
        result.textContent = 'You scored ' + correct + ' out of ' + questions.length + ' (' + percentage + '%). ' + (percentage === 100 ? 'Perfect score!' : 'Thanks for exploring ' + landmarkName + '!');
        result.hidden = false;

        var visitorId = window.localStorage.getItem('histaryo_visitor_id');
        if (!visitorId) {
            visitorId = window.crypto && window.crypto.randomUUID ? window.crypto.randomUUID() : 'visitor-' + Date.now();
            window.localStorage.setItem('histaryo_visitor_id', visitorId);
        }
        fetch('{{ url('/api/quiz-results') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ landmark_id: landmarkId, landmark_name: landmarkName, visitor_id: visitorId, score_percentage: percentage, score_points: correct, total_points: questions.length, correct_answers: correct, total_questions: questions.length })
        }).catch(function () {});
    });
})();
</script>
@endif
</body>
</html>
