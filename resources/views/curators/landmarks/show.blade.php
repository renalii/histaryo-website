<!DOCTYPE html>
<html>
<head>
    <title>View Landmark</title>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        img {
            margin: 10px 0;
        }
        #map {
            height: 300px;
            width: 100%;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <h2>Landmark Details</h2>
    <a href="{{ route('landmarks.index') }}">Back to Landmarks</a>

    <p><strong>Name:</strong> {{ $landmark['name'] ?? 'N/A' }}</p>
    @if (! empty($landmark['landmarkcode'] ?? ''))
        <p><strong>Landmark code:</strong> {{ $landmark['landmarkcode'] }}</p>
    @endif
    <p><strong>Description:</strong> {{ $landmark['description'] ?? 'N/A' }}</p>
    <p><strong>Latitude:</strong> {{ $landmark['latitude'] ?? 'N/A' }}</p>
    <p><strong>Longitude:</strong> {{ $landmark['longitude'] ?? 'N/A' }}</p>

    <!-- Map Display -->
    <h3>Map Location:</h3>
    <div id="map"></div>

    <!-- Old Photo -->
    @php
        $imageSrc = null;
        if (!empty($landmark['image_base64'])) {
            $imageMime = $landmark['image_mime'] ?? 'image/jpeg';
            $imageSrc = 'data:' . $imageMime . ';base64,' . $landmark['image_base64'];
        }
    @endphp

    @if (!empty($imageSrc))
        <p><strong>Old Photo:</strong></p>
        <img src="{{ $imageSrc }}" alt="Landmark Image" style="max-width: 300px;">
    @endif

    <!-- YouTube Video -->
    @php
        use Illuminate\Support\Str;

        $videoUrl = $landmark['video_url'] ?? '';
        $embedUrl = '';

        if (Str::contains($videoUrl, 'youtube.com/watch')) {
            $embedUrl = str_replace('watch?v=', 'embed/', $videoUrl);
        } elseif (Str::contains($videoUrl, 'youtu.be/')) {
            $videoId = Str::after($videoUrl, 'youtu.be/');
            $embedUrl = 'https://www.youtube.com/embed/' . $videoId;
        }
    @endphp

    @if (!empty($videoUrl))
        <p><strong>Video:</strong> <a href="{{ $videoUrl }}" target="_blank">Watch</a></p>
    @endif

    @if ($embedUrl)
        <iframe width="420" height="315" src="{{ $embedUrl }}" frameborder="0" allowfullscreen></iframe>
    @endif

    <!-- QR Code -->
    <h3>QR Code:</h3>
    <p>Scan this QR code with the mobile app to launch AR:</p>
    {!! QrCode::size(200)->generate($id) !!}

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        const latitude = {{ $landmark['latitude'] ?? 0 }};
        const longitude = {{ $landmark['longitude'] ?? 0 }};

        const map = L.map('map').setView([latitude, longitude], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        L.marker([latitude, longitude]).addTo(map)
            .bindPopup("{{ $landmark['name'] ?? 'Landmark' }}")
            .openPopup();
    </script>

</body>
</html>
