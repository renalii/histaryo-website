@extends('layouts.sidebar')

@php
    use Illuminate\Support\Str;

    $videoUrl = trim((string) ($data['video_url'] ?? ''));
    $embedUrl = '';
    $imageSrc = null;

    if (! empty($data['image_base64'] ?? null)) {
        $imageMime = $data['image_mime'] ?? 'image/jpeg';
        $imageSrc = 'data:' . $imageMime . ';base64,' . $data['image_base64'];
    }

    if (Str::contains($videoUrl, 'youtube.com/watch')) {
        parse_str((string) parse_url($videoUrl, PHP_URL_QUERY), $queryParams);
        if (isset($queryParams['v'])) {
            $embedUrl = 'https://www.youtube.com/embed/' . $queryParams['v'];
        }
    } elseif (Str::contains($videoUrl, 'youtu.be/')) {
        $path = parse_url($videoUrl, PHP_URL_PATH);
        $videoId = $path ? basename($path) : '';
        if ($videoId !== '') {
            $embedUrl = 'https://www.youtube.com/embed/' . $videoId;
        }
    } elseif ($videoUrl !== '' && filter_var($videoUrl, FILTER_VALIDATE_URL)) {
        $embedUrl = $videoUrl;
    }

    $youtubeIframeSrc = ($embedUrl !== '' && Str::contains($embedUrl, 'youtube.com/embed')) ? $embedUrl : '';
    $videoOutValid = $videoUrl !== '' && filter_var($videoUrl, FILTER_VALIDATE_URL);
    $showVideoLinkOnly = $videoOutValid && $youtubeIframeSrc === '';
    $modalSafe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $landmarkId);
@endphp

@section('content')
    @if (session('success'))
        <div role="status"
             style="max-width:980px;margin:0 auto 1rem;padding:.75rem 1rem;border-radius:10px;background:#ecfdf5;color:#166534;border:1px solid #bbf7d0;font-weight:600;font-size:.92rem;">
            {{ session('success') }}
        </div>
    @endif
    <style>
        .lm-detail { max-width: 980px; margin: 0 auto; }
        .lm-detail__toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .65rem .75rem;
            margin-bottom: 1rem;
            justify-content: space-between;
        }
        .lm-detail__toolbar-left {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .5rem .65rem;
        }
        .lm-detail__back {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            font-weight: 600;
            font-size: .875rem;
            color: #7A2E1F;
            text-decoration: none;
            padding: .45rem .65rem;
            margin-left: -.65rem;
            border-radius: 8px;
            transition: background .15s ease;
        }
        .lm-detail__back:hover { background: rgba(122, 46, 31, 0.08); text-decoration: none; color: #7A2E1F; }
        .cu-btn-gold {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .55rem 1.1rem;
            border-radius: 10px;
            font-weight: 700;
            font-size: .88rem;
            border: 1px solid #F3C96A;
            background: linear-gradient(180deg, #f3d073 0%, #E8B34B 100%);
            color: #461c14;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 3px 10px rgba(122, 46, 31, .1);
            font-family: inherit;
        }
        .cu-btn-gold:hover { filter: brightness(1.03); }
        .cu-btn-outline {
            display: inline-flex;
            align-items: center;
            padding: .5rem 1rem;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            background: #fff;
            font-weight: 600;
            font-size: .86rem;
            color: #374151;
            text-decoration: none;
        }
        .cu-btn-outline:hover { border-color: #E8B34B; background: #fffefb; color: #7A2E1F; }

        .lm-detail__panel {
            background: #fff;
            border-radius: 16px;
            border: 1px solid rgba(122, 46, 31, 0.08);
            box-shadow:
                0 1px 2px rgba(15, 23, 42, 0.04),
                0 12px 40px rgba(122, 46, 31, 0.06);
            overflow: hidden;
        }
        .lm-detail__hero {
            padding: 1.75rem 1.75rem 1.35rem;
            background: linear-gradient(135deg, #fffdf9 0%, #fff 50%, #faf8ff 100%);
            border-bottom: 1px solid #f0eef5;
        }
        @media (min-width: 640px) {
            .lm-detail__hero { padding: 2rem 2.25rem 1.5rem; }
        }
        .lm-detail__meta-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .65rem 1rem;
            margin-bottom: .5rem;
        }
        .lm-detail__eyebrow {
            margin: 0;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #A67C52;
            flex-shrink: 0;
        }
        .lm-detail__title {
            margin: 0 0 1rem;
            font-size: clamp(1.45rem, 3vw, 1.85rem);
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #4c1d95;
            line-height: 1.2;
        }
        .lm-detail__chips {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem .6rem;
            align-items: center;
        }
        .lm-chip {
            display: inline-flex;
            align-items: baseline;
            gap: .35rem;
            padding: .4rem .72rem;
            border-radius: 999px;
            font-size: .78rem;
            line-height: 1.2;
            border: 1px solid #ece8e4;
            background: #fff;
            color: #57534e;
        }
        .lm-chip__k {
            font-weight: 600;
            color: #44403c;
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .06em;
        }
        .lm-chip__v {
            font-family: ui-monospace, 'Cascadia Code', monospace;
            font-size: .76rem;
            word-break: break-all;
        }
        .lm-chip--muted { color: #78716c; }
        .lm-chip--code {
            border-color: #F3C96A;
            background: linear-gradient(180deg, #fffbeb, #fff);
            color: #92400e;
        }
        .lm-chip--coord {
            border-color: #c7d2fe;
            background: #eef2ff;
            color: #3730a3;
        }
        .lm-chip--category {
            border-color: #bfdbfe;
            background: #eff6ff;
            color: #1d4ed8;
            font-weight: 600;
        }

        .lm-detail__body { padding: 1.75rem 1.75rem 2rem; }
        @media (min-width: 640px) {
            .lm-detail__body { padding: 2rem 2.25rem 2.25rem; }
        }
        .lm-detail__section-title {
            margin: 0 0 .4rem;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: #7A2E1F;
        }
        .lm-media-grid { display: grid; gap: 1rem; margin-bottom: .65rem; }
        @media (min-width: 800px) {
            .lm-media-grid--two { grid-template-columns: 1fr 1fr; }
        }
        .lm-media-frame {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e7e5e4;
            background: #f5f5f4;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
        }
        .lm-media-frame img {
            display: block;
            width: 100%;
            max-height: min(380px, 55vh);
            object-fit: cover;
        }
        .lm-media-frame__cap {
            padding: .5rem .85rem;
            font-size: .75rem;
            font-weight: 600;
            color: #57534e;
            background: #fafaf9;
            border-top: 1px solid #e7e5e4;
        }
        .lm-ratio-16x9 {
            position: relative;
            width: 100%;
            padding-bottom: 56.25%;
            height: 0;
            background: #1c1917;
        }
        .lm-ratio-16x9 iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
        }
        .lm-video-card {
            border-radius: 12px;
            border: 1px dashed #d6d3d1;
            background: linear-gradient(180deg, #fafaf9, #fff);
            padding: 1.35rem 1.25rem;
            text-align: center;
        }
        .lm-video-card p { margin: 0 0 .85rem; font-size: .88rem; color: #57534e; line-height: 1.45; }
        .lm-video-card__btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .4rem;
            padding: .55rem 1.1rem;
            border-radius: 10px;
            font-weight: 700;
            font-size: .875rem;
            text-decoration: none;
            background: #7A2E1F;
            color: #fffdf7;
            border: 1px solid #5c2418;
        }
        .lm-detail__about {
            padding-top: .35rem;
            border-top: 1px solid #f0eef5;
        }
        .lm-detail__about-text {
            margin: 0;
            font-size: .97rem;
            line-height: 1.65;
            color: #44403c;
            max-width: 72ch;
            white-space: pre-wrap;
        }

        /* Modal — match landmarks index */
        .modal-cl {
            display: none;
            position: fixed;
            z-index: 1000;
            inset: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            padding: 1.25rem;
            align-items: center;
            justify-content: center;
        }
        .modal-cl .modal-content {
            background: #fefefe;
            margin: auto;
            padding: 1.5rem 1.85rem;
            border-radius: 14px;
            max-width: 560px;
            width: 100%;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.12);
            position: relative;
            max-height: 92vh;
            overflow-y: auto;
        }
        .modal-cl .modal-content h3 {
            margin: 0 2rem .5rem 0;
            font-size: 1.35rem;
            font-weight: 700;
            color: #4c1d95;
        }
        .modal-cl .modal-content label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-top: .85rem;
            margin-bottom: .35rem;
            font-size: .86rem;
        }
        .modal-cl .modal-content input[type="text"],
        .modal-cl .modal-content input[type="url"],
        .modal-cl .modal-content input[type="file"],
        .modal-cl .modal-content textarea,
        .modal-cl .modal-content select {
            width: 100%;
            padding: .5rem .75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #f9fafb;
            font-size: .875rem;
            box-sizing: border-box;
            font-family: inherit;
        }
        .modal-cl .modal-content button[type="submit"] {
            margin-top: 1.35rem;
            background: #E8B34B;
            color: #7A2E1F;
            padding: .55rem 1.1rem;
            font-size: .9rem;
            border-radius: 8px;
            font-weight: 700;
            border: 1px solid #F3C96A;
            cursor: pointer;
            font-family: inherit;
        }
        .modal-cl .close-cl {
            position: absolute;
            top: 12px;
            right: 16px;
            font-size: 26px;
            font-weight: bold;
            color: #6b7280;
            cursor: pointer;
            line-height: 1;
        }
        .modal-cl .close-cl:hover { color: #111827; }
        .cl-modal-readonly {
            margin: .35rem 0 0;
            font-size: .82rem;
            color: #6b7280;
        }
        .cl-modal-readonly .mono { font-family: ui-monospace, monospace; font-weight: 600; color: #92400e; }
    </style>

    <div class="lm-detail">
        <div class="lm-detail__toolbar">
            <div class="lm-detail__toolbar-left">
                <a href="{{ route('landmarks.show', $landmarkId) }}" class="lm-detail__back">← Landmarks</a>
            </div>
            <div class="lm-detail__toolbar-left" style="margin-left:auto;">
                <button type="button" class="cu-btn-gold" onclick="cuOpenModalCl('editModal_{{ $modalSafe }}')">Edit</button>
                <a href="{{ route('curators.qr') }}"
                   class="cu-btn-outline"
                   data-qr-download-url="{{ route('curators.qr.byLandmark', $landmarkId) }}"
                   onclick="cuDownloadQrAndGo(event, this)">Download QR</a>
            </div>
        </div>

        <article class="lm-detail__panel">
            <header class="lm-detail__hero">
                <div class="lm-detail__meta-row">
                    <p class="lm-detail__eyebrow">Landmark detail</p>
                    <div class="lm-detail__chips" aria-label="Landmark metadata">
                        <span class="lm-chip lm-chip--category">
                            <span class="lm-chip__k">Category</span>
                            <span style="font-family:inherit;font-size:.8rem;">{{ $data['category'] ?? 'Uncategorized' }}</span>
                        </span>
                        <span class="lm-chip lm-chip--muted">
                            <span class="lm-chip__k">ID</span>
                            <span class="lm-chip__v">{{ $landmarkId }}</span>
                        </span>
                        @if (! empty($data['landmarkcode'] ?? ''))
                            <span class="lm-chip lm-chip--code">
                                <span class="lm-chip__k">Code</span>
                                <span class="lm-chip__v">{{ $data['landmarkcode'] }}</span>
                            </span>
                        @endif
                        <span class="lm-chip lm-chip--coord">
                            <span class="lm-chip__k">Location</span>
                            <span class="lm-chip__v">{{ $data['latitude'] ?? 'N/A' }}, {{ $data['longitude'] ?? 'N/A' }}</span>
                        </span>
                    </div>
                </div>
                <h1 class="lm-detail__title">{{ $data['name'] ?? 'Unnamed landmark' }}</h1>
            </header>

            <div class="lm-detail__body">
                @if ($imageSrc || $youtubeIframeSrc !== '' || $showVideoLinkOnly)
                    <h2 class="lm-detail__section-title">Photos & media</h2>
                    <div class="lm-media-grid @if ($imageSrc && ($youtubeIframeSrc !== '' || $showVideoLinkOnly)) lm-media-grid--two @endif">
                        @if ($imageSrc)
                            <figure class="lm-media-frame">
                                <img src="{{ $imageSrc }}" alt="Photo of {{ $data['name'] ?? 'landmark' }}">
                                <figcaption class="lm-media-frame__cap">Featured image</figcaption>
                            </figure>
                        @endif

                        @if ($youtubeIframeSrc !== '')
                            <div class="lm-media-frame">
                                <div class="lm-ratio-16x9">
                                    <iframe src="{{ $youtubeIframeSrc }}"
                                        title="Video: {{ $data['name'] ?? 'landmark' }}"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                        allowfullscreen
                                        loading="lazy"
                                        referrerpolicy="strict-origin-when-cross-origin"></iframe>
                                </div>
                                <div class="lm-media-frame__cap">
                                    Video
                                    @if ($videoOutValid)
                                        <span style="font-weight:400;color:#78716c;"> · </span>
                                        <a href="{{ $videoUrl }}" target="_blank" rel="noopener noreferrer" style="color:#7A2E1F;font-weight:600;">Open link</a>
                                    @endif
                                </div>
                            </div>
                        @elseif ($showVideoLinkOnly)
                            <div class="lm-video-card">
                                <p>This video link can’t be embedded here. Open it in your browser to watch.</p>
                                <a href="{{ $videoUrl }}" class="lm-video-card__btn" target="_blank" rel="noopener noreferrer">
                                    Open video ↗
                                </a>
                            </div>
                        @endif
                    </div>
                @endif

                @if (! empty($data['description'] ?? ''))
                    <section class="lm-detail__about" aria-labelledby="cu-about-heading">
                        <h2 id="cu-about-heading" class="lm-detail__section-title">About</h2>
                        <p class="lm-detail__about-text">{{ $data['description'] }}</p>
                    </section>
                @endif
            </div>
        </article>
    </div>

    <div id="editModal_{{ $modalSafe }}" class="modal-cl" role="dialog" aria-modal="true">
        <div class="modal-content">
            <span class="close-cl" onclick="cuCloseModalCl('editModal_{{ $modalSafe }}')" aria-label="Close">&times;</span>
            <h3>{{ $data['name'] ?? 'Edit site' }}</h3>
            @include('curators.landmarks.partials.edit-form', ['landmarkId' => $landmarkId, 'data' => $data])
        </div>
    </div>

    <script>
        function cuDownloadQrAndGo(event, el) {
            if (event) {
                event.preventDefault();
            }
            var downloadUrl = el && el.getAttribute('data-qr-download-url');
            var qrPageUrl = (el && el.getAttribute('href')) || '';
            if (!downloadUrl || !qrPageUrl) {
                return;
            }
            var iframe = document.createElement('iframe');
            iframe.style.cssText = 'position:absolute;width:0;height:0;border:0;visibility:hidden';
            iframe.setAttribute('aria-hidden', 'true');
            iframe.src = downloadUrl;
            document.body.appendChild(iframe);
            window.setTimeout(function () {
                if (iframe.parentNode) {
                    iframe.parentNode.removeChild(iframe);
                }
            }, 120000);
            window.location.assign(qrPageUrl);
        }
        function cuOpenModalCl(id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        function cuCloseModalCl(id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.style.display = 'none';
            document.body.style.overflow = '';
        }
        document.addEventListener('click', function (e) {
            if (e.target.classList && e.target.classList.contains('modal-cl')) {
                e.target.style.display = 'none';
                document.body.style.overflow = '';
            }
        });
    </script>
@endsection
