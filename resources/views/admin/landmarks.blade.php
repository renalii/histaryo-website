@extends('layouts.sidebar')

@php
    use App\Services\LandmarkImageStorage;
    use App\Support\LandmarkActivation;
    use App\Support\LandmarkVideo;
    use App\Support\LandmarkVisibility;
    $landmarkCount = method_exists($landmarks, 'total') ? $landmarks->total() : $landmarks->count();
    $panelRoutePrefix = session('role') === 'site_manager' ? 'sitemanager' : 'admin';
    $currentView = $panelRoutePrefix === 'sitemanager' ? 'card' : request()->get('view', 'card');
    $isLandmarkApprovalQueue = $isLandmarkApprovalQueue ?? false;
    $landmarkStatusFilter = $landmarkStatusFilter ?? 'all';
    $landmarksListUrl = route($panelRoutePrefix . '.landmarks', array_filter([
        'view' => request()->get('view'),
        'status' => $isLandmarkApprovalQueue ? $landmarkStatusFilter : null,
    ]));
    $mapboxToken = config('services.mapbox.token');
    $createMapLat = is_numeric(old('latitude')) ? (float) old('latitude') : 10.3157;
    $createMapLng = is_numeric(old('longitude')) ? (float) old('longitude') : 123.8854;
@endphp

@section('content')
    @if ($panelRoutePrefix === 'sitemanager' && $mapboxToken)
        <link href="https://api.mapbox.com/mapbox-gl-js/v3.6.0/mapbox-gl.css" rel="stylesheet">
    @endif
    @if (session('status') || session('status_err'))
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
        @if (session('status'))
            <div class="flash-ok-lm">{{ session('status') }}</div>
        @endif
        @if (session('status_err'))
            <div class="flash-ok-lm" style="background:#fef2f2;color:#991b1b;border-color:#fecaca;">{{ session('status_err') }}</div>
        @endif
    @endif
    <style>
        .land-wrap { max-width: 2000px; margin: 0 auto; }
        .land-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.25rem;
            margin-bottom: .25rem;
            flex-wrap: nowrap;
        }
        .land-header-main {
            display: flex;
            flex-direction: column;
            gap: .1rem;
            min-width: 0;
            flex: 1 1 auto;
        }
        .land-header-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            align-items: center;
            justify-content: flex-end;
            flex-shrink: 0;
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
                flex-wrap: wrap;
            }
            .land-header-actions {
                justify-content: flex-start;
                width: 100%;
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
        .land-card h3 { font-size:1.2rem; color:#111827; margin:0; text-decoration:none; }
        .meta { margin:0; font-size:.9rem; color:#4b5563; }
        .land-card-section-title {
            margin:0 0 .2rem;
            font-size:.78rem;
            font-weight:700;
            color:#92400e;
            text-transform:uppercase;
        }
        .land-card-location {
            margin:0;
        }
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
        .media-box img, .media-box iframe, .media-box video {
            width:100%;
            border-radius:8px;
            display:block;
            aspect-ratio:16 / 10;
            object-fit:cover;
            background:#f3f4f6;
        }
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
        .list-media-row .media-box iframe,
        .list-media-row .media-box video {
            width: 100% !important;
            height: 350px !important;
            border-radius: 0 !important;
            display: block;
            object-fit: cover;
        }
        .list-media-row .media-box:first-child img,
        .list-media-row .media-box:first-child iframe,
        .list-media-row .media-box:first-child video {
            border-top-left-radius: 8px !important;
            border-bottom-left-radius: 8px !important;
        }
        .list-media-row .media-box:last-child img,
        .list-media-row .media-box:last-child iframe,
        .list-media-row .media-box:last-child video {
            border-top-right-radius: 8px !important;
            border-bottom-right-radius: 8px !important;
        }
        .list-media-row .media-box:only-child img,
        .list-media-row .media-box:only-child iframe,
        .list-media-row .media-box:only-child video {
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

        .lm-create-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            inset: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.55);
            padding: 1.25rem;
            align-items: center;
            justify-content: center;
        }
        .lm-create-modal__panel {
            background: #fefefe;
            margin: auto;
            padding: 1.5rem 1.85rem;
            border-radius: 14px;
            max-width: min(520px, 100%);
            width: 100%;
            max-height: min(92vh, 720px);
            overflow-y: auto;
            box-shadow: 0 16px 40px rgba(0,0,0,.12);
            position: relative;
            font-family: inherit;
        }
        .lm-create-modal__panel h3 {
            margin: 0 2rem .5rem 0;
            font-size: 1.35rem;
            font-weight: 700;
            color: #4c1d95;
        }
        .lm-create-modal__panel label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-top: .85rem;
            margin-bottom: .35rem;
            font-size: .86rem;
        }
        .lm-create-modal__panel input[type="text"],
        .lm-create-modal__panel input[type="url"],
        .lm-create-modal__panel input[type="file"],
        .lm-create-modal__panel textarea,
        .lm-create-modal__panel select {
            width: 100%;
            padding: .5rem .75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #f9fafb;
            font-size: .875rem;
            color: #111827;
            box-sizing: border-box;
            font-family: inherit;
        }
        .lm-create-modal__panel button[type="submit"] {
            margin-top: 1.35rem;
            background: #E8B34B;
            color: #7A2E1F;
            padding: .55rem 1.1rem;
            font-size: .9rem;
            border-radius: 8px;
            font-weight: 700;
            border: 1px solid #F3C96A;
            cursor: pointer;
        }
        .lm-create-modal__panel button[type="submit"]:hover { background: #F3C96A; }
        .lm-create-modal__close {
            position: absolute;
            top: 10px;
            right: 14px;
            font-size: 28px;
            font-weight: bold;
            color: #6b7280;
            cursor: pointer;
            line-height: 1;
            border: none;
            background: none;
            padding: .25rem .4rem;
            font-family: inherit;
        }
        .lm-create-modal__close:hover,
        .lm-create-modal__close:focus-visible { color: #111827; }
        .lm-create-modal__hint {
            margin: .35rem 0 0;
            font-size: .78rem;
            color: #6b7280;
            line-height: 1.4;
        }
        .lm-create-modal__errors {
            margin: 0 0 .5rem;
            padding: .65rem .85rem;
            border-radius: 8px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            font-size: .85rem;
        }
        .lm-create-modal__errors ul {
            margin: .25rem 0 0;
            padding-left: 1.15rem;
        }
        .lm-create-location-map-wrap {
            position: relative;
            width: 100%;
            overflow: hidden;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #eef2f7;
        }
        .lm-create-location-map {
            width: 100%;
            height: 260px;
            overflow: hidden;
        }
        .lm-create-location-map .mapboxgl-canvas {
            outline: none;
        }
        .lm-create-location-search {
            position: absolute;
            z-index: 5;
            top: .75rem;
            left: .75rem;
            width: min(310px, calc(100% - 1.5rem));
            padding: .55rem;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .18);
            box-sizing: border-box;
        }
        .lm-create-location-search label {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        .lm-create-location-search input[type="search"] {
            width: 100%;
            min-width: 0;
            padding: .5rem .65rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: #f9fafb;
            color: #111827;
            font: inherit;
            font-size: .86rem;
            box-sizing: border-box;
            outline: none;
        }
        .lm-create-location-search input[type="search"]:focus {
            border-color: #7A2E1F;
            box-shadow: 0 0 0 3px rgba(122, 46, 31, .14);
            background: #fff;
        }
        @media (max-width: 520px) {
            .lm-create-location-search {
                top: .55rem;
                left: .55rem;
                width: calc(100% - 1.1rem);
                padding: .45rem;
            }
            .lm-create-location-map {
                height: 240px;
            }
        }
        .land-activation-pill {
            display: inline-block;
            font-size: .72rem;
            font-weight: 700;
            padding: .2rem .55rem;
            border-radius: 999px;
            margin-top: .25rem;
        }
        .land-activation-pill--pending { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
        .land-activation-pill--active { background: #ecfdf5; color: #166534; border: 1px solid #bbf7d0; }
        .land-activation-pill--rejected { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .land-card-badges {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: .35rem;
        }
        .land-card-badges .land-activation-pill {
            margin-top: 0;
        }
        .land-visibility-pill {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            font-size: .72rem;
            font-weight: 700;
            padding: .2rem .55rem;
            border-radius: 999px;
            border: 1px solid transparent;
        }
        .land-visibility-pill--published { background:#ecfdf5; color:#166534; border-color:#bbf7d0; }
        .land-visibility-pill--archived { background:#f3f4f6; color:#4b5563; border-color:#d1d5db; }
        .land-visibility-pill--hidden { background:#eef2ff; color:#4338ca; border-color:#c7d2fe; }
        .lm-visibility-form { display:flex; gap:.55rem; align-items:end; flex-wrap:wrap; padding:.8rem; border:1px solid #e5e7eb; border-radius:10px; background:#f9fafb; }
        .lm-visibility-form label { display:grid; gap:.3rem; font-size:.72rem; font-weight:700; color:#57534e; text-transform:uppercase; }
        .lm-visibility-form select { min-width:160px; padding:.5rem .65rem; border:1px solid #d1d5db; border-radius:8px; background:#fff; }
        .lm-visibility-form button { padding:.52rem .8rem; border:1px solid #F3C96A; border-radius:8px; background:#E8B34B; color:#7A2E1F; font-weight:700; cursor:pointer; }
        .land-card-link {
            color: inherit;
            text-decoration: none;
        }
        .land-card-link:hover h3 { text-decoration: none; }
        .land-card--clickable {
            cursor: pointer;
            transition: box-shadow .15s ease, border-color .15s ease;
        }
        .land-card--clickable:hover {
            border-color: #e8d4a8;
            box-shadow: 0 8px 22px rgba(122, 46, 31, 0.1);
        }
        .land-card--clickable:focus-visible {
            outline: 2px solid #E8B34B;
            outline-offset: 2px;
        }
        .land-card--clickable:hover h3 { text-decoration: none; }
        .lm-view-modal {
            display: none;
            position: fixed;
            z-index: 1100;
            inset: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.55);
            padding: 1.25rem;
            align-items: center;
            justify-content: center;
        }
        .lm-view-modal__panel {
            background: #fff;
            margin: auto;
            padding: 1.5rem 1.75rem 1.75rem;
            border-radius: 14px;
            max-width: min(720px, 100%);
            width: 100%;
            max-height: min(90vh, 880px);
            overflow-y: auto;
            box-shadow: 0 16px 40px rgba(0,0,0,.12);
            position: relative;
            font-family: inherit;
        }
        .lm-view-modal__close {
            position: absolute;
            top: 10px;
            right: 14px;
            font-size: 28px;
            font-weight: bold;
            color: #6b7280;
            cursor: pointer;
            line-height: 1;
            border: none;
            background: none;
            padding: .25rem .4rem;
            font-family: inherit;
        }
        .lm-view-modal__close:hover,
        .lm-view-modal__close:focus-visible { color: #111827; }
        .lm-view-modal__eyebrow {
            margin: 0 0 .35rem;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #A67C52;
        }
        .lm-view-modal__title {
            margin: 0 2.35rem 1rem 0;
            font-size: clamp(1.35rem, 3vw, 1.65rem);
            font-weight: 800;
            color: #4c1d95;
            line-height: 1.2;
            letter-spacing: -0.02em;
        }
        .lm-view-modal__chips {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem .55rem;
            align-items: center;
            margin-bottom: 1.15rem;
        }
        .lm-view-chip {
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
        .lm-view-chip__k {
            font-weight: 600;
            color: #44403c;
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .06em;
        }
        .lm-view-chip__v {
            font-family: ui-monospace, monospace;
            font-size: .76rem;
            word-break: break-all;
        }
        .lm-view-chip--coord {
            border-color: #c7d2fe;
            background: #eef2ff;
            color: #3730a3;
        }
        .lm-view-status {
            display: inline-flex;
            align-items: center;
            padding: .35rem .72rem;
            border-radius: 999px;
            font-size: .78rem;
            font-weight: 700;
            border: 1px solid transparent;
        }
        .lm-view-status--pending { background: #fffbeb; color: #b45309; border-color: #fde68a; }
        .lm-view-status--active { background: #ecfdf5; color: #166534; border-color: #bbf7d0; }
        .lm-view-status--rejected { background: #fef2f2; color: #991b1b; border-color: #fecaca; }
        .lm-view-modal__section {
            margin: 1.1rem 0 .45rem;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: #7A2E1F;
        }
        .lm-view-modal__desc {
            margin: 0;
            font-size: .95rem;
            line-height: 1.65;
            color: #44403c;
            white-space: pre-wrap;
        }
        .lm-view-modal__muted {
            margin: 0;
            font-size: .9rem;
            color: #78716c;
        }
        .lm-view-modal__pending-note {
            margin: 1.15rem 0 0;
            font-size: .9rem;
            color: #92400e;
            line-height: 1.5;
        }
        .lm-view-media-grid {
            display: grid;
            gap: .65rem;
        }
        @media (min-width: 640px) {
            .lm-view-media-grid--two { grid-template-columns: 1fr 1fr; }
        }
        .lm-view-media-frame {
            display: flex;
            flex-direction: column;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #e7e5e4;
            background: #f5f5f4;
            margin: 0;
        }
        .lm-view-media-frame > img {
            width: 100%;
            display: block;
            aspect-ratio: 16 / 10;
            object-fit: cover;
        }
        .lm-view-media-frame__cap {
            padding: .3rem .55rem;
            font-size: .68rem;
            font-weight: 600;
            color: #57534e;
            background: #fafaf9;
            border-top: 1px solid #e7e5e4;
        }
        .lm-view-ratio {
            position: relative;
            width: 100%;
            padding-bottom: 56.25%;
            height: 0;
            background: #1c1917;
        }
        .lm-view-ratio iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
        }
        .lm-view-video-card {
            border-radius: 10px;
            border: 1px dashed #d6d3d1;
            background: #fafaf9;
            padding: 1rem;
            text-align: center;
        }
        .lm-view-video-card p {
            margin: 0 0 .65rem;
            font-size: .88rem;
            color: #57534e;
        }
        .lm-view-video-card__btn {
            display: inline-flex;
            padding: .5rem 1rem;
            border-radius: 8px;
            font-weight: 700;
            font-size: .875rem;
            text-decoration: none;
            background: #7A2E1F;
            color: #fffdf7;
        }
        .lm-view-evidence-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: .55rem;
        }
        .lm-view-evidence-item {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            padding: .65rem .85rem;
            border: 1px solid #e7e5e4;
            border-radius: 10px;
            background: #fafaf9;
        }
        .lm-view-evidence-item a {
            font-weight: 600;
            color: #7A2E1F;
            text-decoration: none;
        }
        .lm-view-evidence-item a:hover { text-decoration: underline; }
        .lm-view-approval-actions {
            margin-top: 1.15rem;
            padding-top: 1rem;
            border-top: 1px solid #e7e5e4;
            display: flex;
            flex-wrap: wrap;
            gap: .55rem;
        }
        .lm-view-btn-approve {
            padding: .55rem 1rem;
            border-radius: 8px;
            border: 1px solid #bbf7d0;
            background: #ecfdf5;
            color: #166534;
            font-weight: 700;
            cursor: pointer;
            font-size: .875rem;
        }
        .lm-view-btn-reject {
            padding: .55rem 1rem;
            border-radius: 8px;
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #991b1b;
            font-weight: 700;
            cursor: pointer;
            font-size: .875rem;
        }
        .land-table .row-name-btn {
            border: none;
            background: none;
            padding: 0;
            font: inherit;
            color: #7A2E1F;
            font-weight: 600;
            cursor: pointer;
            text-align: left;
            text-decoration: none;
        }
        .land-table .row-name-btn:hover { text-decoration: none; }
        .land-status-tabs {
            display: inline-flex;
            flex-wrap: wrap;
            gap: .35rem;
            background: #fff;
            border: 1px solid #eceff3;
            border-radius: 10px;
            padding: .35rem;
        }
        .land-status-tabs a {
            padding: .4rem .75rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: .85rem;
            color: #374151;
        }
        .land-status-tabs a.active {
            background: #E8B34B;
            color: #7A2E1F;
        }
    </style>
    <div class="land-wrap">
    <div class="land-header">
        <div class="land-header-main">
            <h2 class="land-title">{{ $isLandmarkApprovalQueue ? 'Landmark approvals' : 'All Landmarks' }}</h2>
            @if ($panelRoutePrefix !== 'sitemanager' && ! $isLandmarkApprovalQueue)
                <p class="land-sub">
                    {{ $landmarkCount }} submission{{ $landmarkCount !== 1 ? 's' : '' }}.
                </p>
            @endif
        </div>

        <div class="land-header-actions">
            @if ($panelRoutePrefix === 'sitemanager')
                <button type="button"
                        class="view-btn active"
                        onclick="lmOpenCreateModal()"
                        aria-haspopup="dialog"
                        aria-controls="lmCreateLandmarkModal">
                    + Create landmark
                </button>
            @endif
            @if ($isLandmarkApprovalQueue)
                <div class="land-status-tabs" role="tablist" aria-label="Landmark status">
                    @foreach (['pending' => 'Pending', 'active' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $statusKey => $statusLabel)
                        <a href="{{ route('admin.landmarks', ['status' => $statusKey, 'view' => $currentView]) }}"
                           class="{{ $landmarkStatusFilter === $statusKey ? 'active' : '' }}">{{ $statusLabel }}</a>
                    @endforeach
                </div>
            @endif
            @if ($panelRoutePrefix !== 'sitemanager')
                <div class="view-switch">
                    <a href="{{ route($panelRoutePrefix . '.landmarks', array_filter(['view' => 'card', 'status' => $isLandmarkApprovalQueue ? $landmarkStatusFilter : null])) }}"
                       class="view-btn {{ $currentView === 'card' ? 'active' : '' }}">
                        Card View
                    </a>
                    <a href="{{ route($panelRoutePrefix . '.landmarks', array_filter(['view' => 'list', 'status' => $isLandmarkApprovalQueue ? $landmarkStatusFilter : null])) }}"
                       class="view-btn {{ $currentView === 'list' ? 'active' : '' }}">
                        List View
                    </a>
                </div>
            @endif
        </div>
    </div>

    @if ($landmarks->isEmpty())
        <p class="empty-box">{{ $isLandmarkApprovalQueue ? 'No landmarks in this queue.' : 'No landmarks found.' }}</p>
    @else

        
        @if ($currentView === 'card')
            <div id="card-view" class="card-grid">
                @foreach ($landmarks as $landmark)
                    @php
                        $lid = $landmark->id();
                        $modalSafe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $lid);
                        $viewModalId = 'viewModal_' . $modalSafe;
                        $data = $landmark->data();
                        $videoFileUrl = LandmarkVideo::url($data);
                        $imageSrc = null;
                        $storedImageUrl = $data['image_url'] ?? LandmarkImageStorage::publicUrl($lid);

                        if (!empty($data['image_base64']) || $storedImageUrl !== null) {
                            $imageSrc = $storedImageUrl ?? route($panelRoutePrefix . '.landmarks.image', $lid);
                        }

                        $activation = strtolower((string) ($data['activation_status'] ?? 'active'));
                        $visibility = LandmarkVisibility::normalize($data['visibility'] ?? '', $activation);
                        $activationLabel = $isLandmarkApprovalQueue
                            ? match ($activation) {
                                'pending' => 'Pending',
                                'active' => 'Approved',
                                'rejected' => 'Rejected',
                                default => LandmarkActivation::label($activation),
                            }
                            : LandmarkActivation::label($activation);
                        $useViewModal = true;
                        $canApproveLandmark = $isLandmarkApprovalQueue
                            && $activation === 'pending';
                    @endphp

                    <div class="land-card {{ $useViewModal ? 'land-card--clickable' : '' }}"
                         @if ($useViewModal)
                             role="button"
                             tabindex="0"
                             onclick="smOpenLandmarkViewModal('{{ $viewModalId }}', '{{ $lid }}')"
                             onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); smOpenLandmarkViewModal('{{ $viewModalId }}', '{{ $lid }}'); }"
                             aria-haspopup="dialog"
                             aria-controls="{{ $viewModalId }}"
                         @endif>
                        @if ($useViewModal)
                            <h3>{{ $data['name'] ?? 'Unnamed Landmark' }}</h3>
                            <div class="land-card-badges">
                                <span class="land-activation-pill land-activation-pill--{{ $activation === 'pending' || $activation === 'rejected' ? $activation : 'active' }}">
                                    {{ $activationLabel }}
                                </span>
                                @unless ($isLandmarkApprovalQueue)
                                    <span class="land-visibility-pill land-visibility-pill--{{ $visibility }}">{{ LandmarkVisibility::label($visibility) }}</span>
                                @endunless
                            </div>
                        @else
                            <a class="land-card-link" href="{{ $showRoute }}">
                                <h3>{{ $data['name'] ?? 'Unnamed Landmark' }}</h3>
                                <div class="land-card-badges">
                                    <span class="land-activation-pill land-activation-pill--{{ $activation === 'pending' || $activation === 'rejected' ? $activation : 'active' }}">
                                        {{ $activationLabel }}
                                    </span>
                                    @unless ($isLandmarkApprovalQueue)
                                        <span class="land-visibility-pill land-visibility-pill--{{ $visibility }}">{{ LandmarkVisibility::label($visibility) }}</span>
                                    @endunless
                                </div>
                            </a>
                        @endif
                        @if ($panelRoutePrefix !== 'sitemanager' && ! $isLandmarkApprovalQueue && ! empty($data['landmarkcode'] ?? ''))
                            <p class="meta" style="font-family:ui-monospace,monospace;font-weight:600;color:#92400e;">{{ $data['landmarkcode'] }}</p>
                        @endif

                        @if (!empty($data['description']))
                            <div @if ($useViewModal) onclick="event.stopPropagation()" @endif>
                                <p class="land-card-section-title">Description</p>
                                <p class="desc">{{ $data['description'] }}</p>
                            </div>
                        @endif

                        <div class="land-card-location">
                            <p class="land-card-section-title">Location</p>
                            <p class="meta">
                                Latitude: {{ $data['latitude'] ?? 'N/A' }}<br>
                                Longitude: {{ $data['longitude'] ?? 'N/A' }}
                            </p>
                        </div>

                        @if (!empty($imageSrc) || !empty($videoFileUrl))
                            <p class="land-card-section-title">Photos &amp; Media</p>
                        @endif

                        @if (!empty($imageSrc))
                            <div class="media-box" @if ($useViewModal) onclick="event.stopPropagation()" @endif>
                                <img src="{{ $imageSrc }}" alt="Landmark Image" loading="lazy" decoding="async">
                            </div>
                        @endif

                        @if (!empty($videoFileUrl))
                            <div class="media-box" @if ($useViewModal) onclick="event.stopPropagation()" @endif>
                                <video controls preload="metadata">
                                    <source src="{{ $videoFileUrl }}" type="{{ $data['video_mime'] ?? 'video/mp4' }}">
                                </video>
                            </div>
                        @endif
                    </div>

                    @if ($useViewModal)
                        @include('admin.partials.landmark-view-modal', [
                            'viewModalId' => $viewModalId,
                            'modalSafe' => $modalSafe,
                            'landmarkId' => $lid,
                            'data' => $data,
                            'imageSrc' => $imageSrc,
                            'videoFileUrl' => $videoFileUrl,
                            'canApproveLandmark' => $canApproveLandmark,
                            'panelRoutePrefix' => $panelRoutePrefix,
                        ])
                    @endif
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
                                $lid = $landmark->id();
                                $modalSafe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $lid);
                                $viewModalId = 'viewModal_' . $modalSafe;
                                $data = $landmark->data();
                                $videoFileUrl = LandmarkVideo::url($data);
                                $imageSrc = null;
                                $storedImageUrl = $data['image_url'] ?? LandmarkImageStorage::publicUrl($lid);

                                if (!empty($data['image_base64']) || $storedImageUrl !== null) {
                                    $imageSrc = $storedImageUrl ?? route($panelRoutePrefix . '.landmarks.image', $lid);
                                }

                                $activation = strtolower((string) ($data['activation_status'] ?? 'active'));
                                $visibility = LandmarkVisibility::normalize($data['visibility'] ?? '', $activation);
                                $activationLabel = $isLandmarkApprovalQueue
                                    ? match ($activation) {
                                        'pending' => 'Pending',
                                        'active' => 'Approved',
                                        'rejected' => 'Rejected',
                                        default => LandmarkActivation::label($activation),
                                    }
                                    : LandmarkActivation::label($activation);
                                $useViewModal = true;
                                $canApproveLandmark = $isLandmarkApprovalQueue
                                    && $activation === 'pending';
                            @endphp

                            <tr class="row-main" onclick="toggleRow({{ $index }})">
                                <td>
                                    @if ($useViewModal)
                                        <button type="button"
                                                class="row-name-btn"
                                                onclick="event.stopPropagation(); smOpenLandmarkViewModal('{{ $viewModalId }}', '{{ $lid }}')">
                                            {{ $data['name'] ?? 'Unnamed Landmark' }}
                                        </button>
                                    @else
                                        <a href="{{ route($panelRoutePrefix . '.landmarks.show', $lid) }}" style="color:#7A2E1F;font-weight:600;text-decoration:none;">
                                            {{ $data['name'] ?? 'Unnamed Landmark' }}
                                        </a>
                                    @endif
                                    <br>
                                    <span class="land-activation-pill land-activation-pill--{{ $activation === 'pending' || $activation === 'rejected' ? $activation : 'active' }}">
                                        {{ $activationLabel }}
                                    </span>
                                    @unless ($isLandmarkApprovalQueue)
                                        <span class="land-visibility-pill land-visibility-pill--{{ $visibility }}">{{ LandmarkVisibility::label($visibility) }}</span>
                                    @endunless
                                </td>
                                <td style="font-family:ui-monospace,monospace;">{{ $data['landmarkcode'] ?? '—' }}</td>
                                <td>{{ $data['latitude'] ?? 'N/A' }}, {{ $data['longitude'] ?? 'N/A' }}</td>
                                <td class="expand-text">Click to expand</td>
                            </tr>
                            <tr id="expand-{{ $index }}" class="row-expanded" style="display: none;">
                                <td colspan="4">
                                    <div class="row-content">
                                    @if (!empty($imageSrc) || !empty($videoFileUrl))
                                        <div class="{{ (!empty($imageSrc) && !empty($videoFileUrl)) ? 'list-media-row' : '' }}">
                                            @if (!empty($imageSrc))
                                                <div class="media-box">
                                                    <img src="{{ $imageSrc }}" alt="Landmark Image" loading="lazy" decoding="async">
                                                </div>
                                            @endif
                                            @if (!empty($videoFileUrl))
                                                <div class="media-box">
                                                    <video controls preload="metadata">
                                                        <source src="{{ $videoFileUrl }}" type="{{ $data['video_mime'] ?? 'video/mp4' }}">
                                                    </video>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                    <div class="detail"><strong>Description:</strong> {{ $data['description'] ?? 'No description' }}</div>
                                    @if ($useViewModal)
                                        <button type="button"
                                                class="view-btn"
                                                style="margin-top:.5rem;"
                                                onclick="smOpenLandmarkViewModal('{{ $viewModalId }}', '{{ $lid }}')">
                                            View full details
                                        </button>
                                    @endif
                                    </div>
                                </td>
                            </tr>

                            @if ($useViewModal)
                                @include('admin.partials.landmark-view-modal', [
                                    'viewModalId' => $viewModalId,
                                    'modalSafe' => $modalSafe,
                                    'landmarkId' => $lid,
                                    'data' => $data,
                                    'imageSrc' => $imageSrc,
                                    'videoFileUrl' => $videoFileUrl,
                                    'canApproveLandmark' => $canApproveLandmark,
                                    'panelRoutePrefix' => $panelRoutePrefix,
                                ])
                            @endif
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

    @if ($panelRoutePrefix === 'sitemanager')
        <div id="lmCreateLandmarkModal"
             class="lm-create-modal"
             role="dialog"
             aria-modal="true"
             aria-labelledby="lmCreateLandmarkHeading"
             aria-hidden="true">
            <div class="lm-create-modal__panel">
                <button type="button"
                        class="lm-create-modal__close"
                        onclick="lmCloseCreateModal()"
                        aria-label="Close">&times;</button>
                <h3 id="lmCreateLandmarkHeading">Add New Landmark</h3>

                @if ($errors->any())
                    <div class="lm-create-modal__errors" role="alert">
                        <strong>Please fix the following:</strong>
                        <ul>
                            @foreach ($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST"
                      id="lm-create-form"
                      action="{{ route('sitemanager.landmarks.store') }}"
                      enctype="multipart/form-data">
                    @csrf

                    <label for="lm-create-name">Landmark Name</label>
                    <input id="lm-create-name" type="text" name="name" value="{{ old('name') }}" required autocomplete="organization">

                    <label for="lm-create-category">Category</label>
                    <select id="lm-create-category" name="category" required>
                        @foreach (['Historical', 'Natural', 'Cultural', 'Religious', 'Modern'] as $cat)
                            <option value="{{ $cat }}" {{ old('category', 'Historical') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>

                    <label for="lm-create-description">Description</label>
                    <textarea id="lm-create-description" name="description" rows="4">{{ old('description') }}</textarea>

                    <label for="lm-create-map">Location</label>
                    <div class="lm-create-location-map-wrap">
                        <div class="lm-create-location-search">
                            <label for="lm-create-location-search">Search location</label>
                            <input id="lm-create-location-search" type="search" autocomplete="off" placeholder="Search Cebu landmark or place...">
                        </div>
                        <div id="lm-create-map" class="lm-create-location-map"></div>
                    </div>
                    <input id="lm-create-lat" type="hidden" name="latitude" value="{{ old('latitude') }}">
                    <input id="lm-create-lng" type="hidden" name="longitude" value="{{ old('longitude') }}">

                    <label for="lm-create-video">Video (optional)</label>
                    <input id="lm-create-video" type="file" name="video" accept="video/*" data-max-bytes="52428800">

                    <label for="lm-create-image">Landmark photo <span style="font-weight:500;color:#666;">(optional)</span></label>
                    <input id="lm-create-image" type="file" name="image" accept="image/*" data-max-bytes="524288">

                    <label for="lm-create-evidence">Evidence / supporting documents <span style="color:#b45309;">(required)</span></label>
                    <input id="lm-create-evidence" type="file" name="evidence_files[]" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,image/*,application/pdf" multiple required data-max-files="5" data-max-file-bytes="5242880">

                    <button type="submit">Submit for approval</button>
                </form>
            </div>
        </div>
    @endif

    @if ($panelRoutePrefix === 'sitemanager' && $mapboxToken)
        <script src="https://api.mapbox.com/mapbox-gl-js/v3.6.0/mapbox-gl.js"></script>
    @endif
    <script>
        function toggleRow(index) {
            const row = document.getElementById('expand-' + index);
            row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
        }

        var smLandmarksIndexUrl = @json($landmarksListUrl);
        var smLandmarkShowUrlTemplate = @json(route($panelRoutePrefix . '.landmarks.show', ['id' => '__ID__']));

        function smLandmarkShowUrl(landmarkId) {
            return smLandmarkShowUrlTemplate.replace('__ID__', encodeURIComponent(landmarkId));
        }

        function smModalIdForLandmarkId(landmarkId) {
            return 'viewModal_' + String(landmarkId).replace(/[^a-zA-Z0-9_-]/g, '_');
        }

        function smLandmarkIdFromPath() {
            var indexPath = smLandmarksIndexUrl.replace(/\/$/, '');
            var path = window.location.pathname.replace(/\/$/, '');
            if (path === indexPath) {
                return null;
            }
            var prefix = indexPath + '/';
            if (path.indexOf(prefix) !== 0) {
                return null;
            }
            var id = path.slice(prefix.length);
            return id ? decodeURIComponent(id.split('/')[0]) : null;
        }

        function smAnyModalOpen() {
            var createModal = document.getElementById('lmCreateLandmarkModal');
            if (createModal && createModal.style.display === 'flex') return true;
            var viewModals = document.querySelectorAll('.lm-view-modal');
            for (var i = 0; i < viewModals.length; i++) {
                if (viewModals[i].style.display === 'flex') return true;
            }
            return false;
        }

        function smSyncBodyScrollLock() {
            document.body.style.overflow = smAnyModalOpen() ? 'hidden' : '';
        }

        function smOpenLandmarkViewModal(modalId, landmarkId, updateUrl) {
            var modal = document.getElementById(modalId);
            if (!modal) return;
            document.querySelectorAll('.lm-view-modal').forEach(function (m) {
                if (m.id !== modalId) {
                    m.style.display = 'none';
                    m.setAttribute('aria-hidden', 'true');
                }
            });
            modal.style.display = 'flex';
            modal.setAttribute('aria-hidden', 'false');
            smSyncBodyScrollLock();
            if (updateUrl !== false && landmarkId) {
                try {
                    history.replaceState({ smLandmarkId: landmarkId }, '', smLandmarkShowUrl(landmarkId));
                } catch (e) {}
            }
        }

        function smCloseLandmarkViewModal(modalId, updateUrl) {
            var modal = document.getElementById(modalId);
            if (!modal) return;
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            smSyncBodyScrollLock();
            if (updateUrl !== false && smLandmarkIdFromPath()) {
                try {
                    history.replaceState(null, '', smLandmarksIndexUrl);
                } catch (e) {}
            }
        }

        document.addEventListener('click', function (event) {
            if (event.target.classList && event.target.classList.contains('lm-view-modal')) {
                smCloseLandmarkViewModal(event.target.id);
            }
        });

        function lmCreateModalBaseUrl() {
            return window.location.pathname + window.location.search;
        }

        function lmOpenCreateModal() {
            var modal = document.getElementById('lmCreateLandmarkModal');
            if (!modal) return;
            modal.style.display = 'flex';
            modal.setAttribute('aria-hidden', 'false');
            lmInitCreateMap();
            smSyncBodyScrollLock();
            try {
                history.replaceState(null, '', lmCreateModalBaseUrl() + '#create-landmark');
            } catch (e) {}
        }

        function lmCloseCreateModal() {
            var modal = document.getElementById('lmCreateLandmarkModal');
            if (!modal) return;
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            smSyncBodyScrollLock();
            if (window.location.hash === '#create-landmark') {
                try {
                    history.replaceState(null, '', lmCreateModalBaseUrl());
                } catch (e) {}
            }
        }

        function lmShouldOpenCreateModal() {
            return window.location.hash === '#create-landmark';
        }

        @if ($panelRoutePrefix === 'sitemanager' && $mapboxToken)
        var lmCreateMap = null;
        var lmCreateMarker = null;
        var lmDefaultLat = Number(@json($createMapLat));
        var lmDefaultLng = Number(@json($createMapLng));
        var lmCreateGeocodeTimer = null;
        var lmCreateGeocodeRequestId = 0;
        var lmCebuGeocodeBounds = {
            west: 123.25,
            south: 9.35,
            east: 124.30,
            north: 11.35
        };

        if (!Number.isFinite(lmDefaultLat)) lmDefaultLat = 10.3157;
        if (!Number.isFinite(lmDefaultLng)) lmDefaultLng = 123.8854;

        function lmSetCreateCoordinates(lngLat) {
            var latInput = document.getElementById('lm-create-lat');
            var lngInput = document.getElementById('lm-create-lng');
            if (!latInput || !lngInput) return;
            latInput.value = Number(lngLat.lat).toFixed(6);
            lngInput.value = Number(lngLat.lng).toFixed(6);
        }

        function lmMoveCreateMarker(lng, lat, zoom) {
            if (!lmCreateMap || !lmCreateMarker) return;
            var lngLat = { lng: Number(lng), lat: Number(lat) };
            if (!Number.isFinite(lngLat.lng) || !Number.isFinite(lngLat.lat)) return;
            lmCreateMarker.setLngLat([lngLat.lng, lngLat.lat]);
            lmSetCreateCoordinates(lngLat);
            lmCreateMap.flyTo({
                center: [lngLat.lng, lngLat.lat],
                zoom: zoom || 15,
                essential: true
            });
        }

        function lmIsInCebuBounds(lng, lat) {
            lng = Number(lng);
            lat = Number(lat);
            return Number.isFinite(lng)
                && Number.isFinite(lat)
                && lng >= lmCebuGeocodeBounds.west
                && lng <= lmCebuGeocodeBounds.east
                && lat >= lmCebuGeocodeBounds.south
                && lat <= lmCebuGeocodeBounds.north;
        }

        function lmFeatureIsInCebu(feature) {
            if (!feature || !Array.isArray(feature.center) || feature.center.length < 2) return false;
            if (!lmIsInCebuBounds(feature.center[0], feature.center[1])) return false;

            var searchableText = [
                feature.place_name || '',
                feature.text || ''
            ];
            (feature.context || []).forEach(function (part) {
                searchableText.push(part.text || '');
                searchableText.push(part.short_code || '');
            });

            var haystack = searchableText.join(' ').toLowerCase();
            var isPhilippines = haystack.indexOf('philippines') !== -1 || /\bph\b/.test(haystack);
            return haystack.indexOf('cebu') !== -1 && isPhilippines;
        }

        function lmGeocodeCreateLandmarkName(name) {
            var query = String(name || '').trim();
            if (query.length < 3 || !lmCreateMap || !lmCreateMarker) return;

            var requestId = ++lmCreateGeocodeRequestId;
            var params = new URLSearchParams({
                access_token: @json($mapboxToken),
                autocomplete: 'true',
                bbox: [
                    lmCebuGeocodeBounds.west,
                    lmCebuGeocodeBounds.south,
                    lmCebuGeocodeBounds.east,
                    lmCebuGeocodeBounds.north
                ].join(','),
                country: 'ph',
                limit: '5',
                types: 'poi,address,place,locality',
                proximity: lmDefaultLng + ',' + lmDefaultLat
            });
            var url = 'https://api.mapbox.com/geocoding/v5/mapbox.places/'
                + encodeURIComponent(query + ' Cebu Philippines')
                + '.json?'
                + params.toString();

            fetch(url)
                .then(function (response) {
                    if (!response.ok) throw new Error('Mapbox geocoding failed');
                    return response.json();
                })
                .then(function (payload) {
                    if (requestId !== lmCreateGeocodeRequestId) return;
                    var feature = payload && payload.features
                        ? payload.features.find(lmFeatureIsInCebu)
                        : null;
                    if (!feature || !Array.isArray(feature.center) || feature.center.length < 2) return;
                    lmMoveCreateMarker(feature.center[0], feature.center[1], 15);
                })
                .catch(function () {});
        }

        function lmScheduleCreateNameGeocode() {
            var nameInput = document.getElementById('lm-create-name');
            if (!nameInput) return;
            clearTimeout(lmCreateGeocodeTimer);
            lmCreateGeocodeTimer = setTimeout(function () {
                lmGeocodeCreateLandmarkName(nameInput.value);
            }, 650);
        }

        function lmScheduleCreateLocationSearchGeocode() {
            var searchInput = document.getElementById('lm-create-location-search');
            if (!searchInput) return;
            clearTimeout(lmCreateGeocodeTimer);
            lmCreateGeocodeTimer = setTimeout(function () {
                lmGeocodeCreateLandmarkName(searchInput.value);
            }, 450);
        }

        function lmInitCreateMap() {
            if (!window.mapboxgl) return;
            var mapEl = document.getElementById('lm-create-map');
            if (!mapEl) return;

            if (lmCreateMap) {
                setTimeout(function () {
                    lmCreateMap.resize();
                }, 80);
                return;
            }

            mapboxgl.accessToken = @json($mapboxToken);
            var startLngLat = { lng: lmDefaultLng, lat: lmDefaultLat };
            lmSetCreateCoordinates(startLngLat);

            lmCreateMap = new mapboxgl.Map({
                container: mapEl,
                style: 'mapbox://styles/mapbox/streets-v12',
                center: [startLngLat.lng, startLngLat.lat],
                zoom: 13
            });

            lmCreateMap.addControl(new mapboxgl.NavigationControl({ showCompass: false }), 'top-right');

            lmCreateMarker = new mapboxgl.Marker({ draggable: true })
                .setLngLat([startLngLat.lng, startLngLat.lat])
                .addTo(lmCreateMap);

            lmCreateMap.on('click', function (event) {
                lmCreateMarker.setLngLat(event.lngLat);
                lmSetCreateCoordinates(event.lngLat);
            });

            lmCreateMarker.on('dragend', function () {
                lmSetCreateCoordinates(lmCreateMarker.getLngLat());
            });

            var nameInput = document.getElementById('lm-create-name');
            if (nameInput) {
                nameInput.addEventListener('input', lmScheduleCreateNameGeocode);
                nameInput.addEventListener('change', lmScheduleCreateNameGeocode);
            }
            var locationSearchInput = document.getElementById('lm-create-location-search');
            if (locationSearchInput) {
                locationSearchInput.addEventListener('input', lmScheduleCreateLocationSearchGeocode);
                locationSearchInput.addEventListener('change', lmScheduleCreateLocationSearchGeocode);
            }

            setTimeout(function () {
                lmCreateMap.resize();
            }, 120);
        }
        @else
        function lmInitCreateMap() {}
        @endif

        @if ($panelRoutePrefix === 'sitemanager')
        function lmBytesToMegabytes(bytes) {
            return Math.round((Number(bytes || 0) / 1024 / 1024) * 10) / 10;
        }

        function lmCreateFileValidationErrors() {
            var errors = [];
            var videoInput = document.getElementById('lm-create-video');
            var imageInput = document.getElementById('lm-create-image');
            var evidenceInput = document.getElementById('lm-create-evidence');
            var totalBytes = 0;
            var maxPostBytes = 115 * 1024 * 1024;

            if (videoInput && videoInput.files && videoInput.files[0]) {
                var videoFile = videoInput.files[0];
                totalBytes += videoFile.size;
                var videoMax = Number(videoInput.dataset.maxBytes || 0);
                if (videoMax > 0 && videoFile.size > videoMax) {
                    errors.push('Video must be 50 MB or smaller. Selected file is ' + lmBytesToMegabytes(videoFile.size) + ' MB.');
                }
            }

            if (imageInput && imageInput.files && imageInput.files[0]) {
                var imageFile = imageInput.files[0];
                totalBytes += imageFile.size;
                var imageMax = Number(imageInput.dataset.maxBytes || 0);
                if (imageMax > 0 && imageFile.size > imageMax) {
                    errors.push('Landmark photo must be 512 KB or smaller.');
                }
            }

            if (evidenceInput && evidenceInput.files) {
                var evidenceMaxFiles = Number(evidenceInput.dataset.maxFiles || 0);
                var evidenceMaxBytes = Number(evidenceInput.dataset.maxFileBytes || 0);
                if (evidenceMaxFiles > 0 && evidenceInput.files.length > evidenceMaxFiles) {
                    errors.push('Upload up to five evidence files only.');
                }
                for (var i = 0; i < evidenceInput.files.length; i++) {
                    var evidenceFile = evidenceInput.files[i];
                    totalBytes += evidenceFile.size;
                    if (evidenceMaxBytes > 0 && evidenceFile.size > evidenceMaxBytes) {
                        errors.push('Each evidence file must be 5 MB or smaller. "' + evidenceFile.name + '" is ' + lmBytesToMegabytes(evidenceFile.size) + ' MB.');
                    }
                }
            }

            if (totalBytes > maxPostBytes) {
                errors.push('Combined uploads are too large. Keep the video, photo, and evidence under about 115 MB total.');
            }

            return errors;
        }

        function lmAttachCreateUploadGuard() {
            var form = document.getElementById('lm-create-form');
            if (!form) return;
            form.addEventListener('submit', function (event) {
                var errors = lmCreateFileValidationErrors();
                if (errors.length === 0) return;
                event.preventDefault();
                alert(errors.join('\n'));
            });
        }
        @endif

        document.addEventListener('DOMContentLoaded', function () {
            @if ($panelRoutePrefix === 'sitemanager')
            lmAttachCreateUploadGuard();
            if (lmShouldOpenCreateModal() || @json($errors->any())) {
                lmOpenCreateModal();
                return;
            }
            @endif
            var pathId = smLandmarkIdFromPath();
            if (pathId) {
                smOpenLandmarkViewModal(smModalIdForLandmarkId(pathId), null, false);
            } else if (@json(! empty($openViewModalId ?? null))) {
                smOpenLandmarkViewModal(@json($openViewModalId), @json($openLandmarkId ?? null), false);
            }
        });

        @if ($panelRoutePrefix === 'sitemanager')
        window.addEventListener('hashchange', function () {
            if (lmShouldOpenCreateModal()) {
                lmOpenCreateModal();
            } else {
                lmCloseCreateModal();
            }
        });
        @endif

        window.addEventListener('popstate', function () {
            var pathId = smLandmarkIdFromPath();
            if (pathId) {
                smOpenLandmarkViewModal(smModalIdForLandmarkId(pathId), null, false);
                return;
            }
            document.querySelectorAll('.lm-view-modal').forEach(function (m) {
                if (m.style.display === 'flex') {
                    smCloseLandmarkViewModal(m.id, false);
                }
            });
        });

        @if ($panelRoutePrefix === 'sitemanager')
        document.addEventListener('click', function (event) {
            var modal = document.getElementById('lmCreateLandmarkModal');
            if (modal && event.target === modal) {
                lmCloseCreateModal();
            }
        });
        @endif

        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            var viewModal = document.querySelector('.lm-view-modal[style*="flex"]');
            if (viewModal) {
                smCloseLandmarkViewModal(viewModal.id);
                return;
            }
            @if ($panelRoutePrefix === 'sitemanager')
            var modal = document.getElementById('lmCreateLandmarkModal');
            if (modal && modal.style.display === 'flex') {
                lmCloseCreateModal();
            }
            @endif
        });
    </script>
@endsection
