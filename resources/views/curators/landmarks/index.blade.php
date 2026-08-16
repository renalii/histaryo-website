@extends('layouts.sidebar')

@section('content')
    @php
        $qrPreview = $qrPreview ?? null;
    @endphp
    @if (session('success'))
        <div class="cu-lm-flash cu-lm-flash--ok" role="status">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="cu-lm-flash cu-lm-flash--err" role="alert">{{ session('error') }}</div>
    @endif
    <style>
        .mono { font-family: ui-monospace, monospace; font-weight: 600; }
        .cu-lm-flash {
            max-width: 1200px;
            margin: 0 auto .85rem;
            padding: .75rem 1rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: .92rem;
        }
        .cu-lm-flash--ok { background: #ecfdf5; color: #166534; border: 1px solid #bbf7d0; }
        .cu-lm-flash--err { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .land-wrap { max-width: 1900px; min-height: 1100px; margin: 0 auto; }
        .land-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: .85rem;
        }
        button.cu-lm-add-btn {
            cursor: pointer;
            font-family: inherit;
        }
        .cu-lm-add-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .55rem 1.15rem;
            border-radius: 10px;
            border: 1px solid #F3C96A;
            background: linear-gradient(180deg, #f3d073 0%, #E8B34B 100%);
            color: #461c14;
            font-weight: 800;
            font-size: .9rem;
            text-decoration: none;
            white-space: nowrap;
            box-shadow: 0 4px 12px rgba(122, 46, 31, 0.1);
            transition: transform 0.12s ease, filter 0.12s ease;
        }
        .cu-lm-add-btn:hover {
            filter: brightness(1.03);
            transform: translateY(-1px);
            color: #461c14;
            text-decoration: none;
        }
        .cu-lm-add-btn:focus-visible {
            outline: 2px solid #7A2E1F;
            outline-offset: 2px;
        }
        .land-header-main { display: flex; flex-direction: column; gap: .65rem; }
        .land-title { font-size: 1.9rem; font-weight: 800; margin: 0; color: #7A2E1F; letter-spacing: -0.02em; }
        #cu-card-view.cu-card-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        #cu-card-view.cu-card-grid .cu-land-card {
            width: calc((100% - 2rem) / 3);
            box-sizing: border-box;
        }
        @media (max-width: 1100px) {
            #cu-card-view.cu-card-grid .cu-land-card { width: calc((100% - 1rem) / 2); }
        }
        @media (max-width: 700px) {
            #cu-card-view.cu-card-grid .cu-land-card { width: 100%; }
        }

        .cu-land-card {
            background: #fff;
            padding: 1rem;
            border-radius: 12px;
            border: 1px solid #eceff3;
            box-shadow: 0 6px 16px rgba(0,0,0,.05);
            display: flex;
            flex-direction: column;
            gap: .5rem;
            position: relative;
            min-height: 100%;
        }
        .cu-land-card__top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .5rem;
        }
        .cu-land-card__hit {
            flex: 1;
            min-width: 0;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            gap: .35rem;
        }
        .cu-land-card__hit:focus-visible {
            outline: 2px solid #E8B34B;
            outline-offset: 3px;
            border-radius: 8px;
        }
        .cu-land-card__hit:hover h3 { color: #7A2E1F; }
        .cu-land-card h3 {
            font-size: 1.15rem;
            font-weight: 800;
            color: #111827;
            margin: 0;
            line-height: 1.25;
        }
        .cu-land-card__badge {
            margin: 0;
            font-size: .82rem;
            font-weight: 600;
            color: #2563eb;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }
        .cu-land-card__badge-icon { opacity: .85; font-size: .95rem; }
        .cu-land-desc {
            margin: 0;
            font-size: .92rem;
            color: #374151;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 4;
            -webkit-box-orient: vertical;
            line-height: 1.4;
        }
        .cu-land-hit-media {
            margin-top: .35rem;
            border: none;
            background: none;
            padding: 0;
            width: 100%;
            cursor: pointer;
            text-align: left;
            font: inherit;
            color: inherit;
        }
        .cu-land-hit-media:focus-visible .media-box img {
            outline: 2px solid #E8B34B;
            outline-offset: 2px;
        }
        .media-box img, .media-box iframe { width: 100%; border-radius: 8px; display: block; }
        .cu-land-card .media-box { margin-top: auto; padding-top: .35rem; }

        .lm-card-menu-wrap { position: relative; flex-shrink: 0; z-index: 2; }
        .lm-card-menu-btn {
            width: 2.25rem;
            height: 2.25rem;
            padding: 0;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            background: #f3f4f6;
            color: #4b5563;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            line-height: 1;
            letter-spacing: 0;
            transition: background .15s, border-color .15s;
        }
        .lm-card-menu-btn:hover {
            background: #e5e7eb;
            border-color: #d1d5db;
            color: #111827;
        }
        .lm-card-menu {
            position: absolute;
            top: calc(100% + 4px);
            right: 0;
            min-width: 12.5rem;
            padding: .35rem 0;
            margin: 0;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-shadow: 0 10px 24px rgba(0,0,0,.1);
        }
        .lm-card-menu:not([hidden]) { display: block; }
        .lm-card-menu[hidden] { display: none !important; }
        .lm-card-menu-item {
            display: block;
            width: 100%;
            text-align: left;
            padding: .55rem 1rem;
            border: none;
            background: none;
            font-size: .9rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            font-family: inherit;
            color: #374151;
            box-sizing: border-box;
        }
        .lm-card-menu-item:hover { background: #f3f4f6; }
        .lm-card-menu-item--delete { color: #b91c1c; }
        .lm-card-menu-item--delete:hover { background: #fef2f2; }

        .empty-box {
            color: #6b7280;
            background: #fff;
            border: 1px dashed #d1d5db;
            border-radius: 10px;
            padding: .9rem 1rem;
        }
        .pager {
            margin-top: 1.15rem;
            display: flex;
            align-items: center;
            justify-content: flex-end;
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

        .cu-lm-detail-page {
            max-width: 2200px;
            margin: 0 auto;
            height: auto;
            max-height: none;
            overflow: visible;
        }
        .cu-qr-modal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 10000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(15, 23, 42, .6);
        }
        .cu-qr-modal.is-open { display: flex; }
        .cu-qr-modal__panel {
            width: min(620px, 96vw);
            overflow: hidden;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 20px 50px rgba(0, 0, 0, .3);
        }
        .cu-qr-modal__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .85rem 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .cu-qr-modal__header h2 { margin: 0; color: #7A2E1F; font-size: 1rem; }
        .cu-qr-modal__close {
            border: 0;
            background: none;
            color: #111827;
            font-size: 1.4rem;
            line-height: 1;
            cursor: pointer;
        }
        .cu-qr-modal__body { padding: 1rem; text-align: center; }
        .cu-qr-modal__image {
            display: block;
            width: min(500px, 82vw);
            max-width: 100%;
            max-height: 65vh;
            margin: 0 auto;
            object-fit: contain;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
        }
        .cu-qr-modal__unavailable {
            margin: 1rem 0;
            color: #6b7280;
            font-weight: 700;
        }
        .cu-qr-modal__download { margin-top: 1rem; }
    </style>


    <div class="land-wrap">
        <div class="land-header">
            <div class="land-header-main">
                <h1 class="land-title">Landmark</h1>
            </div>
        </div>

        @if (empty($landmark))
            <p class="empty-box">No landmark assigned.</p>
        @else
            @php
                $data = $landmark->data();
                $lid = $landmark->id();
            @endphp

            <div class="cu-lm-detail-page">
                @include('partials.landmark-detail-card', [
                    'landmarkId' => $lid,
                    'data' => $data,
                    'headerActionsView' => 'curators.landmarks.partials.detail-actions',
                    'headerActionsData' => [
                        'qrBase64' => $qrPreview['base64'] ?? '',
                        'qrUrl' => $qrPreview['url'] ?? '',
                        'qrFilename' => $qrPreview['filename'] ?? 'landmark-code-qr.png',
                    ],
                    'mapboxToken' => $mapboxToken ?? config('services.mapbox.token'),
                    'tipsReview' => $landmarkTips ?? [],
                ])
            </div>
        @endif
    </div>

    <div id="cu-qr-preview-modal" class="cu-qr-modal" role="dialog" aria-modal="true" aria-labelledby="cu-qr-preview-title">
        <div class="cu-qr-modal__panel">
            <div class="cu-qr-modal__header">
                <h2 id="cu-qr-preview-title">QR image preview</h2>
                <button id="cu-qr-preview-close" type="button" class="cu-qr-modal__close" aria-label="Close QR image preview">&times;</button>
            </div>
            <div class="cu-qr-modal__body">
                @if (! empty($qrPreview['url'] ?? ''))
                    <img id="cu-qr-preview-image" class="cu-qr-modal__image" src="{{ $qrPreview['url'] }}" alt="QR code image" hidden>
                    <a id="cu-qr-preview-download"
                       class="cu-lm-add-btn cu-qr-modal__download"
                       href="{{ $qrPreview['downloadUrl'] ?? $qrPreview['url'] }}"
                       download="{{ $qrPreview['filename'] ?? 'landmark-code-qr.png' }}"
                       hidden>
                        Download QR Image
                    </a>
                @endif
                <p id="cu-qr-preview-unavailable" class="cu-qr-modal__unavailable" hidden>No QR code has been generated for this landmark.</p>
            </div>
        </div>
    </div>

    <script>
        function cuOpenQrPreview(event, el) {
            if (event) {
                event.preventDefault();
            }
            var modal = document.getElementById('cu-qr-preview-modal');
            var image = document.getElementById('cu-qr-preview-image');
            var download = document.getElementById('cu-qr-preview-download');
            var unavailable = document.getElementById('cu-qr-preview-unavailable');
            if (!modal || !unavailable) {
                return;
            }
            modal.classList.add('is-open');
            document.body.style.overflow = 'hidden';

            if (image && download && image.getAttribute('src') !== '') {
                image.hidden = false;
                unavailable.hidden = true;
                download.hidden = false;
            } else {
                if (image) image.hidden = true;
                if (download) download.hidden = true;
                unavailable.hidden = false;
            }
        }
        function cuCloseQrPreview() {
            var modal = document.getElementById('cu-qr-preview-modal');
            var image = document.getElementById('cu-qr-preview-image');
            var download = document.getElementById('cu-qr-preview-download');
            var unavailable = document.getElementById('cu-qr-preview-unavailable');
            if (modal) modal.classList.remove('is-open');
            if (image) {
                image.hidden = true;
            }
            if (download) {
                download.hidden = true;
            }
            if (unavailable) unavailable.hidden = true;
            document.body.style.overflow = '';
        }
        function closeLandmarkMenus() {
            document.querySelectorAll('.lm-card-menu').forEach(function (menu) {
                menu.hidden = true;
            });
            document.querySelectorAll('.lm-card-menu-btn[aria-expanded="true"]').forEach(function (btn) {
                btn.setAttribute('aria-expanded', 'false');
            });
        }
        function toggleLandmarkMenu(event, btn) {
            event.stopPropagation();
            event.preventDefault();
            var wrap = btn.closest('.lm-card-menu-wrap');
            var menu = wrap ? wrap.querySelector('.lm-card-menu') : null;
            if (!menu) return;
            var willOpen = menu.hidden;
            closeLandmarkMenus();
            if (willOpen) {
                menu.hidden = false;
                btn.setAttribute('aria-expanded', 'true');
            }
        }
        document.addEventListener('click', function (event) {
            var qrModal = document.getElementById('cu-qr-preview-modal');
            if (event.target.id === 'cu-qr-preview-close' || event.target === qrModal) {
                cuCloseQrPreview();
            }
            if (!event.target.closest('.lm-card-menu-wrap')) {
                closeLandmarkMenus();
            }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') {
                return;
            }
            cuCloseQrPreview();
            closeLandmarkMenus();
        });
    </script>
@endsection
