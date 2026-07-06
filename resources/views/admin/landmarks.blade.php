@extends('layouts.sidebar')

@php
    use App\Services\LandmarkImageStorage;
    use App\Support\LandmarkActivation;
    $landmarkCount = method_exists($landmarks, 'total') ? $landmarks->total() : $landmarks->count();
    $panelRoutePrefix = session('role') === 'site_manager' ? 'sitemanager' : 'admin';
    $currentView = in_array(request()->get('view', 'card'), ['card', 'list'], true)
        ? request()->get('view', 'card')
        : 'card';
    $isLandmarkApprovalQueue = $isLandmarkApprovalQueue ?? false;
    $landmarkStatusFilter = $landmarkStatusFilter ?? 'all';
    $landmarksListUrl = route($panelRoutePrefix . '.landmarks', array_filter([
        'view' => request()->get('view'),
        'status' => $isLandmarkApprovalQueue ? $landmarkStatusFilter : null,
    ]));
    $mapboxToken = config('services.mapbox.token');
    $createHasCoordinates = is_numeric(old('latitude')) && is_numeric(old('longitude'));
    $createMapLat = $createHasCoordinates ? (float) old('latitude') : 10.3157;
    $createMapLng = $createHasCoordinates ? (float) old('longitude') : 123.8854;
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
        html:has(body .land-wrap--sitemanager #card-view),
        html:has(body .land-wrap--approval #card-view),
        body:has(.land-wrap--sitemanager #card-view),
        body:has(.land-wrap--approval #card-view) {
            overflow-y: hidden;
        }
        .land-wrap { max-width: 2000px; margin: 0 auto; }
        .land-wrap--approval,
        .land-wrap--sitemanager {
            min-height: calc(100vh - 5rem);
            display: flex;
            flex-direction: column;
            padding-bottom: 32px;
        }
        .land-wrap--approval .pager,
        .land-wrap--sitemanager .pager {
            margin-top: 24px;
            margin-bottom: 40px;
        }
        .land-wrap--sitemanager #card-view + .pager {
            margin-top: 32px;
            margin-bottom: 32px;
        }
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
            .land-wrap--approval,
            .land-wrap--sitemanager {
                min-height: calc(100vh - 2.5rem);
                padding-bottom: 32px;
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
        .land-wrap--sitemanager .land-card {
            border-color: #e5e7eb;
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
        .media-box img {
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
        .empty-box {
            color: #6b7280;
            background: #fff;
            border: 1px dashed #d1d5db;
            border-radius: 10px;
            padding: .9rem 1rem;
        }
        .pager {
            margin: 24px 0 40px auto;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: .65rem;
            flex-wrap: nowrap;
            width: fit-content;
            max-width: 100%;
            padding-bottom: 32px;
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
            white-space: nowrap;
        }
        @media (max-width: 640px) {
            .pager {
                gap: .4rem;
                max-width: 100%;
            }
            .pager-btn {
                padding: .42rem .58rem;
                font-size: .8rem;
            }
            .pager-text {
                padding: 0;
                font-size: .8rem;
            }
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
        .lm-create-location-results {
            display: none;
            max-height: 190px;
            margin: .45rem 0 0;
            padding: .25rem 0;
            overflow-y: auto;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            background: #fff;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .12);
        }
        .lm-create-location-results.is-open { display: block; }
        .lm-create-location-result {
            display: block;
            width: 100%;
            padding: .55rem .65rem;
            border: 0;
            background: #fff;
            color: #111827;
            font: inherit;
            font-size: .82rem;
            line-height: 1.35;
            text-align: left;
            cursor: pointer;
        }
        .lm-create-location-result:hover,
        .lm-create-location-result:focus-visible {
            background: #f3f4f6;
            outline: none;
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
            
            box-shadow: 0 8px 22px rgba(122, 46, 31, 0.1);
        }
        .land-card--clickable:focus-visible {
            outline: 2px solid #e5e7eb;
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
            cursor: inherit;
            text-align: left;
            text-decoration: none;
        }
        .land-table .row-name-btn:hover { text-decoration: none; }
        .land-expand-btn {
            border: none;
            background: none;
            padding: 0;
            font: inherit;
            color: #7A2E1F;
            font-weight: 700;
            cursor: pointer;
            text-align: left;
            text-decoration: none;
        }
        .land-expand-btn:hover { text-decoration: none; }
        .land-row-actions {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            flex-wrap: wrap;
        }
        .land-action-divider {
            color: #d1d5db;
            font-weight: 700;
        }
        .land-delete-form {
            display: inline;
            margin: 0;
        }
        .land-delete-btn {
            border: none;
            background: none;
            padding: 0;
            font: inherit;
            color: #991b1b;
            font-weight: 700;
            cursor: pointer;
            text-align: left;
            text-decoration: none;
        }
        .land-delete-btn:hover { text-decoration: none; }
        .land-delete-modal {
            display: none;
            position: fixed;
            z-index: 1200;
            inset: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.55);
            padding: 1rem;
            align-items: center;
            justify-content: center;
        }
        .land-delete-modal.is-open { display: flex; }
        .land-delete-modal__panel {
            width: 100%;
            max-width: 430px;
            background: #fff;
            border: 1px solid #eceff3;
            border-radius: 14px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.2);
            padding: 1.1rem 1.15rem 1rem;
        }
        .land-delete-modal__title {
            margin: 0 0 .45rem;
            color: #7A2E1F;
            font-size: 1.2rem;
            font-weight: 800;
        }
        .land-delete-modal__message {
            margin: 0;
            color: #374151;
            line-height: 1.45;
            font-size: .95rem;
        }
        .land-delete-modal__name {
            margin: .65rem 0 0;
            color: #111827;
            font-weight: 800;
            word-break: break-word;
        }
        .land-delete-modal__actions {
            margin-top: 1rem;
            display: flex;
            justify-content: flex-end;
            gap: .55rem;
        }
        .land-delete-modal__btn {
            border-radius: 8px;
            padding: .5rem .9rem;
            font-weight: 700;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all .15s ease;
        }
        .land-delete-modal__btn.cancel {
            background: #f3f4f6;
            border-color: #e5e7eb;
            color: #374151;
        }
        .land-delete-modal__btn.cancel:hover { background: #e5e7eb; }
        .land-delete-modal__btn.confirm {
            background: #ef4444;
            border-color: #fecaca;
            color: #fff;
        }
        .land-delete-modal__btn.confirm:hover { background: #dc2626; }
        .land-detail-row td {
            padding: 0;
            border-top: 0;
            background: #fff;
        }
        .land-detail-row.is-open td {
            border-top: 1px solid #eef2f7;
        }
        .land-detail-panel {
            max-height: 0;
            overflow: hidden;
            transition: max-height .28s ease;
        }
        .land-detail-content {
            display: grid;
            grid-template-columns: minmax(220px, 320px) 1fr;
            gap: 1.25rem;
            padding: 1rem 1.25rem 1.25rem;
        }
        .land-detail-heading {
            margin: 0 0 .45rem;
            font-size: .78rem;
            font-weight: 700;
            color: #92400e;
            text-transform: uppercase;
        }
        .land-detail-photo {
            width: 100%;
            border-radius: 8px;
            display: block;
            aspect-ratio: 16 / 10;
            object-fit: cover;
            background: #f3f4f6;
        }
        .land-detail-empty {
            margin: 0;
            color: #6b7280;
            font-size: .9rem;
        }
        .land-detail-description {
            margin: 0;
            color: #374151;
            line-height: 1.55;
            font-size: .92rem;
            white-space: pre-line;
        }
        @media (max-width: 760px) {
            .land-detail-content {
                grid-template-columns: 1fr;
            }
        }
        .land-status-filter {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
        }
        .land-status-filter label {
            font-weight: 700;
            font-size: .85rem;
            color: #374151;
        }
        .land-status-filter select {
            min-width: 125px;
            padding: .42rem .65rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
            color: #374151;
            font: inherit;
            font-size: .85rem;
            font-weight: 700;
            cursor: pointer;
        }
    </style>
    <div class="land-wrap {{ $isLandmarkApprovalQueue ? 'land-wrap--approval' : ($panelRoutePrefix === 'sitemanager' ? 'land-wrap--sitemanager' : '') }}">
    <div class="land-header">
        <div class="land-header-main">
            <h2 class="land-title">{{ $isLandmarkApprovalQueue ? 'Landmark Approvals' : 'All Landmarks' }}</h2>
            @if ($panelRoutePrefix !== 'sitemanager')
                <p class="land-sub">
                    {{ $landmarkCount }} submission{{ $landmarkCount !== 1 ? 's' : '' }}
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
                <form method="GET" action="{{ route('admin.landmarks') }}" class="land-status-filter">
                    <label for="landmark-status-filter">Status:</label>
                    <input type="hidden" name="view" value="{{ $currentView }}">
                    <select id="landmark-status-filter" name="status" onchange="this.form.submit()">
                        <option value="pending" @selected($landmarkStatusFilter === 'pending')>Pending</option>
                        <option value="all" @selected($landmarkStatusFilter === 'all')>All</option>
                        <option value="active" @selected($landmarkStatusFilter === 'active')>Approved</option>
                        <option value="rejected" @selected($landmarkStatusFilter === 'rejected')>Rejected</option>
                    </select>
                </form>
            @endif
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
                        $imageSrc = null;
                        $storedImageUrl = $data['image_url'] ?? LandmarkImageStorage::publicUrl($lid);

                        if (!empty($data['image_base64']) || $storedImageUrl !== null) {
                            $imageSrc = $storedImageUrl ?? route($panelRoutePrefix . '.landmarks.image', $lid);
                        }

                        $activation = strtolower((string) ($data['activation_status'] ?? 'active'));
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
                            </div>
                        @else
                            <a class="land-card-link" href="{{ $showRoute }}">
                                <h3>{{ $data['name'] ?? 'Unnamed Landmark' }}</h3>
                                <div class="land-card-badges">
                                    <span class="land-activation-pill land-activation-pill--{{ $activation === 'pending' || $activation === 'rejected' ? $activation : 'active' }}">
                                        {{ $activationLabel }}
                                    </span>
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

                        @if (!empty($imageSrc))
                            <p class="land-card-section-title">Landmark photo</p>
                        @endif

                        @if (!empty($imageSrc))
                            <div class="media-box" @if ($useViewModal) onclick="event.stopPropagation()" @endif>
                                <img src="{{ $imageSrc }}" alt="Landmark Image" loading="lazy" decoding="async">
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
                            <th>Location</th>
                            <th>Categorical</th>
                            <th>Status</th>
                            @if ($panelRoutePrefix === 'sitemanager')
                            <th>Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($landmarks as $index => $landmark)
                            @php
                                $lid = $landmark->id();
                                $modalSafe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $lid);
                                $viewModalId = 'viewModal_' . $modalSafe;
                                $data = $landmark->data();
                                $imageSrc = null;
                                $storedImageUrl = $data['image_url'] ?? LandmarkImageStorage::publicUrl($lid);

                                if (!empty($data['image_base64']) || $storedImageUrl !== null) {
                                    $imageSrc = $storedImageUrl ?? route($panelRoutePrefix . '.landmarks.image', $lid);
                                }

                                $activation = strtolower((string) ($data['activation_status'] ?? 'active'));
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
                                $locationLabel = trim((string) ($data['location'] ?? ''));
                                $categoryLabel = trim((string) ($data['category'] ?? ''));
                            @endphp

                            <tr>
                                <td>
                                    @if ($useViewModal)
                                        <span class="row-name-btn">
                                            {{ $data['name'] ?? 'Unnamed Landmark' }}
                                        </span>
                                    @else
                                        <a href="{{ route($panelRoutePrefix . '.landmarks.show', $lid) }}" style="color:#7A2E1F;font-weight:600;text-decoration:none;">
                                            {{ $data['name'] ?? 'Unnamed Landmark' }}
                                        </a>
                                    @endif
                                </td>
                                <td>{{ $locationLabel !== '' ? $locationLabel : 'N/A' }}</td>
                                <td>{{ $categoryLabel !== '' ? $categoryLabel : 'N/A' }}</td>
                                <td>
                                    <span class="land-activation-pill land-activation-pill--{{ $activation === 'pending' || $activation === 'rejected' ? $activation : 'active' }}">
                                        {{ $activationLabel }}
                                    </span>
                                </td>
                                @if ($panelRoutePrefix === 'sitemanager')
                                    <td>
                                        <div class="land-row-actions">
                                            <button type="button"
                                                    class="land-expand-btn"
                                                    data-landmark-expand
                                                    data-target="land-detail-{{ $index }}"
                                                    aria-controls="land-detail-{{ $index }}"
                                                    aria-expanded="false">
                                                Click to expand
                                            </button>
                                            <span class="land-action-divider" aria-hidden="true">|</span>
                                            <form method="POST"
                                                  action="{{ route('sitemanager.landmarks.destroy', $lid) }}"
                                                  class="land-delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button"
                                                        class="land-delete-btn"
                                                        data-landmark-delete
                                                        data-landmark-name="{{ $data['name'] ?? 'this landmark' }}">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                @endif
                            </tr>

                            @if ($panelRoutePrefix === 'sitemanager')
                                <tr class="land-detail-row" id="land-detail-{{ $index }}">
                                    <td colspan="5">
                                        <div class="land-detail-panel">
                                            <div class="land-detail-content">
                                                <section>
                                                    <h4 class="land-detail-heading">Landmark Photo</h4>
                                                    @if (!empty($imageSrc))
                                                        <img class="land-detail-photo" src="{{ $imageSrc }}" alt="Landmark Image" loading="lazy" decoding="async">
                                                    @else
                                                        <p class="land-detail-empty">No photo available.</p>
                                                    @endif
                                                </section>
                                                <section>
                                                    <h4 class="land-detail-heading">Full Description</h4>
                                                    <p class="land-detail-description">{{ trim((string) ($data['description'] ?? '')) !== '' ? $data['description'] : 'No description available.' }}</p>
                                                </section>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endif

                            @if ($useViewModal)
                                @include('admin.partials.landmark-view-modal', [
                                    'viewModalId' => $viewModalId,
                                    'modalSafe' => $modalSafe,
                                    'landmarkId' => $lid,
                                    'data' => $data,
                                    'imageSrc' => $imageSrc,
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
        <div id="landDeleteModal"
             class="land-delete-modal"
             aria-hidden="true">
            <div class="land-delete-modal__panel"
                 role="dialog"
                 aria-modal="true"
                 aria-labelledby="landDeleteModalTitle">
                <h3 id="landDeleteModalTitle" class="land-delete-modal__title">Delete Landmark</h3>
                <p class="land-delete-modal__message">Are you sure you want to delete this landmark?</p>
                <p class="land-delete-modal__name" id="landDeleteModalName"></p>
                <div class="land-delete-modal__actions">
                    <button type="button" id="cancelLandDelete" class="land-delete-modal__btn cancel">Cancel</button>
                    <button type="button" id="confirmLandDelete" class="land-delete-modal__btn confirm">Delete</button>
                </div>
            </div>
        </div>

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
                            <input id="lm-create-location-search" type="search" value="{{ old('location') }}" autocomplete="off" placeholder="Search Cebu landmark or place..." aria-controls="lm-create-location-results" aria-expanded="false">
                            <div id="lm-create-location-results" class="lm-create-location-results" role="listbox"></div>
                        </div>
                        <div id="lm-create-map" class="lm-create-location-map"></div>
                    </div>
                    <input id="lm-create-location" type="hidden" name="location" value="{{ old('location') }}">
                    <input id="lm-create-lat" type="hidden" name="latitude" value="{{ old('latitude') }}">
                    <input id="lm-create-lng" type="hidden" name="longitude" value="{{ old('longitude') }}">

                    <label for="lm-create-image">Landmark photo</label>
                    <input id="lm-create-image" type="file" name="image" accept="image/*" data-max-bytes="524288">

                    <label for="lm-create-evidence">Evidence / supporting documents</label>
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
        var smLandmarksIndexUrl = @json($landmarksListUrl);
        var smLandmarkShowUrlTemplate = @json(route($panelRoutePrefix . '.landmarks.show', ['id' => '__ID__']));

        function smLandmarkShowUrl(landmarkId) {
            return smLandmarkShowUrlTemplate.replace('__ID__', encodeURIComponent(landmarkId));
        }

        function smInitLandmarkListExpansion() {
            var buttons = document.querySelectorAll('[data-landmark-expand]');
            if (!buttons.length) return;

            function closeExpandedRows(exceptId) {
                buttons.forEach(function (button) {
                    if (button.dataset.target !== exceptId) {
                        button.setAttribute('aria-expanded', 'false');
                    }
                });
                document.querySelectorAll('.land-detail-row.is-open').forEach(function (row) {
                    if (row.id !== exceptId) {
                        var panel = row.querySelector('.land-detail-panel');
                        if (panel) panel.style.maxHeight = '0px';
                        row.classList.remove('is-open');
                    }
                });
            }

            buttons.forEach(function (button) {
                button.addEventListener('click', function () {
                    var targetId = button.dataset.target;
                    var row = document.getElementById(targetId);
                    if (!row) return;

                    var shouldOpen = !row.classList.contains('is-open');
                    var panel = row.querySelector('.land-detail-panel');
                    closeExpandedRows(targetId);
                    row.classList.toggle('is-open', shouldOpen);
                    button.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
                    if (panel) {
                        panel.style.maxHeight = shouldOpen ? panel.scrollHeight + 'px' : '0px';
                        if (shouldOpen) {
                            window.setTimeout(function () {
                                panel.style.maxHeight = panel.scrollHeight + 'px';
                            }, 60);
                        }
                    }
                });
            });
        }

        function smInitLandmarkDeleteModal() {
            var modal = document.getElementById('landDeleteModal');
            var nameEl = document.getElementById('landDeleteModalName');
            var cancelBtn = document.getElementById('cancelLandDelete');
            var confirmBtn = document.getElementById('confirmLandDelete');
            var pendingForm = null;
            if (!modal || !nameEl || !cancelBtn || !confirmBtn) return;

            function openModal(form, landmarkName) {
                pendingForm = form;
                nameEl.textContent = landmarkName || 'This landmark';
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                confirmBtn.focus();
            }

            function closeModal() {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                pendingForm = null;
            }

            document.querySelectorAll('[data-landmark-delete]').forEach(function (button) {
                button.addEventListener('click', function () {
                    openModal(button.closest('form'), button.dataset.landmarkName || 'This landmark');
                });
            });

            cancelBtn.addEventListener('click', closeModal);
            confirmBtn.addEventListener('click', function () {
                if (pendingForm) {
                    pendingForm.submit();
                }
            });
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeModal();
                }
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                    closeModal();
                }
            });
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
        var lmCreateSearchFeatures = [];
        var lmHasInitialCreateCoordinates = @json($createHasCoordinates);
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

        function lmCloseCreateLocationResults() {
            var searchInput = document.getElementById('lm-create-location-search');
            var resultsEl = document.getElementById('lm-create-location-results');
            if (searchInput) searchInput.setAttribute('aria-expanded', 'false');
            if (resultsEl) {
                resultsEl.classList.remove('is-open');
                resultsEl.replaceChildren();
            }
            lmCreateSearchFeatures = [];
        }

        function lmSetCreateLocationSearchValue(value) {
            var searchInput = document.getElementById('lm-create-location-search');
            var locationInput = document.getElementById('lm-create-location');
            if (searchInput) searchInput.value = value;
            if (locationInput) locationInput.value = value;
        }

        function lmSuggestCreateCategory(feature) {
            var select = document.getElementById('lm-create-category');
            if (!select || !feature) return;

            var properties = feature.properties || {};
            var text = [
                feature.text,
                feature.place_name,
                feature.id,
                Array.isArray(feature.place_type) ? feature.place_type.join(' ') : '',
                properties.category,
                properties.maki,
                properties.class,
                properties.type,
                properties.name
            ].join(' ').toLowerCase();

            var category = '';
            if (/(church|shrine|cathedral|chapel|basilica|temple|mosque|religious)/.test(text)) {
                category = 'Religious';
            } else if (/(museum|ancestral|heritage|historic|historical|monument|memorial|cultural|fort|cross)/.test(text)) {
                category = /(museum|ancestral|heritage|cultural)/.test(text) ? 'Cultural' : 'Historical';
            } else if (/(peak|mountain|park|natural|beach|falls|waterfall|lake|river|garden|trail|reserve)/.test(text)) {
                category = 'Natural';
            } else if (/(cafe|coffee|restaurant|mall|shop|bar|hotel|resort|attraction|tourist)/.test(text)) {
                category = 'Modern';
            }

            if (!category) return;
            for (var i = 0; i < select.options.length; i++) {
                if (select.options[i].value === category) {
                    select.value = category;
                    return;
                }
            }
        }

        function lmSelectCreateLocation(feature) {
            if (!feature || !Array.isArray(feature.center) || feature.center.length < 2) return;
            clearTimeout(lmCreateGeocodeTimer);
            lmCreateGeocodeRequestId++;
            lmSetCreateLocationSearchValue(feature.place_name || feature.text || '');
            lmSuggestCreateCategory(feature);
            lmCloseCreateLocationResults();
            lmMoveCreateMarker(feature.center[0], feature.center[1], 15);
        }

        function lmCreateReverseFeaturePriority(feature) {
            var types = Array.isArray(feature && feature.place_type) ? feature.place_type : [];
            var category = String(feature && feature.properties && feature.properties.category || '').toLowerCase();
            var featureId = String(feature && feature.id || '').toLowerCase();

            if (types.indexOf('poi') !== -1 || featureId.indexOf('poi.') === 0) return 0;
            if (types.indexOf('landmark') !== -1 || category.indexOf('landmark') !== -1) return 1;
            if (types.indexOf('place') !== -1 || types.indexOf('locality') !== -1) return 2;
            if (types.indexOf('neighborhood') !== -1) return 3;
            if (types.indexOf('address') !== -1 || types.indexOf('street') !== -1) return 4;
            return 5;
        }

        function lmCreateReverseFeatureLabel(feature) {
            if (!feature) return '';
            var priority = lmCreateReverseFeaturePriority(feature);
            if (priority <= 3) {
                return feature.text || (feature.properties && feature.properties.name) || feature.place_name || '';
            }
            return feature.place_name || feature.text || '';
        }

        function lmCreateRenderedLandmarkLabel(point) {
            if (!lmCreateMap || !point) return '';

            var radius = 36;
            var features = lmCreateMap.queryRenderedFeatures([
                [point.x - radius, point.y - radius],
                [point.x + radius, point.y + radius]
            ]);
            var rejectedLayerPattern = /(road|street|settlement|place|locality|neighborhood|admin|country|state|address|transit)/i;
            var landmarkLayerPattern = /(poi|landmark|landuse|park|museum|attraction|historic|monument|memorial)/i;

            var landmarks = features.filter(function (feature) {
                var layerId = String(feature && feature.layer && feature.layer.id || '');
                if (rejectedLayerPattern.test(layerId)) return false;

                var properties = feature && feature.properties || {};
                var descriptor = [
                    layerId,
                    properties.class,
                    properties.type,
                    properties.category,
                    properties.maki
                ].join(' ');
                var name = properties.name || properties.name_en || properties.name_und || '';

                return Boolean(name) && landmarkLayerPattern.test(descriptor);
            });

            if (landmarks.length === 0) return '';
            lmSuggestCreateCategory(landmarks[0]);
            var properties = landmarks[0].properties || {};
            return properties.name || properties.name_en || properties.name_und || '';
        }

        function lmReverseGeocodeCreateLocation(lngLat, requestId, renderedLandmarkLabel) {
            if (renderedLandmarkLabel) {
                lmSetCreateLocationSearchValue(renderedLandmarkLabel);
                return;
            }

            var params = new URLSearchParams({
                access_token: @json($mapboxToken),
                country: 'ph'
            });
            var url = 'https://api.mapbox.com/geocoding/v5/mapbox.places/'
                + Number(lngLat.lng) + ',' + Number(lngLat.lat)
                + '.json?'
                + params.toString();

            fetch(url)
                .then(function (response) {
                    if (!response.ok) throw new Error('Mapbox reverse geocoding failed');
                    return response.json();
                })
                .then(function (payload) {
                    if (requestId !== lmCreateGeocodeRequestId) return;
                    var features = payload && Array.isArray(payload.features) ? payload.features.slice() : [];
                    features.sort(function (a, b) {
                        return lmCreateReverseFeaturePriority(a) - lmCreateReverseFeaturePriority(b);
                    });
                    var landmark = features.find(function (feature) {
                        return lmCreateReverseFeaturePriority(feature) <= 1;
                    });
                    if (!landmark && features.length > 0) {
                        landmark = features[0];
                    }
                    var label = landmark ? lmCreateReverseFeatureLabel(landmark) : '';
                    lmSetCreateLocationSearchValue(label);
                    if (landmark) lmSuggestCreateCategory(landmark);
                })
                .catch(function () {});
        }

        function lmHandleManualCreateLocation(lngLat, moveMarker, point) {
            var location = { lng: Number(lngLat.lng), lat: Number(lngLat.lat) };
            if (!Number.isFinite(location.lng) || !Number.isFinite(location.lat)) return;

            clearTimeout(lmCreateGeocodeTimer);
            var requestId = ++lmCreateGeocodeRequestId;
            lmCloseCreateLocationResults();
            lmSetCreateLocationSearchValue('');
            if (moveMarker && lmCreateMarker) lmCreateMarker.setLngLat([location.lng, location.lat]);
            lmSetCreateCoordinates(location);
            var renderedLandmarkLabel = lmCreateRenderedLandmarkLabel(point || lmCreateMap.project(location));
            lmReverseGeocodeCreateLocation(location, requestId, renderedLandmarkLabel);
        }

        function lmRenderCreateLocationResults(features) {
            var searchInput = document.getElementById('lm-create-location-search');
            var resultsEl = document.getElementById('lm-create-location-results');
            if (!searchInput || !resultsEl) return;

            lmCreateSearchFeatures = features;
            resultsEl.replaceChildren();

            features.forEach(function (feature, index) {
                var option = document.createElement('button');
                option.type = 'button';
                option.className = 'lm-create-location-result';
                option.setAttribute('role', 'option');
                option.textContent = feature.place_name || feature.text || '';
                option.addEventListener('click', function () {
                    lmSelectCreateLocation(lmCreateSearchFeatures[index]);
                });
                resultsEl.appendChild(option);
            });

            var hasResults = features.length > 0;
            resultsEl.classList.toggle('is-open', hasResults);
            searchInput.setAttribute('aria-expanded', hasResults ? 'true' : 'false');
        }

        function lmSearchCreateLocations(searchValue) {
            var query = String(searchValue || '').trim();
            if (query.length < 3 || !lmCreateMap || !lmCreateMarker) {
                lmCloseCreateLocationResults();
                return;
            }

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
                    var features = payload && payload.features
                        ? payload.features.filter(lmFeatureIsInCebu)
                        : [];
                    lmRenderCreateLocationResults(features);
                })
                .catch(function () {
                    if (requestId === lmCreateGeocodeRequestId) lmCloseCreateLocationResults();
                });
        }

        function lmScheduleCreateLocationSearchGeocode() {
            var searchInput = document.getElementById('lm-create-location-search');
            if (!searchInput) return;
            clearTimeout(lmCreateGeocodeTimer);
            lmCreateGeocodeRequestId++;
            lmCloseCreateLocationResults();
            if (!searchInput.value.trim()) {
                return;
            }
            lmCreateGeocodeTimer = setTimeout(function () {
                lmSearchCreateLocations(searchInput.value);
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
            if (lmHasInitialCreateCoordinates) {
                lmSetCreateCoordinates(startLngLat);
            }

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
                lmHandleManualCreateLocation(event.lngLat, true, event.point);
            });

            lmCreateMarker.on('dragend', function () {
                lmHandleManualCreateLocation(lmCreateMarker.getLngLat(), false);
            });

            var locationSearchInput = document.getElementById('lm-create-location-search');
            if (locationSearchInput) {
                lmSetCreateLocationSearchValue(locationSearchInput.value || '');
                locationSearchInput.addEventListener('input', lmScheduleCreateLocationSearchGeocode);
            }
            document.addEventListener('click', function (event) {
                if (!event.target.closest('.lm-create-location-search')) {
                    lmCloseCreateLocationResults();
                }
            });

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
            var imageInput = document.getElementById('lm-create-image');
            var evidenceInput = document.getElementById('lm-create-evidence');
            var totalBytes = 0;
            var maxPostBytes = 115 * 1024 * 1024;

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
                errors.push('Combined uploads are too large. Keep the photo and evidence under about 115 MB total.');
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
            smInitLandmarkListExpansion();
            smInitLandmarkDeleteModal();
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
