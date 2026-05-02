@extends('layouts.sidebar')

@php
    use Illuminate\Support\Str;
    $currentView = request()->get('view', 'card');
    $landmarkCount = method_exists($landmarks, 'total') ? $landmarks->total() : $landmarks->count();
    $panelRoutePrefix = session('role') === 'landmark_manager' ? 'landmarkmanager' : 'admin';
@endphp

@section('content')
    @if (session('status'))
        <style>
            .flash-ok-lm {
                max-width: 2000px; margin: 0 auto .75rem;
                padding: .75rem 1rem;
                border-radius: 10px;
                background: #ecfdf5;
                color: #166534;
                border: 1px solid #bbf7d0;
                font-weight: 600;
            }
        </style>
        <div class="flash-ok-lm">{{ session('status') }}</div>
    @endif
    <style>
        .land-wrap { max-width: 2000px; margin: 0 auto; }
        .land-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: .25rem;
            flex-wrap: wrap;
        }
        .land-header-main {
            display: flex;
            flex-direction: column;
            gap: .1rem;
        }
        .land-title { font-size: 1.9rem; font-weight: 800; margin: 0; color: #7A2E1F; }
        .land-sub { margin: 0; color: #6b7280; font-size: .95rem; }
        .view-switch {
            margin-bottom: 0;
            display: inline-flex;
            gap: .45rem;
            background: #fff;
            border: 1px solid #eceff3;
            border-radius: 10px;
            padding: .35rem;
            box-shadow: 0 4px 12px rgba(15,23,42,.05);
        }
        @media (max-width: 700px) {
            .land-header {
                align-items: flex-start;
                flex-direction: column;
            }
        }
        .view-btn {
            padding: .45rem .8rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #f8fafc;
            color: #374151;
            text-decoration: none;
            font-weight: 700;
            font-size: .88rem;
            transition: all .15s ease;
        }
        .view-btn.active {
            background: #E8B34B;
            border-color: #F3C96A;
            color: #7A2E1F;
        }
        .view-btn:hover { transform: translateY(-1px); }
        #card-view.card-grid {
            display: flex !important;
            /* flex-wrap: wrap; */
            gap: 1rem;
        }
        #card-view.card-grid .land-card {
            width: calc((100% - 2rem) / 3);
        }
        @media (max-width: 1100px) {
            #card-view.card-grid .land-card {
                width: calc((100% - 1rem) / 2);
            }
        }
        @media (max-width: 700px) {
            #card-view.card-grid .land-card {
                width: 100%;
            }
        }
        .land-card {
            background:#fff;
            padding:1rem;
            border-radius:12px;
            border:1px solid #eceff3;
            box-shadow:0 6px 16px rgba(0,0,0,.05);
            display:flex;
            flex-direction:column;
            gap:.6rem;
        }
        .land-card h3 { font-size:1.2rem; color:#111827; margin:0; }
        .meta { margin:0; font-size:.9rem; color:#4b5563; }
        .desc {
            margin:0;
            font-size:.92rem;
            color:#374151;
            overflow:hidden;
            display:-webkit-box;
            -webkit-line-clamp:4;
            -webkit-box-orient:vertical;
            line-height:1.35;
        }
        .media-box img, .media-box iframe { width:100%; border-radius:8px; display:block; }
        .table-wrap {
            margin-top: .5rem;
            border: 1px solid #eceff3;
            border-radius: 12px;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 6px 16px rgba(0,0,0,.05);
        }
        .land-table { width: 100%; border-collapse: collapse; }
        .land-table thead { background: #fff7ed; }
        .land-table th {
            padding: 12px;
            text-align: left;
            color: #7A2E1F;
            font-size: .92rem;
        }
        .land-table td { padding: 12px; border-top: 1px solid #eef2f7; }
        .row-main { cursor: pointer; transition: background .15s ease; }
        .row-main:hover { background: #fcfcfd; }
        .expand-text { color: #7A2E1F; font-weight: 600; }
        .row-expanded { background: #fafafa; }
        .row-expanded td { padding: 14px; }
        .row-content { display: grid; gap: .75rem; }
        .row-content .detail { color: #374151; }
        .list-media-row {
            display: flex;
            gap: 0;
            overflow: hidden;
            border-radius: 8px;
            width: 100%;
            max-width: 980px;
        }
        .list-media-row .media-box {
            flex: 1 1 50%;
            min-width: 0;
        }
        .list-media-row .media-box img,
        .list-media-row .media-box iframe {
            width: 100% !important;
            height: 350px !important;
            border-radius: 0 !important;
            display: block;
            object-fit: cover;
        }
        .list-media-row .media-box:first-child img,
        .list-media-row .media-box:first-child iframe {
            border-top-left-radius: 8px !important;
            border-bottom-left-radius: 8px !important;
        }
        .list-media-row .media-box:last-child img,
        .list-media-row .media-box:last-child iframe {
            border-top-right-radius: 8px !important;
            border-bottom-right-radius: 8px !important;
        }
        .list-media-row .media-box:only-child img,
        .list-media-row .media-box:only-child iframe {
            border-radius: 8px !important;
        }
        @media (max-width: 760px) {
            .list-media-row {
                flex-direction: column;
                max-width: 100%;
            }
        }
        .empty-box {
            color: #6b7280;
            background: #fff;
            border: 1px dashed #d1d5db;
            border-radius: 10px;
            padding: .9rem 1rem;
        }
        .pager {
            margin-top: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .55rem;
            flex-wrap: wrap;
        }
        .pager-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .48rem .82rem;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            text-decoration: none;
            font-weight: 700;
            font-size: .9rem;
            transition: all .15s ease;
        }
        .pager-btn.active {
            background: #E8B34B;
            border-color: #F3C96A;
            color: #7A2E1F;
        }
        .pager-btn.active:hover {
            background: #F3C96A;
            transform: translateY(-1px);
        }
        .pager-btn.disabled {
            background: #f9fafb;
            color: #9ca3af;
            pointer-events: none;
            border-color: #e5e7eb;
        }
        .pager-text {
            color: #6b7280;
            font-size: .9rem;
            font-weight: 600;
            padding: 0 .25rem;
        }
    </style>
    <div class="land-wrap">
    <div class="land-header">
        <div class="land-header-main">
            <h2 class="land-title">All Landmarks</h2>
            <p class="land-sub">
                @if ($panelRoutePrefix === 'landmarkmanager')
                    {{ $landmarkCount }} landmark{{ $landmarkCount !== 1 ? 's' : '' }} in your portfolio — add sites with Create landmark to raise this total.
                @else
                    {{ $landmarkCount }} landmark{{ $landmarkCount !== 1 ? 's' : '' }} available
                @endif
            </p>
        </div>

        <div style="display:flex;flex-wrap:wrap;gap:.5rem;align-items:center;">
            @if ($panelRoutePrefix === 'landmarkmanager')
                <a href="{{ route('landmarkmanager.landmarks.create') }}" class="view-btn active">Create landmark</a>
            @endif
            <div class="view-switch">
                <a href="{{ route($panelRoutePrefix . '.landmarks', ['view' => 'card']) }}"
                   class="view-btn {{ $currentView === 'card' ? 'active' : '' }}">
                    Card View
                </a>
                <a href="{{ route($panelRoutePrefix . '.landmarks', ['view' => 'list']) }}"
                   class="view-btn {{ $currentView === 'list' ? 'active' : '' }}">
                    List View
                </a>
            </div>
        </div>
    </div>

    @if ($landmarks->isEmpty())
        <p class="empty-box">No landmarks found.</p>
    @else

        
        @if ($currentView === 'card')
            <div id="card-view" class="card-grid">
                @foreach ($landmarks as $landmark)
                    @php
                        $data = $landmark->data();
                        $videoUrl = $data['video_url'] ?? '';
                        $embedUrl = '';
                        $imageSrc = null;

                        if (!empty($data['image_base64'])) {
                            $imageMime = $data['image_mime'] ?? 'image/jpeg';
                            $imageSrc = 'data:' . $imageMime . ';base64,' . $data['image_base64'];
                        }   

                        if (Str::contains($videoUrl, 'youtube.com/watch')) {
                            parse_str(parse_url($videoUrl, PHP_URL_QUERY), $queryParams);
                            if (isset($queryParams['v'])) {
                                $embedUrl = 'https://www.youtube.com/embed/' . $queryParams['v'];
                            }
                        } elseif (Str::contains($videoUrl, 'youtu.be')) {
                            $videoId = basename(parse_url($videoUrl, PHP_URL_PATH));
                            $embedUrl = 'https://www.youtube.com/embed/' . $videoId;
                        } else {
                            $embedUrl = $videoUrl;
                        }
                    @endphp

                    <div class="land-card">
                        <h3>
                            {{ $data['name'] ?? 'Unnamed Landmark' }}
                        </h3>
                        @if (! empty($data['landmarkcode'] ?? ''))
                            <p class="meta" style="font-family:ui-monospace,monospace;font-weight:600;color:#92400e;">{{ $data['landmarkcode'] }}</p>
                        @endif

                        <p class="meta">
                            Lat: {{ $data['latitude'] ?? 'N/A' }}<br>
                            Lng: {{ $data['longitude'] ?? 'N/A' }}
                        </p>

                        @if (!empty($imageSrc))
                            <div class="media-box">
                                <img src="{{ $imageSrc }}" alt="Landmark Image">
                            </div>
                        @endif

                        @if (!empty($embedUrl))
                            <div class="media-box">
                                <iframe width="100%" height="180" src="{{ $embedUrl }}" frameborder="0"
                                    allowfullscreen></iframe>
                            </div>
                        @endif

                        @if (!empty($data['description']))
                            <p class="desc">
                                {{ $data['description'] }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>

        @endif

        
        @if ($currentView === 'list')
            <div id="list-view" class="table-wrap">
                <table class="land-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Coordinates</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($landmarks as $index => $landmark)
                            @php
                                $data = $landmark->data();
                                $videoUrl = $data['video_url'] ?? '';
                                $embedUrl = '';
                                $imageSrc = null;

                                if (!empty($data['image_base64'])) {
                                    $imageMime = $data['image_mime'] ?? 'image/jpeg';
                                    $imageSrc = 'data:' . $imageMime . ';base64,' . $data['image_base64'];
                                }

                                if (Str::contains($videoUrl, 'youtube.com/watch')) {
                                    parse_str(parse_url($videoUrl, PHP_URL_QUERY), $queryParams);
                                    if (isset($queryParams['v'])) {
                                        $embedUrl = 'https://www.youtube.com/embed/' . $queryParams['v'];
                                    }
                                } elseif (Str::contains($videoUrl, 'youtu.be')) {
                                    $videoId = basename(parse_url($videoUrl, PHP_URL_PATH));
                                    $embedUrl = 'https://www.youtube.com/embed/' . $videoId;
                                } else {
                                    $embedUrl = $videoUrl;
                                }
                            @endphp

                            <tr class="row-main" onclick="toggleRow({{ $index }})">
                                <td>{{ $data['name'] ?? 'Unnamed Landmark' }}</td>
                                <td style="font-family:ui-monospace,monospace;">{{ $data['landmarkcode'] ?? '—' }}</td>
                                <td>{{ $data['latitude'] ?? 'N/A' }}, {{ $data['longitude'] ?? 'N/A' }}</td>
                                <td class="expand-text">Click to expand</td>
                            </tr>
                            <tr id="expand-{{ $index }}" class="row-expanded" style="display: none;">
                                <td colspan="4">
                                    <div class="row-content">
                                    @if (!empty($imageSrc) || !empty($embedUrl))
                                        <div class="{{ !empty($imageSrc) && !empty($embedUrl) ? 'list-media-row' : '' }}">
                                            @if (!empty($imageSrc))
                                                <div class="media-box">
                                                    <img src="{{ $imageSrc }}" alt="Landmark Image">
                                                </div>
                                            @endif
                                            @if (!empty($embedUrl))
                                                <div class="media-box">
                                                    <iframe width="100%" height="180" src="{{ $embedUrl }}" frameborder="0"
                                                        allowfullscreen></iframe>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                    <div class="detail"><strong>Description:</strong> {{ $data['description'] ?? 'No description' }}</div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if (method_exists($landmarks, 'hasPages') && $landmarks->hasPages())
            <nav class="pager" aria-label="Landmarks pagination">
                @if ($landmarks->onFirstPage())
                    <span class="pager-btn disabled">← Prev</span>
                @else
                    <a class="pager-btn active" href="{{ $landmarks->appends(['view' => $currentView])->previousPageUrl() }}">← Prev</a>
                @endif

                <span class="pager-text">Page {{ $landmarks->currentPage() }} of {{ $landmarks->lastPage() }}</span>

                @if ($landmarks->hasMorePages())
                    <a class="pager-btn active" href="{{ $landmarks->appends(['view' => $currentView])->nextPageUrl() }}">Next →</a>
                @else
                    <span class="pager-btn disabled">Next →</span>
                @endif
            </nav>
        @endif
    @endif
    </div>

    <script>
        function toggleRow(index) {
            const row = document.getElementById('expand-' + index);
            row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
        }
    </script>
@endsection
