@php
    use App\Support\LandmarkActivation;
    use App\Support\LandmarkVideo;
    use App\Support\LandmarkVisibility;

    $data = $data ?? [];
    $landmarkId = (string) ($landmarkId ?? '');
    $activationStatus = strtolower((string) ($data['activation_status'] ?? 'active'));
    $showVisibilityBadge = $showVisibilityBadge ?? true;
    $visibility = $showVisibilityBadge
        ? LandmarkVisibility::normalize($data['visibility'] ?? '', $activationStatus)
        : null;

    $latOut = $data['latitude'] ?? $data['lati'] ?? null;
    $lngOut = $data['longitude'] ?? $data['longti'] ?? null;
    $latDisplay = ($latOut !== null && $latOut !== '') ? $latOut : 'N/A';
    $lngDisplay = ($lngOut !== null && $lngOut !== '') ? $lngOut : 'N/A';
    $hasCoords = is_numeric($latOut) && is_numeric($lngOut);
    $mapContainerId = $landmarkId !== ''
        ? 'lm-detail-map-' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $landmarkId)
        : 'lm-detail-map';

    $videoFileUrl = LandmarkVideo::url($data);
    $imageSrc = null;

    if (! empty($data['image_url'] ?? null)) {
        $imageSrc = $data['image_url'];
    } elseif (! empty($data['image_base64'] ?? null)) {
        $imageMime = $data['image_mime'] ?? 'image/jpeg';
        $imageSrc = str_starts_with($data['image_base64'], 'data:')
            ? $data['image_base64']
            : 'data:' . $imageMime . ';base64,' . $data['image_base64'];
    }

    $idChipValue = trim((string) ($data['landmarkcode'] ?? ''));
    if ($idChipValue === '' && $landmarkId !== '') {
        $idChipValue = $landmarkId;
    }
@endphp

@once
    <style>
        .lm-detail-card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid rgba(122, 46, 31, 0.08);
            box-shadow:
                0 1px 2px rgba(15, 23, 42, 0.04),
                0 12px 40px rgba(122, 46, 31, 0.06);
            padding: 1.75rem 1.75rem 2rem;
            height: auto;
            max-height: none;
            overflow: visible;
        }
        @media (min-width: 640px) {
            .lm-detail-card { padding: 2rem 2.25rem 2.25rem; }
        }
        .lm-detail-card__top {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem 1.25rem;
            margin-bottom: 1rem;
        }
        .lm-detail-card__top-main {
            flex: 1;
            min-width: min(100%, 12rem);
        }
        .lm-detail-card__actions {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem .65rem;
            align-items: center;
            justify-content: flex-end;
            flex-shrink: 0;
        }
        .lm-detail-card__meta-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .65rem 1rem;
            margin-bottom: .5rem;
        }
        .lm-detail-card__eyebrow {
            margin: 0;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #A67C52;
            flex-shrink: 0;
        }
        .lm-detail-card__title {
            margin: 0 0 1.15rem;
            font-size: clamp(1.35rem, 3vw, 1.85rem);
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #4c1d95;
            line-height: 1.2;
        }
        .lm-detail-card__chips {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem .55rem;
            align-items: center;
        }
        .lm-detail-chip {
            display: inline-flex;
            align-items: baseline;
            gap: .35rem;
            padding: .35rem .65rem;
            border-radius: 999px;
            font-size: .78rem;
            border: 1px solid #ece8e4;
            background: #fff;
            color: #57534e;
        }
        .lm-detail-chip__k {
            font-weight: 600;
            color: #44403c;
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .06em;
        }
        .lm-detail-chip__v {
            font-family: ui-monospace, monospace;
            font-size: .76rem;
            word-break: break-all;
        }
        .lm-detail-chip--coord {
            border-color: #c7d2fe;
            background: #eef2ff;
            color: #3730a3;
        }
        .lm-detail-status {
            display: inline-flex;
            align-items: center;
            padding: .35rem .72rem;
            border-radius: 999px;
            font-size: .78rem;
            font-weight: 700;
            border: 1px solid transparent;
        }
        .lm-detail-status--pending { background: #fffbeb; color: #b45309; border-color: #fde68a; }
        .lm-detail-status--active { background: #ecfdf5; color: #166534; border-color: #bbf7d0; }
        .lm-detail-status--rejected { background: #fef2f2; color: #991b1b; border-color: #fecaca; }
        .lm-detail-visibility--published { background:#ecfdf5; color:#166534; border-color:#bbf7d0; }
        .lm-detail-visibility--archived { background:#f3f4f6; color:#4b5563; border-color:#d1d5db; }
        .lm-detail-visibility--hidden { background:#eef2ff; color:#4338ca; border-color:#c7d2fe; }
        .lm-detail-card__section {
            margin: 1.35rem 0 .45rem;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: #7A2E1F;
        }
        .lm-detail-card__section:first-of-type { margin-top: 0; }
        .lm-detail-card__desc {
            margin: 0;
            font-size: .95rem;
            line-height: 1.65;
            color: #44403c;
            white-space: pre-wrap;
        }
        .lm-detail-media-grid {
            display: grid;
            gap: .65rem;
        }
        @media (min-width: 640px) {
            .lm-detail-media-grid--two { grid-template-columns: 1fr 1fr; }
        }
        .lm-detail-media-frame {
            display: flex;
            flex-direction: column;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #e7e5e4;
            background: #f5f5f4;
            margin: 0;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
        }
        .lm-detail-media-frame > img {
            width: 100%;
            display: block;
            aspect-ratio: 16 / 10;
            object-fit: cover;
        }
        .lm-detail-media-frame__cap {
            padding: .3rem .55rem;
            font-size: .68rem;
            font-weight: 600;
            color: #57534e;
            background: #fafaf9;
            border-top: 1px solid #e7e5e4;
        }
        .lm-detail-ratio {
            position: relative;
            width: 100%;
            padding-bottom: 56.25%;
            height: 0;
            background: #1c1917;
        }
        .lm-detail-ratio iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
        }
        .lm-detail-video-card {
            border-radius: 10px;
            border: 1px dashed #d6d3d1;
            background: #fafaf9;
            padding: 1rem;
            text-align: center;
        }
        .lm-detail-video-card p {
            margin: 0 0 .65rem;
            font-size: .88rem;
            color: #57534e;
        }
        .lm-detail-video-card__btn {
            display: inline-flex;
            padding: .5rem 1rem;
            border-radius: 8px;
            font-weight: 700;
            font-size: .875rem;
            text-decoration: none;
            background: #7A2E1F;
            color: #fffdf7;
        }
        .lm-detail-card__pending-note {
            margin: 1.15rem 0 0;
            font-size: .9rem;
            color: #92400e;
            line-height: 1.5;
        }
        .lm-detail-approval-actions {
            margin-top: 1.15rem;
            padding-top: 1rem;
            border-top: 1px solid #e7e5e4;
            display: flex;
            flex-wrap: wrap;
            gap: .55rem;
        }
        .lm-detail-btn-approve {
            padding: .55rem 1rem;
            border-radius: 8px;
            border: 1px solid #bbf7d0;
            background: #ecfdf5;
            color: #166534;
            font-weight: 700;
            cursor: pointer;
        }
        .lm-detail-btn-reject {
            padding: .55rem 1rem;
            border-radius: 8px;
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #991b1b;
            font-weight: 700;
            cursor: pointer;
        }
    </style>
@endonce

<article class="lm-detail-card" aria-label="Landmark detail">
    <div class="lm-detail-card__top">
        <div class="lm-detail-card__top-main">
            <div class="lm-detail-card__meta-row">
                <p class="lm-detail-card__eyebrow">Landmark detail</p>
                <div class="lm-detail-card__chips" aria-label="Landmark metadata">
                    @if ($idChipValue !== '')
                        <span class="lm-detail-chip">
                            <span class="lm-detail-chip__k">ID</span>
                            <span class="lm-detail-chip__v">{{ $idChipValue }}</span>
                        </span>
                    @endif
                    <span class="lm-detail-chip lm-detail-chip--coord">
                        <span class="lm-detail-chip__k">Location</span>
                        <span class="lm-detail-chip__v">{{ $latDisplay }}, {{ $lngDisplay }}</span>
                    </span>
                    <span class="lm-detail-status lm-detail-status--{{ $activationStatus === 'pending' || $activationStatus === 'rejected' ? $activationStatus : 'active' }}">
                        {{ LandmarkActivation::label($activationStatus) }}
                    </span>
                    @if ($showVisibilityBadge)
                        <span class="lm-detail-status lm-detail-visibility--{{ $visibility }}">
                            {{ LandmarkVisibility::label($visibility) }}
                        </span>
                    @endif
                </div>
            </div>
            <h1 class="lm-detail-card__title">{{ $data['name'] ?? 'Unnamed landmark' }}</h1>
        </div>
        @if (! empty($headerActionsView))
            <div class="lm-detail-card__actions" aria-label="Landmark actions">
                @include($headerActionsView, array_merge([
                    'landmarkId' => $landmarkId,
                    'landmarkName' => $data['name'] ?? 'Unnamed landmark',
                ], $headerActionsData ?? []))
            </div>
        @endif
    </div>

    @if (! empty($data['description'] ?? ''))
        <h2 class="lm-detail-card__section">Description</h2>
        <p class="lm-detail-card__desc">{{ $data['description'] }}</p>
    @endif

    @if ($hasCoords)
        <h2 class="lm-detail-card__section">Location</h2>
        @include('partials.landmark-map-embed', [
            'latitude' => $latOut,
            'longitude' => $lngOut,
            'mapContainerId' => $mapContainerId,
            'landmarkName' => $data['name'] ?? 'Landmark',
            'mapboxToken' => $mapboxToken ?? null,
        ])
    @endif

    @if ($imageSrc || $videoFileUrl !== '')
        <h2 class="lm-detail-card__section">Photos &amp; media</h2>
        <div class="lm-detail-media-grid @if ($imageSrc && $videoFileUrl !== '') lm-detail-media-grid--two @endif">
            @if ($imageSrc)
                <figure class="lm-detail-media-frame">
                    <img src="{{ $imageSrc }}" alt="Photo of {{ $data['name'] ?? 'landmark' }}">
                    <figcaption class="lm-detail-media-frame__cap">Featured image</figcaption>
                </figure>
            @endif

            @if ($videoFileUrl !== '')
                <div class="lm-detail-media-frame">
                    <video controls preload="metadata" style="width:100%;display:block;background:#1c1917;">
                        <source src="{{ $videoFileUrl }}" type="{{ $data['video_mime'] ?? 'video/mp4' }}">
                    </video>
                    <div class="lm-detail-media-frame__cap">Video</div>
                </div>
            @endif
        </div>
    @endif

    @if (($canApproveLandmark ?? false) && ($showApprovalActions ?? true))
        <div class="lm-detail-approval-actions" aria-label="Landmark approval">
            <form method="POST"
                  action="{{ route('admin.landmarks.approve', $landmarkId) }}"
                  onsubmit="return confirm('Approve and publish this landmark?');">
                @csrf
                <button type="submit" class="lm-detail-btn-approve">Approve landmark</button>
            </form>
            <form method="POST"
                  action="{{ route('admin.landmarks.reject', $landmarkId) }}"
                  onsubmit="return confirm('Reject this landmark submission? The Site Manager will need to submit again.');">
                @csrf
                <button type="submit" class="lm-detail-btn-reject">Reject</button>
            </form>
        </div>
    @elseif (($showPendingNote ?? false) && $activationStatus === 'pending')
        <p class="lm-detail-card__pending-note">
            This landmark is awaiting administrator review. After approval, create the site QR code in QR Codes using the landmark code.
        </p>
    @endif
</article>
