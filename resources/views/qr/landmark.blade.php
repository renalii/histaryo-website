@php
    /** @var array $payload */
    /** @var array $landmark */
    /** @var string $videoEmbedUrl */
    /** @var string|null $mapUrl */
    $lm = $payload['landmark'] ?? [];
    $name = $lm['name'] ?? 'Landmark';
    $description = trim((string) ($lm['description'] ?? ''));
    $category = trim((string) ($lm['category'] ?? ''));
    $videoUrl = trim((string) ($lm['video_url'] ?? ''));
    $locationLabel = trim((string) ($landmark['location'] ?? ''));
    $lat = $lm['latitude'] ?? null;
    $lng = $lm['longitude'] ?? null;
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
    </style>
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
                <a href="{{ $mapUrl }}" rel="noopener noreferrer" target="_blank">View on map</a>
                @if ($lat !== null && $lng !== null)
                    <span style="color:#666;font-weight:400"> ({{ number_format((float) $lat, 5) }}, {{ number_format((float) $lng, 5) }})</span>
                @endif
            </p>
        @elseif ($lat !== null && $lng !== null)
            <p class="meta">📍 <strong>Coordinates:</strong> {{ number_format((float) $lat, 5) }}, {{ number_format((float) $lng, 5) }}</p>
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
</body>
</html>
