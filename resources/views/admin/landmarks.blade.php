@extends('layouts.sidebar')

@php
    use App\Support\LandmarkActivation;
    $landmarkCount = method_exists($landmarks, 'total') ? $landmarks->total() : $landmarks->count();
    $panelRoutePrefix = session('role') === 'site_manager' ? 'sitemanager' : 'admin';
    $isLandmarkApprovalQueue = $isLandmarkApprovalQueue ?? false;
    $landmarkStatusFilter = $landmarkStatusFilter ?? 'all';
    $landmarkSearch = $landmarkSearch ?? '';
    $landmarkCategoryFilter = $landmarkCategoryFilter ?? 'all';
    $landmarkOrder = $landmarkOrder ?? 'default';
    $landmarksListUrl = route($panelRoutePrefix . '.landmarks', array_filter([
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
            .flash-ok-lm--compact {
                width: fit-content;
                max-width: 100%;
                margin-right: 0;
                margin-left: 0;
                padding: .55rem .8rem;
            }
        </style>
        @if (session('status'))
            <div class="flash-ok-lm{{ $panelRoutePrefix === 'sitemanager' ? ' flash-ok-lm--compact' : '' }}">{{ session('status') }}</div>
        @endif
        @if (session('status_err'))
            <div class="flash-ok-lm" style="background:#fef2f2;color:#991b1b;border-color:#fecaca;">{{ session('status_err') }}</div>
        @endif
    @endif
    <style>
        @if ($panelRoutePrefix === 'admin' && $isLandmarkApprovalQueue)
        html:has(body .admin-landmarks-page),
        body:has(.admin-landmarks-page) {
            height: 100%;
            overflow-y: hidden;
        }
        @endif

        .land-wrap { max-width: 2000px; margin: 0 auto; }
        .land-wrap--approval,
        .land-wrap--sitemanager {
            min-height: calc(100vh - 5rem);
            display: flex;
            flex-direction: column;
            padding-bottom: 32px;
        }
        .land-wrap--sitemanager {
            min-height: auto;
            padding-bottom: 0;
        }
        .land-wrap--approval {
            min-height: auto;
        }
        .land-wrap--approval .table-wrap {
            margin-bottom: 24px;
            padding: 1rem;
            box-sizing: border-box;
            overflow-x: auto;
            border-color: #eceff3;
            border-radius: 12px;
            box-shadow: 0 6px 16px rgba(15, 23, 42, .05);
        }
        .land-wrap--approval .land-table {
            min-width: 850px;
            table-layout: fixed;
            border-collapse: separate;
            border-spacing: 0;
        }
        .land-wrap--approval .land-table th {
            padding: .68rem .78rem;
            background: #fff7ed;
            border-bottom: 1px solid #f1f5f9;
            line-height: 1.2;
        }
        .land-wrap--approval .land-table td {
            padding: .65rem .78rem;
            border-top: 0;
            border-bottom: 1px solid #eef2f7;
            vertical-align: middle;
            line-height: 1.25;
        }
        .land-wrap--approval .land-table th:nth-child(1),
        .land-wrap--approval .land-table td:nth-child(1) { width: 34%; }
        .land-wrap--approval .land-table th:nth-child(2),
        .land-wrap--approval .land-table td:nth-child(2) { width: 33%; }
        .land-wrap--approval .land-table th:nth-child(3),
        .land-wrap--approval .land-table td:nth-child(3) { width: 12%; }
        .land-wrap--approval .land-table th:nth-child(4),
        .land-wrap--approval .land-table td:nth-child(4) { width: 12%; }
        .land-wrap--approval .land-table th:nth-child(5),
        .land-wrap--approval .land-table td:nth-child(5) { width: 9%; }
        .land-wrap--approval .land-table tbody tr:last-child td {
            border-bottom: 0;
        }
        .land-wrap--approval .land-activation-pill {
            margin-top: 0;
            padding: .18rem .5rem;
            line-height: 1;
        }
        .land-wrap--approval .pager {
            margin-top: 0;
            margin-bottom: 40px;
        }
        .land-wrap--sitemanager .pager {
            margin-top: 24px;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .land-wrap--sitemanager .table-wrap {
            margin-top: .75rem;
            padding: 1rem;
            box-sizing: border-box;
            overflow-x: auto;
            border-color: #eceff3;
            border-radius: 12px;
            box-shadow: 0 6px 16px rgba(15, 23, 42, .05);
        }
        .land-wrap--sitemanager .land-table {
            min-width: 850px;
            table-layout: fixed;
            border-collapse: separate;
            border-spacing: 0;
        }
        .land-wrap--sitemanager .land-table th {
            padding: .68rem .78rem;
            background: #fff7ed;
            border-bottom: 1px solid #f1f5f9;
            line-height: 1.2;
        }
        .land-wrap--sitemanager .land-table td {
            padding: .65rem .78rem;
            border-top: 0;
            border-bottom: 1px solid #eef2f7;
            vertical-align: middle;
            line-height: 1.25;
        }
        .land-wrap--sitemanager .land-table th:nth-child(1),
        .land-wrap--sitemanager .land-table td:nth-child(1) { width: 29%; }
        .land-wrap--sitemanager .land-table th:nth-child(2),
        .land-wrap--sitemanager .land-table td:nth-child(2) { width: 34%; }
        .land-wrap--sitemanager .land-table th:nth-child(3),
        .land-wrap--sitemanager .land-table td:nth-child(3) { width: 13%; }
        .land-wrap--sitemanager .land-table th:nth-child(4),
        .land-wrap--sitemanager .land-table td:nth-child(4) { width: 15%; min-width: 125px; }
        .land-wrap--sitemanager .land-table th:nth-child(5),
        .land-wrap--sitemanager .land-table td:nth-child(5) { width: 9%; }
        .land-wrap--sitemanager .land-table tbody tr:last-child td {
            border-bottom: 0;
        }
        .land-wrap--sitemanager .land-activation-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
            min-height: 24px;
            margin-top: 0;
            padding: 3px 10px;
            line-height: 1;
            white-space: nowrap;
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
            .land-wrap--sitemanager {
                min-height: auto;
                padding-bottom: 0;
            }
            .land-wrap--approval {
                min-height: auto;
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
        .lm-create-modal,
        .lm-edit-modal {
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
        .lm-create-modal__panel,
        .lm-edit-modal__panel {
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
        .lm-create-modal__panel h3,
        .lm-edit-modal__panel h3 {
            margin: 0 2rem .5rem 0;
            font-size: 1.35rem;
            font-weight: 700;
            color: #4c1d95;
        }
        .lm-create-modal__panel label,
        .lm-edit-modal__panel label {
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
        .lm-create-modal__panel select,
        .lm-edit-modal__panel input[type="text"],
        .lm-edit-modal__panel input[type="number"],
        .lm-edit-modal__panel input[type="file"],
        .lm-edit-modal__panel textarea,
        .lm-edit-modal__panel select {
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
        .lm-create-modal__panel input:focus,
        .lm-create-modal__panel textarea:focus,
        .lm-create-modal__panel select:focus,
        .lm-edit-modal__panel input:focus,
        .lm-edit-modal__panel textarea:focus,
        .lm-edit-modal__panel select:focus,
        .lm-edit-modal__panel .lm-create-location-search input[type="search"]:focus {
            outline: none !important;
            border-color: #d1d5db !important;
            box-shadow: none !important;
        }
        .lm-create-modal__panel .landmark-form-control {
            box-sizing: border-box;
            width: 100%;
            height: 40px;
            min-height: 40px;
            padding: 0 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            outline: none;
            background: #fff;
            color: #111827;
            box-shadow: none;
            font-family: inherit;
            font-size: 14px;
        }
        .lm-create-modal__panel .landmark-form-control:focus,
        .lm-create-modal__panel .landmark-form-control:focus-visible {
            border-color: #cbd5e1 !important;
            outline: none !important;
            box-shadow: none !important;
        }
        .lm-create-modal__panel .lm-create-category-dropdown {
            height: 40px;
            min-height: 40px;
        }
        .lm-create-modal__panel .lm-create-category-dropdown .landmark-form-control {
            display: flex;
            align-items: center;
            padding-right: 2rem;
            text-align: left;
            cursor: pointer;
        }
        .lm-create-modal__panel input[type="file"].landmark-form-control {
            padding: 0 12px 0 0;
            line-height: 38px;
        }
        .lm-create-modal__panel input[type="file"].landmark-form-control::file-selector-button {
            height: 38px;
            margin: 0 12px 0 0;
            padding: 0 12px;
            border: 0;
            border-right: 1px solid #cbd5e1;
            background: #f3f4f6;
            color: #111827;
            font: inherit;
            cursor: pointer;
        }
        .lm-create-modal__panel input[type="file"].landmark-form-control::-webkit-file-upload-button {
            height: 38px;
            margin: 0 12px 0 0;
            padding: 0 12px;
            border: 0;
            border-right: 1px solid #cbd5e1;
            background: #f3f4f6;
            color: #111827;
            font: inherit;
            cursor: pointer;
        }
        .lm-edit-modal__panel .edit-landmark-control {
            box-sizing: border-box;
            width: 100%;
            height: 40px;
            min-height: 40px;
            padding: 0 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            outline: none;
            background: #fff;
            color: #111827;
            box-shadow: none;
            font-family: inherit;
            font-size: 14px;
            line-height: 1;
        }
        .lm-edit-modal__panel .edit-landmark-control:focus,
        .lm-edit-modal__panel .edit-landmark-control:focus-visible {
            border-color: #cbd5e1 !important;
            outline: none !important;
            box-shadow: none !important;
        }
        .lm-edit-modal__panel .lm-edit-category-dropdown {
            width: 100%;
            min-width: 0;
            height: 40px;
            min-height: 40px;
            flex: none;
        }
        .lm-edit-modal__panel .lm-edit-category-dropdown .edit-landmark-control {
            display: flex;
            align-items: center;
            padding-right: 2rem;
            text-align: left;
            cursor: pointer;
        }
        .lm-edit-modal__panel input[type="file"].edit-landmark-control {
            padding: 0 12px 0 0;
            line-height: 38px;
        }
        .lm-edit-modal__panel input[type="file"].edit-landmark-control::file-selector-button {
            height: 38px;
            margin: 0 12px 0 0;
            padding: 0 12px;
            border: 0;
            border-right: 1px solid #cbd5e1;
            background: #f3f4f6;
            color: #111827;
            font: inherit;
            cursor: pointer;
        }
        .lm-edit-modal__panel input[type="file"].edit-landmark-control::-webkit-file-upload-button {
            height: 38px;
            margin: 0 12px 0 0;
            padding: 0 12px;
            border: 0;
            border-right: 1px solid #cbd5e1;
            background: #f3f4f6;
            color: #111827;
            font: inherit;
            cursor: pointer;
        }
        .lm-create-modal__panel button[type="submit"],
        .lm-edit-modal__panel button[type="submit"] {
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
        .lm-create-modal__panel button[type="submit"]:hover,
        .lm-edit-modal__panel button[type="submit"]:hover { background: #F3C96A; }
        .lm-edit-modal__actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: .55rem;
            margin-top: 1.35rem;
        }
        .lm-edit-modal__actions button { margin-top: 0 !important; }
        .lm-edit-modal__cancel {
            background: #f9fafb;
            color: #374151;
            padding: .55rem 1.1rem;
            font-size: .9rem;
            line-height: normal;
            border-radius: 8px;
            font-weight: 700;
            border: 1px solid #d1d5db;
            cursor: pointer;
            font-family: inherit;
        }
        .lm-edit-modal__cancel:hover { background: #f3f4f6; }
        .lm-create-modal__close,
        .lm-edit-modal__close {
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
        .lm-create-modal__close:focus-visible,
        .lm-edit-modal__close:hover,
        .lm-edit-modal__close:focus-visible { color: #111827; }
        .lm-create-modal__hint,
        .lm-edit-modal__hint {
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
            border-color: #d1d5db !important;
            box-shadow: none !important;
            outline: none !important;
            background: #f9fafb;
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
            padding: 0;
            border-radius: 14px;
            max-width: min(720px, 100%);
            width: 100%;
            max-height: min(90vh, 880px);
            overflow: hidden;
            box-shadow: 0 16px 40px rgba(0,0,0,.12);
            position: relative;
            font-family: inherit;
            display: flex;
            flex-direction: column;
        }
        .lm-view-modal__close {
            position: static;
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 1.55rem;
            font-weight: 700;
            color: #6b7280;
            cursor: pointer;
            line-height: 1;
            border: none;
            background: #fff;
            padding: 0;
            font-family: inherit;
        }
        .lm-view-modal__close:hover,
        .lm-view-modal__close:focus-visible { color: #111827; background: #f9fafb; }
        .lm-view-modal__header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            flex-shrink: 0;
            margin: 0;
            padding: 1.25rem 1.35rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            background: #fff;
        }
        .lm-view-modal__heading {
            min-width: 0;
        }
        .lm-view-modal__top-actions {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            flex-shrink: 0;
            z-index: 2;
        }
        .lm-view-modal__top-actions .land-delete-form {
            margin: 0;
            display: inline-flex;
        }
        .lm-view-modal__action {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
            color: #7A2E1F;
            padding: .48rem .72rem;
            font: inherit;
            font-size: .9rem;
            font-weight: 800;
            line-height: 1;
            cursor: pointer;
        }
        .lm-view-modal__action--edit {
            background: #f3f4f6;
            border-color: #e5e7eb;
            color: #374151;
        }
        .lm-view-modal__action--edit:hover { background: #e5e7eb; }
        .lm-view-modal__action--delete {
            color: #991b1b;
            border-color: #fecaca;
            background: #fff;
        }
        .lm-view-modal__action--delete:hover { background: #fef2f2; }
        .lm-view-modal__action:hover {
            transform: translateY(-1px);
        }
        .lm-view-modal__eyebrow {
            display: none;
            margin: 0 0 .35rem;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #A67C52;
        }
        .lm-view-modal__title {
            margin: 0;
            font-size: clamp(1.35rem, 3vw, 1.65rem);
            font-weight: 800;
            color: #7A2E1F;
            line-height: 1.2;
            letter-spacing: -0.02em;
        }
        .lm-view-modal__body {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            padding: 1rem 1.35rem 1.35rem;
        }
        @media (max-width: 640px) {
            .lm-view-modal__header {
                flex-direction: column;
                align-items: stretch;
            }
            .lm-view-modal__top-actions {
                justify-content: flex-end;
            }
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
            align-items: center;
            flex-wrap: nowrap;
            gap: 8px;
        }
        .lm-view-btn-approve {
            height: 30px;
            min-width: 64px;
            padding: 0 12px;
            border-radius: 6px;
            border: 0;
            background: #166534;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            font-size: 13px;
        }
        .lm-view-btn-approve:hover {
            background: #14532d;
        }
        .lm-view-btn-reject {
            height: 30px;
            min-width: 60px;
            padding: 0 12px;
            border-radius: 6px;
            border: 1px solid #fca5a5;
            background: #fff;
            color: #b91c1c;
            font-weight: 600;
            cursor: pointer;
            font-size: 13px;
        }
        .lm-view-btn-reject:hover {
            background: #fef2f2;
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
        .land-wrap--approval .land-expand-btn,
        .land-wrap--sitemanager .land-expand-btn {
            padding: .35rem .65rem;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            font-weight: 700;
            font-size: .78rem;
            cursor: pointer;
            background: #fff;
            color: #374151;
            text-decoration: none;
        }
        .land-wrap--approval .land-expand-btn:hover,
        .land-wrap--sitemanager .land-expand-btn:hover { background: #f9fafb; }
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
            padding: .45rem .8rem;
            font-weight: 700;
            font-size: .85rem;
            line-height: 1;
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
            border-color: #ef4444;
            color: #fff;
        }
        .land-delete-modal__btn.confirm:hover { background: #dc2626; border-color: #dc2626; }
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
        .land-controls {
            display: grid;
            grid-template-columns: minmax(220px, 1fr) repeat(3, minmax(145px, max-content));
            gap: .65rem;
            align-items: center;
            margin: .85rem 0 .7rem;
        }
        .land-controls--approval {
            display: flex;
            flex-wrap: nowrap;
            align-items: flex-end;
        }
        .land-controls input,
        .land-controls select {
            height: 40px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
            color: #374151;
            font: inherit;
            font-size: .88rem;
            font-weight: 600;
        }
        .land-controls input {
            width: 100%;
            padding: 0 .8rem;
        }
        .land-controls input[type="search"]::-webkit-search-cancel-button,
        .land-controls input[type="search"]::-webkit-search-decoration {
            -webkit-appearance: none;
            appearance: none;
        }
        .land-controls select {
            min-width: 145px;
            padding: 0 2rem 0 .7rem;
            cursor: pointer;
        }
        .land-controls button[type="submit"] {
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 .95rem;
            border: 1px solid #F3C96A;
            border-radius: 8px;
            background: #E8B34B;
            color: #7A2E1F;
            font: inherit;
            font-size: .88rem;
            font-weight: 800;
            cursor: pointer;
            white-space: nowrap;
        }
        .land-controls button[type="submit"]:hover { background: #F3C96A; }
        .land-controls--approval input[type="search"] {
            width: 350px;
            flex: 0 0 350px;
        }
        .land-controls--approval select {
            width: 140px;
            flex: 0 0 140px;
        }
        .land-controls--approval button[type="submit"],
        .land-controls--approval .land-clear-btn {
            flex: 0 0 auto;
            width: auto;
        }
        .land-controls--approval input:focus,
        .land-controls--approval select:focus,
        .land-controls--approval button:focus,
        .land-controls--approval .land-clear-btn:focus {
            border-color: #d1d5db !important;
            box-shadow: none !important;
            outline: none !important;
        }
        .land-wrap--sitemanager .land-controls {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
        }
        .land-wrap--sitemanager .land-controls input[type="search"] {
            width: 350px;
            flex: 0 0 350px;
        }
        .land-wrap--sitemanager .land-filter-dropdown {
            width: 150px;
            min-width: 150px;
            flex: 0 0 150px;
            height: 40px;
        }
        .land-controls--approval .land-filter-dropdown {
            width: 150px;
            min-width: 150px;
            flex: 0 0 150px;
            height: 40px;
        }
        .land-controls--approval .land-filter-dropdown__toggle {
            height: 40px;
        }
        .land-wrap--sitemanager .land-controls .land-filter-dropdown__toggle {
            display: flex;
            align-items: center;
            height: 40px;
            min-height: 40px;
            padding: 0 2rem 0 .8rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
            color: #111827;
            line-height: 1;
            outline: none;
            box-shadow: none;
        }
        .land-wrap--sitemanager .land-controls .land-filter-dropdown__toggle:focus,
        .land-wrap--sitemanager .land-controls .land-filter-dropdown__toggle:focus-visible {
            border-color: #d1d5db;
            outline: none;
            box-shadow: none;
        }
        .land-wrap--sitemanager .lm-create-category-dropdown {
            width: 100%;
            min-width: 0;
            flex: none;
            height: 40px;
            min-height: 40px;
        }
        .land-wrap--sitemanager .lm-edit-modal__panel .lm-edit-category-dropdown {
            display: block;
            box-sizing: border-box;
            width: 100%;
            min-width: 100%;
            height: 40px;
            min-height: 40px;
            flex: none;
        }
        .land-filter-dropdown {
            position: relative;
            display: block;
            height: 46px;
        }
        .land-filter-dropdown__native {
            position: absolute;
            inset: 0;
            opacity: 0;
            pointer-events: none;
        }
        .land-filter-dropdown__toggle {
            position: relative;
            display: block;
            box-sizing: border-box;
            width: 100%;
            height: 46px;
            padding: .62rem 2rem .62rem .72rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
            color: #111827;
            font: inherit;
            font-size: .9rem;
            font-weight: 700;
            line-height: 1;
            text-align: left;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer;
        }
        .land-filter-dropdown__arrow {
            position: absolute;
            z-index: 1;
            top: 1px;
            right: 1px;
            width: 2.5rem;
            height: calc(100% - 2px);
            padding: 0;
            border: 0;
            border-radius: 0 7px 7px 0;
            background: transparent;
            color: #374151;
            cursor: pointer;
        }
        .land-filter-dropdown__arrow::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: .45rem;
            height: .45rem;
            border-right: 2px solid currentColor;
            border-bottom: 2px solid currentColor;
            transform: translate(-50%, -70%) rotate(45deg);
        }
        .land-filter-dropdown.is-open .land-filter-dropdown__arrow::after {
            transform: translate(-50%, -30%) rotate(225deg);
        }
        .land-filter-dropdown__arrow:focus-visible {
            outline: 2px solid #9ca3af;
            outline-offset: -3px;
        }
        .land-filter-dropdown__toggle:focus,
        .land-filter-dropdown__toggle:focus-visible {
            border-color: #d1d5db;
            outline: none;
            box-shadow: none;
        }
        .land-filter-dropdown-menu {
            position: fixed;
            z-index: 9999;
            box-sizing: border-box;
            max-height: 322px;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #c7c7c7 transparent;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: #fff;
            box-shadow: 0 8px 20px rgba(0, 0, 0, .15);
            list-style: none;
        }
        .land-filter-dropdown-menu[hidden] { display: none; }
        .land-filter-dropdown-menu::-webkit-scrollbar { width: 6px; }
        .land-filter-dropdown-menu::-webkit-scrollbar-thumb { background: #c7c7c7; border-radius: 999px; }
        .land-filter-dropdown-menu::-webkit-scrollbar-track { background: transparent; }
        .land-filter-dropdown-menu__option {
            display: block;
            width: 100%;
            height: 40px;
            padding: 0 .72rem;
            border: 0;
            background: transparent;
            color: #111827;
            font: inherit;
            font-size: .9rem;
            line-height: 40px;
            text-align: left;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer;
        }
        .land-filter-dropdown-menu__option:hover,
        .land-filter-dropdown-menu__option:focus,
        .land-filter-dropdown-menu__option[aria-selected="true"] {
            background: #f3f4f6;
            outline: none;
        }
        .land-wrap--sitemanager .land-controls input[type="search"]:focus,
        .land-wrap--sitemanager .land-controls select:focus {
            border-color: #d1d5db !important;
            box-shadow: none !important;
            outline: none !important;
        }
        .land-wrap--sitemanager .land-controls button[type="submit"] {
            flex: 0 0 auto;
        }
        .land-wrap--sitemanager .land-controls .land-clear-btn {
            flex: 0 0 auto;
        }
        .land-clear-btn {
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 .85rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
            color: #374151;
            font-size: .88rem;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
        }
        .land-clear-btn:hover { background: #e5e7eb; }
        .land-controls input:focus,
        .land-controls select:focus {
            outline: none;
            border-color: #E8B34B;
            box-shadow: 0 0 0 3px rgba(232, 179, 75, .22);
        }
        @media (max-width: 900px) {
            .land-controls {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 560px) {
            .land-controls {
                grid-template-columns: 1fr;
            }
            .land-controls select {
                width: 100%;
            }
        }
    </style>
    <div class="land-wrap {{ $isLandmarkApprovalQueue ? 'land-wrap--approval' : ($panelRoutePrefix === 'sitemanager' ? 'land-wrap--sitemanager' : '') }} {{ $panelRoutePrefix === 'admin' && $isLandmarkApprovalQueue ? 'admin-landmarks-page content-wrapper' : '' }}">
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
        </div>
    </div>

    @if ($isLandmarkApprovalQueue)
        <form method="GET" action="{{ route('admin.landmarks') }}" class="land-controls land-controls--approval land-controls--manual">
            <input
                type="search"
                name="search"
                value="{{ $landmarkSearch }}"
                placeholder="Search landmarks..."
                aria-label="Search landmarks">

            <span class="land-filter-dropdown">
                <select class="land-filter-dropdown__native" name="status" aria-label="Filter by status">
                    <option value="all" @selected($landmarkStatusFilter === 'all')>All Status</option>
                    <option value="pending" @selected($landmarkStatusFilter === 'pending')>Pending</option>
                    <option value="active" @selected($landmarkStatusFilter === 'active')>Approved</option>
                    <option value="rejected" @selected($landmarkStatusFilter === 'rejected')>Rejected</option>
                </select>
                <button class="land-filter-dropdown__toggle" type="button" aria-haspopup="listbox" aria-expanded="false" aria-label="Filter by status"></button>
                <button class="land-filter-dropdown__arrow custom-select-arrow" type="button" aria-label="Toggle options" aria-expanded="false"></button>
                <ul class="land-filter-dropdown-menu" role="listbox" aria-label="Status options" hidden></ul>
            </span>

            <span class="land-filter-dropdown">
                <select class="land-filter-dropdown__native" name="category" aria-label="Filter by category">
                    <option value="all" @selected($landmarkCategoryFilter === 'all')>All Category</option>
                    <option value="historical" @selected($landmarkCategoryFilter === 'historical')>Historical</option>
                    <option value="religious" @selected($landmarkCategoryFilter === 'religious')>Religious</option>
                    <option value="modern" @selected($landmarkCategoryFilter === 'modern')>Modern</option>
                    <option value="natural" @selected($landmarkCategoryFilter === 'natural')>Natural</option>
                    <option value="cultural" @selected($landmarkCategoryFilter === 'cultural')>Cultural</option>
                    <option value="others" @selected($landmarkCategoryFilter === 'others')>Others</option>
                </select>
                <button class="land-filter-dropdown__toggle" type="button" aria-haspopup="listbox" aria-expanded="false" aria-label="Filter by category"></button>
                <button class="land-filter-dropdown__arrow custom-select-arrow" type="button" aria-label="Toggle options" aria-expanded="false"></button>
                <ul class="land-filter-dropdown-menu" role="listbox" aria-label="Category options" hidden></ul>
            </span>

            <button type="submit">Search</button>

            <a href="{{ route('admin.landmarks') }}" class="land-clear-btn">
                Clear
            </a>
        </form>
    @endif

    @if ($panelRoutePrefix === 'sitemanager' && ! $isLandmarkApprovalQueue)
        <form method="GET" action="{{ route('sitemanager.landmarks') }}" class="land-controls land-controls--manual">
            <input
                type="search"
                name="search"
                value="{{ $landmarkSearch }}"
                placeholder="Search landmarks..."
                aria-label="Search landmarks">

            <span class="land-filter-dropdown">
                <select class="land-filter-dropdown__native" name="category" aria-label="Filter by category">
                    <option value="all" @selected($landmarkCategoryFilter === 'all')>All Category</option>
                    <option value="historical" @selected($landmarkCategoryFilter === 'historical')>Historical</option>
                    <option value="religious" @selected($landmarkCategoryFilter === 'religious')>Religious</option>
                    <option value="natural" @selected($landmarkCategoryFilter === 'natural')>Natural</option>
                    <option value="modern" @selected($landmarkCategoryFilter === 'modern')>Modern</option>
                </select>
                <button class="land-filter-dropdown__toggle" type="button" aria-haspopup="listbox" aria-expanded="false" aria-label="Filter by category"></button>
                <button class="land-filter-dropdown__arrow" type="button" aria-label="Toggle category options" aria-expanded="false"></button>
                <ul class="land-filter-dropdown-menu" role="listbox" aria-label="Category options" hidden></ul>
            </span>

            <span class="land-filter-dropdown">
                <select class="land-filter-dropdown__native" name="status" aria-label="Filter by status">
                    <option value="all" @selected($landmarkStatusFilter === 'all')>All Status</option>
                    <option value="active" @selected($landmarkStatusFilter === 'active')>Active</option>
                    <option value="pending" @selected($landmarkStatusFilter === 'pending')>Pending Approval</option>
                    <option value="rejected" @selected($landmarkStatusFilter === 'rejected')>Rejected</option>
                </select>
                <button class="land-filter-dropdown__toggle" type="button" aria-haspopup="listbox" aria-expanded="false" aria-label="Filter by status"></button>
                <button class="land-filter-dropdown__arrow" type="button" aria-label="Toggle status options" aria-expanded="false"></button>
                <ul class="land-filter-dropdown-menu" role="listbox" aria-label="Status options" hidden></ul>
            </span>

            <span class="land-filter-dropdown">
                <select class="land-filter-dropdown__native" name="order" aria-label="Order landmarks">
                    <option value="default" @selected($landmarkOrder === 'default')>Default order</option>
                    <option value="name_az" @selected($landmarkOrder === 'name_az')>Name A-Z</option>
                    <option value="name_za" @selected($landmarkOrder === 'name_za')>Name Z-A</option>
                    <option value="newest" @selected($landmarkOrder === 'newest')>Newest</option>
                    <option value="oldest" @selected($landmarkOrder === 'oldest')>Oldest</option>
                </select>
                <button class="land-filter-dropdown__toggle" type="button" aria-haspopup="listbox" aria-expanded="false" aria-label="Order landmarks"></button>
                <button class="land-filter-dropdown__arrow" type="button" aria-label="Toggle order options" aria-expanded="false"></button>
                <ul class="land-filter-dropdown-menu" role="listbox" aria-label="Order options" hidden></ul>
            </span>

            <button type="submit">Search</button>

            <a href="{{ route('sitemanager.landmarks') }}" class="land-clear-btn">
                Clear
            </a>
        </form>
    @endif

    @if ($landmarks->isEmpty())
        <p class="empty-box">No landmarks found.</p>
    @else

        <div id="list-view" class="table-wrap">
                <table class="land-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Location</th>
                            <th>Category</th>
                            <th>Status</th>
                            @if ($panelRoutePrefix === 'sitemanager' || $isLandmarkApprovalQueue)
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
                                $imageSrc = $data['image_path'] ?? null;

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
                                @if ($panelRoutePrefix === 'sitemanager' || $isLandmarkApprovalQueue)
                                    <td>
                                        <div class="land-row-actions">
                                            <button type="button"
                                                    class="land-expand-btn"
                                                    data-landmark-expand
                                                    data-modal-id="{{ $viewModalId }}"
                                                    data-landmark-id="{{ $lid }}"
                                                    aria-haspopup="dialog"
                                                    aria-controls="{{ $viewModalId }}">
                                                View
                                            </button>
                                        </div>
                                    </td>
                                @endif
                            </tr>

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
                                @if ($panelRoutePrefix === 'sitemanager')
                                    <div id="lmEditLandmarkModal_{{ $modalSafe }}"
                                         class="lm-edit-modal"
                                         role="dialog"
                                         aria-modal="true"
                                         aria-labelledby="lmEditLandmarkHeading_{{ $modalSafe }}"
                                         aria-hidden="true">
                                        <div class="lm-edit-modal__panel">
                                            <button type="button"
                                                    class="lm-edit-modal__close"
                                                    onclick="lmCloseEditModal('lmEditLandmarkModal_{{ $modalSafe }}')"
                                                    aria-label="Close">&times;</button>
                                            <h3 id="lmEditLandmarkHeading_{{ $modalSafe }}">Edit Landmark</h3>

                                            <form method="POST"
                                                  action="{{ route('sitemanager.landmarks.update', $lid) }}"
                                                  enctype="multipart/form-data"
                                                  data-landmark-edit-form>
                                                @csrf
                                                @method('PUT')

                                                <label for="lm-edit-name-{{ $modalSafe }}">Landmark Name</label>
                                                <input id="lm-edit-name-{{ $modalSafe }}" class="edit-landmark-control" type="text" name="name" value="{{ $data['name'] ?? '' }}" required autocomplete="organization">

                                                <label for="lm-edit-category-{{ $modalSafe }}">Category</label>
                                                <span class="land-filter-dropdown lm-edit-category-dropdown">
                                                    <select id="lm-edit-category-{{ $modalSafe }}"
                                                            class="land-filter-dropdown__native"
                                                            name="category"
                                                            required>
                                                        @foreach (['Historical', 'Natural', 'Cultural', 'Religious', 'Modern'] as $cat)
                                                            <option value="{{ $cat }}" {{ strcasecmp((string) ($data['category'] ?? ''), $cat) === 0 ? 'selected' : '' }}>{{ $cat }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button class="land-filter-dropdown__toggle edit-landmark-control"
                                                            type="button"
                                                            aria-haspopup="listbox"
                                                            aria-expanded="false"
                                                            aria-label="Select landmark category"></button>
                                                    <button class="land-filter-dropdown__arrow"
                                                            type="button"
                                                            aria-label="Toggle landmark category options"
                                                            aria-expanded="false"></button>
                                                    <ul class="land-filter-dropdown-menu"
                                                        role="listbox"
                                                        aria-label="Landmark category options"
                                                        hidden></ul>
                                                </span>

                                                <label for="lm-edit-description-{{ $modalSafe }}">Description</label>
                                                <textarea id="lm-edit-description-{{ $modalSafe }}" name="description" rows="4">{{ $data['description'] ?? '' }}</textarea>

                                                <label for="lm-edit-location-{{ $modalSafe }}">Location</label>
                                                <div class="lm-create-location-map-wrap"
                                                     data-edit-location-picker
                                                     data-modal-id="lmEditLandmarkModal_{{ $modalSafe }}"
                                                     data-map-id="lm-edit-map-{{ $modalSafe }}"
                                                     data-search-id="lm-edit-location-search-{{ $modalSafe }}"
                                                     data-results-id="lm-edit-location-results-{{ $modalSafe }}"
                                                     data-location-id="lm-edit-location-{{ $modalSafe }}"
                                                     data-lat-id="lm-edit-lat-{{ $modalSafe }}"
                                                     data-lng-id="lm-edit-lng-{{ $modalSafe }}"
                                                     data-category-id="lm-edit-category-{{ $modalSafe }}"
                                                     data-initial-lat="{{ $data['latitude'] ?? $data['lati'] ?? '' }}"
                                                     data-initial-lng="{{ $data['longitude'] ?? $data['longti'] ?? '' }}">
                                                    <div class="lm-create-location-search">
                                                        <label for="lm-edit-location-search-{{ $modalSafe }}">Search location</label>
                                                        <input id="lm-edit-location-search-{{ $modalSafe }}" type="search" value="{{ $locationLabel }}" autocomplete="off" placeholder="Search Cebu landmark or place..." aria-controls="lm-edit-location-results-{{ $modalSafe }}" aria-expanded="false">
                                                        <div id="lm-edit-location-results-{{ $modalSafe }}" class="lm-create-location-results" role="listbox"></div>
                                                    </div>
                                                    <div id="lm-edit-map-{{ $modalSafe }}" class="lm-create-location-map"></div>
                                                </div>
                                                <input id="lm-edit-location-{{ $modalSafe }}" type="hidden" name="location" value="{{ $locationLabel }}">
                                                <input id="lm-edit-lat-{{ $modalSafe }}" type="hidden" name="latitude" value="{{ $data['latitude'] ?? $data['lati'] ?? '' }}">
                                                <input id="lm-edit-lng-{{ $modalSafe }}" type="hidden" name="longitude" value="{{ $data['longitude'] ?? $data['longti'] ?? '' }}">

                                                <label for="lm-edit-image-{{ $modalSafe }}">Landmark photo</label>
                                                <input id="lm-edit-image-{{ $modalSafe }}" class="edit-landmark-control" type="file" name="image" accept="image/*" data-max-bytes="524288">
                                                <p class="lm-edit-modal__hint">Leave blank to keep the current photo.</p>

                                                <label for="lm-edit-evidence-{{ $modalSafe }}">Evidence / supporting documents</label>
                                                <input id="lm-edit-evidence-{{ $modalSafe }}" class="edit-landmark-control" type="file" name="evidence_files[]" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,image/*,application/pdf" multiple data-max-files="5" data-max-file-bytes="5242880">
                                                <p class="lm-edit-modal__hint">Leave blank to keep the current evidence files.</p>

                                                <div class="lm-edit-modal__actions">
                                                    <button type="button" class="lm-edit-modal__cancel" onclick="lmCloseEditModal('lmEditLandmarkModal_{{ $modalSafe }}')">Cancel</button>
                                                    <button type="submit">Save changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endif
                            @endif
                        @endforeach
                    </tbody>
                </table>
        </div>

        @if (method_exists($landmarks, 'currentPage')
            && method_exists($landmarks, 'lastPage')
            && (! $isLandmarkApprovalQueue || $landmarks->hasPages()))
            <nav class="pager" aria-label="Landmarks pagination">
                @if ($landmarks->onFirstPage())
                    <span class="pager-btn disabled">← Prev</span>
                @else
                    <a class="pager-btn active" href="{{ $landmarks->previousPageUrl() }}">← Prev</a>
                @endif

                <span class="pager-text">Page {{ $landmarks->currentPage() }} of {{ $landmarks->lastPage() }}</span>

                @if ($landmarks->hasMorePages())
                    <a class="pager-btn active" href="{{ $landmarks->nextPageUrl() }}">Next →</a>
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
                <h3 id="landDeleteModalTitle" class="land-delete-modal__title">Delete this landmark?</h3>
                <p class="land-delete-modal__message">Are you sure you want to delete "<strong class="land-delete-modal__name" id="landDeleteModalName"></strong>"?</p>
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
                    <input id="lm-create-name" class="landmark-form-control" type="text" name="name" value="{{ old('name') }}" required autocomplete="organization">

                    <label for="lm-create-category">Category</label>
                    <span class="land-filter-dropdown lm-create-category-dropdown">
                        <select id="lm-create-category"
                                class="land-filter-dropdown__native"
                                name="category"
                                required>
                            @foreach (['Historical', 'Natural', 'Cultural', 'Religious', 'Modern'] as $cat)
                                <option value="{{ $cat }}" {{ old('category', 'Historical') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                        <button class="land-filter-dropdown__toggle landmark-form-control"
                                type="button"
                                aria-haspopup="listbox"
                                aria-expanded="false"
                                aria-label="Select landmark category"></button>
                        <button class="land-filter-dropdown__arrow"
                                type="button"
                                aria-label="Toggle landmark category options"
                                aria-expanded="false"></button>
                        <ul class="land-filter-dropdown-menu"
                            role="listbox"
                            aria-label="Landmark category options"
                            hidden></ul>
                    </span>

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
                    <input id="lm-create-image" class="landmark-form-control" type="file" name="image" accept="image/*" data-max-bytes="524288">

                    <label for="lm-create-evidence">Evidence / supporting documents</label>
                    <input id="lm-create-evidence" class="landmark-form-control" type="file" name="evidence_files[]" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,image/*,application/pdf" multiple required data-max-files="5" data-max-file-bytes="5242880">

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

        function smLandmarksIndexPath() {
            try {
                return new URL(smLandmarksIndexUrl, window.location.origin).pathname.replace(/\/$/, '');
            } catch (e) {
                return String(smLandmarksIndexUrl).replace(/^https?:\/\/[^/]+/i, '').split(/[?#]/)[0].replace(/\/$/, '');
            }
        }

        function smLandmarksIndexHistoryUrl() {
            return smLandmarksIndexPath() + window.location.search;
        }

        function smLandmarkShowUrl(landmarkId) {
            return smLandmarkShowUrlTemplate.replace('__ID__', encodeURIComponent(landmarkId));
        }

        function smInitLandmarkListExpansion() {
            var buttons = document.querySelectorAll('[data-landmark-expand]');
            if (!buttons.length) return;

            buttons.forEach(function (button) {
                button.addEventListener('click', function () {
                    smOpenLandmarkViewModal(button.dataset.modalId, button.dataset.landmarkId);
                });
            });
        }

        function smInitLandmarkControls() {
            var form = document.querySelector('.land-controls');
            if (!form) return;
            if (form.classList.contains('land-controls--manual')) return;

            var search = form.querySelector('input[name="search"]');
            var timer = null;
            if (!search) return;

            search.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(function () {
                    form.submit();
                }, 300);
            });
        }

        function smInitLandmarkDeleteModal() {
            var modal = document.getElementById('landDeleteModal');
            var nameEl = document.getElementById('landDeleteModalName');
            var cancelBtn = document.getElementById('cancelLandDelete');
            var confirmBtn = document.getElementById('confirmLandDelete');
            var pendingForm = null;
            var pendingViewModal = null;
            if (!modal || !nameEl || !cancelBtn || !confirmBtn) return;

            function openModal(form, viewModal, landmarkName) {
                pendingForm = form;
                pendingViewModal = viewModal;
                nameEl.textContent = landmarkName || 'This landmark';
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                confirmBtn.focus();
            }

            function closeModal() {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                pendingForm = null;
                pendingViewModal = null;
            }

            document.querySelectorAll('[data-landmark-delete]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var form = document.getElementById(button.dataset.deleteFormId);
                    openModal(form, button.closest('.lm-view-modal'), button.dataset.landmarkName || 'This landmark');
                });
            });

            cancelBtn.addEventListener('click', closeModal);
            confirmBtn.addEventListener('click', function () {
                if (!pendingForm) return;

                var form = pendingForm;
                var viewModal = pendingViewModal;
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                if (viewModal) {
                    viewModal.style.display = 'none';
                    viewModal.setAttribute('aria-hidden', 'true');
                }
                document.body.style.overflow = '';
                pendingForm = null;
                pendingViewModal = null;

                HTMLFormElement.prototype.submit.call(form);
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
            var indexPath = smLandmarksIndexPath();
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
            var editModals = document.querySelectorAll('.lm-edit-modal');
            for (var i = 0; i < editModals.length; i++) {
                if (editModals[i].style.display === 'flex') return true;
            }
            var viewModals = document.querySelectorAll('.lm-view-modal');
            for (var j = 0; j < viewModals.length; j++) {
                if (viewModals[j].style.display === 'flex') return true;
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
                    history.replaceState(null, '', smLandmarksIndexHistoryUrl());
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

        function lmOpenEditModal(modalId, viewModalId) {
            if (viewModalId) {
                smCloseLandmarkViewModal(viewModalId, false);
            }
            var modal = document.getElementById(modalId);
            if (!modal) return;
            modal.dataset.returnViewModalId = viewModalId || '';
            modal.style.display = 'flex';
            modal.setAttribute('aria-hidden', 'false');
            lmInitEditMap(modalId);
            smSyncBodyScrollLock();
        }

        function lmCloseEditModal(modalId) {
            var modal = document.getElementById(modalId);
            if (!modal) return;
            var form = modal.querySelector('form');
            if (form) form.reset();
            var picker = typeof lmEditPickers !== 'undefined' ? lmEditPickers[modalId] : null;
            if (picker && picker.map) {
                picker.map.remove();
                delete lmEditPickers[modalId];
            }
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            var viewModalId = modal.dataset.returnViewModalId || '';
            modal.dataset.returnViewModalId = '';
            if (viewModalId) {
                smOpenLandmarkViewModal(viewModalId, null, false);
            } else {
                smSyncBodyScrollLock();
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
        var lmEditPickers = {};
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
            lmSuggestLocationCategory(document.getElementById('lm-create-category'), feature);
        }

        function lmSuggestLocationCategory(select, feature) {
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
                    select.dispatchEvent(new Event('change', { bubbles: true }));
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

        function lmSetEditLocationSearchValue(picker, value) {
            if (picker.searchInput) picker.searchInput.value = value;
            if (picker.locationInput) picker.locationInput.value = value;
        }

        function lmSetEditCoordinates(picker, lngLat) {
            if (!picker.latInput || !picker.lngInput) return;
            picker.latInput.value = Number(lngLat.lat).toFixed(6);
            picker.lngInput.value = Number(lngLat.lng).toFixed(6);
        }

        function lmCloseEditLocationResults(picker) {
            if (picker.searchInput) picker.searchInput.setAttribute('aria-expanded', 'false');
            if (picker.resultsEl) {
                picker.resultsEl.classList.remove('is-open');
                picker.resultsEl.replaceChildren();
            }
            picker.searchFeatures = [];
        }

        function lmSuggestEditCategory(picker, feature) {
            lmSuggestLocationCategory(picker.categorySelect, feature);
        }

        function lmSelectEditLocation(picker, feature) {
            if (!feature || !Array.isArray(feature.center) || feature.center.length < 2) return;
            clearTimeout(picker.geocodeTimer);
            picker.geocodeRequestId++;
            lmSetEditLocationSearchValue(picker, feature.place_name || feature.text || '');
            lmSuggestEditCategory(picker, feature);
            lmCloseEditLocationResults(picker);
            lmMoveEditMarker(picker, feature.center[0], feature.center[1], 15);
        }

        function lmMoveEditMarker(picker, lng, lat, zoom) {
            if (!picker.map || !picker.marker) return;
            var lngLat = { lng: Number(lng), lat: Number(lat) };
            if (!Number.isFinite(lngLat.lng) || !Number.isFinite(lngLat.lat)) return;
            picker.marker.setLngLat([lngLat.lng, lngLat.lat]);
            lmSetEditCoordinates(picker, lngLat);
            picker.map.flyTo({
                center: [lngLat.lng, lngLat.lat],
                zoom: zoom || 15,
                essential: true
            });
        }

        function lmEditRenderedLandmarkLabel(picker, point) {
            if (!picker.map || !point) return '';

            var radius = 36;
            var features = picker.map.queryRenderedFeatures([
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
            lmSuggestEditCategory(picker, landmarks[0]);
            var properties = landmarks[0].properties || {};
            return properties.name || properties.name_en || properties.name_und || '';
        }

        function lmReverseGeocodeEditLocation(picker, lngLat, requestId, renderedLandmarkLabel) {
            if (renderedLandmarkLabel) {
                lmSetEditLocationSearchValue(picker, renderedLandmarkLabel);
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
                    if (requestId !== picker.geocodeRequestId) return;
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
                    lmSetEditLocationSearchValue(picker, label);
                    if (landmark) lmSuggestEditCategory(picker, landmark);
                })
                .catch(function () {});
        }

        function lmHandleManualEditLocation(picker, lngLat, moveMarker, point) {
            var location = { lng: Number(lngLat.lng), lat: Number(lngLat.lat) };
            if (!Number.isFinite(location.lng) || !Number.isFinite(location.lat)) return;

            clearTimeout(picker.geocodeTimer);
            var requestId = ++picker.geocodeRequestId;
            lmCloseEditLocationResults(picker);
            lmSetEditLocationSearchValue(picker, '');
            if (moveMarker && picker.marker) picker.marker.setLngLat([location.lng, location.lat]);
            lmSetEditCoordinates(picker, location);
            var renderedLandmarkLabel = lmEditRenderedLandmarkLabel(picker, point || picker.map.project(location));
            lmReverseGeocodeEditLocation(picker, location, requestId, renderedLandmarkLabel);
        }

        function lmRenderEditLocationResults(picker, features) {
            if (!picker.searchInput || !picker.resultsEl) return;

            picker.searchFeatures = features;
            picker.resultsEl.replaceChildren();

            features.forEach(function (feature, index) {
                var option = document.createElement('button');
                option.type = 'button';
                option.className = 'lm-create-location-result';
                option.setAttribute('role', 'option');
                option.textContent = feature.place_name || feature.text || '';
                option.addEventListener('click', function () {
                    lmSelectEditLocation(picker, picker.searchFeatures[index]);
                });
                picker.resultsEl.appendChild(option);
            });

            var hasResults = features.length > 0;
            picker.resultsEl.classList.toggle('is-open', hasResults);
            picker.searchInput.setAttribute('aria-expanded', hasResults ? 'true' : 'false');
        }

        function lmSearchEditLocations(picker, searchValue) {
            var query = String(searchValue || '').trim();
            if (query.length < 3 || !picker.map || !picker.marker) {
                lmCloseEditLocationResults(picker);
                return;
            }

            var requestId = ++picker.geocodeRequestId;
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
                proximity: picker.startLng + ',' + picker.startLat
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
                    if (requestId !== picker.geocodeRequestId) return;
                    var features = payload && payload.features
                        ? payload.features.filter(lmFeatureIsInCebu)
                        : [];
                    lmRenderEditLocationResults(picker, features);
                })
                .catch(function () {
                    if (requestId === picker.geocodeRequestId) lmCloseEditLocationResults(picker);
                });
        }

        function lmScheduleEditLocationSearchGeocode(picker) {
            if (!picker.searchInput) return;
            clearTimeout(picker.geocodeTimer);
            picker.geocodeRequestId++;
            lmCloseEditLocationResults(picker);
            if (!picker.searchInput.value.trim()) {
                return;
            }
            picker.geocodeTimer = setTimeout(function () {
                lmSearchEditLocations(picker, picker.searchInput.value);
            }, 450);
        }

        function lmInitEditMap(modalId) {
            if (!window.mapboxgl) return;
            var pickerEl = document.querySelector('[data-edit-location-picker][data-modal-id="' + modalId + '"]');
            if (!pickerEl) return;

            var existingPicker = lmEditPickers[modalId];
            if (existingPicker && existingPicker.map) {
                setTimeout(function () {
                    existingPicker.map.resize();
                }, 80);
                return;
            }

            var mapEl = document.getElementById(pickerEl.dataset.mapId || '');
            if (!mapEl) return;

            mapboxgl.accessToken = @json($mapboxToken);
            var initialLat = Number(pickerEl.dataset.initialLat);
            var initialLng = Number(pickerEl.dataset.initialLng);
            var hasInitialCoordinates = Number.isFinite(initialLat) && Number.isFinite(initialLng);
            var startLat = hasInitialCoordinates ? initialLat : lmDefaultLat;
            var startLng = hasInitialCoordinates ? initialLng : lmDefaultLng;

            var picker = {
                el: pickerEl,
                mapEl: mapEl,
                searchInput: document.getElementById(pickerEl.dataset.searchId || ''),
                resultsEl: document.getElementById(pickerEl.dataset.resultsId || ''),
                locationInput: document.getElementById(pickerEl.dataset.locationId || ''),
                latInput: document.getElementById(pickerEl.dataset.latId || ''),
                lngInput: document.getElementById(pickerEl.dataset.lngId || ''),
                categorySelect: document.getElementById(pickerEl.dataset.categoryId || ''),
                startLat: startLat,
                startLng: startLng,
                hasInitialCoordinates: hasInitialCoordinates,
                geocodeTimer: null,
                geocodeRequestId: 0,
                searchFeatures: []
            };

            picker.map = new mapboxgl.Map({
                container: mapEl,
                style: 'mapbox://styles/mapbox/streets-v12',
                center: [startLng, startLat],
                zoom: hasInitialCoordinates ? 15 : 13
            });

            picker.map.addControl(new mapboxgl.NavigationControl({ showCompass: false }), 'top-right');

            picker.marker = new mapboxgl.Marker({ draggable: true })
                .setLngLat([startLng, startLat])
                .addTo(picker.map);

            if (hasInitialCoordinates) {
                lmSetEditCoordinates(picker, { lng: startLng, lat: startLat });
            }
            lmSetEditLocationSearchValue(picker, picker.searchInput ? picker.searchInput.value : '');

            picker.map.on('click', function (event) {
                lmHandleManualEditLocation(picker, event.lngLat, true, event.point);
            });

            picker.marker.on('dragend', function () {
                lmHandleManualEditLocation(picker, picker.marker.getLngLat(), false);
            });

            if (picker.searchInput) {
                picker.searchInput.addEventListener('input', function () {
                    lmScheduleEditLocationSearchGeocode(picker);
                });
            }

            lmEditPickers[modalId] = picker;

            setTimeout(function () {
                picker.map.resize();
            }, 120);
        }

        document.addEventListener('click', function (event) {
            Object.keys(lmEditPickers).forEach(function (modalId) {
                var picker = lmEditPickers[modalId];
                if (picker && picker.el && !event.target.closest('[data-edit-location-picker][data-modal-id="' + modalId + '"]')) {
                    lmCloseEditLocationResults(picker);
                }
            });
        });
        @else
        function lmInitCreateMap() {}
        function lmInitEditMap() {}
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

        function lmAttachEditUploadGuards() {
            document.querySelectorAll('[data-landmark-edit-form]').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    var errors = [];
                    var imageInput = form.querySelector('input[name="image"]');
                    var evidenceInput = form.querySelector('input[name="evidence_files[]"]');
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

                    if (errors.length === 0) return;
                    event.preventDefault();
                    alert(errors.join('\n'));
                });
            });
        }
        @endif

        function smInitLandmarkFilterDropdowns() {
            var dropdowns = [];

            function closeDropdowns(exceptMenu, group) {
                dropdowns.forEach(function (dropdown) {
                    if (dropdown.menu === exceptMenu) return;
                    if (group && dropdown.group !== group) return;
                    dropdown.close();
                });
            }

            document.querySelectorAll('.land-filter-dropdown').forEach(function (root) {
                var select = root.querySelector('.land-filter-dropdown__native');
                var toggle = root.querySelector('.land-filter-dropdown__toggle');
                var arrow = root.querySelector('.land-filter-dropdown__arrow');
                var menu = root.querySelector('.land-filter-dropdown-menu');
                if (!select || !toggle || !arrow || !menu) return;
                var group = root.closest('.land-controls') ? 'filters' : root;
                var isOpen = false;

                function syncDropdown() {
                    var selected = select.options[select.selectedIndex] || select.options[0];
                    toggle.textContent = selected ? selected.textContent : '';
                    menu.querySelectorAll('[role="option"]').forEach(function (option) {
                        option.setAttribute('aria-selected', option.dataset.value === select.value ? 'true' : 'false');
                    });
                }

                function closeDropdown() {
                    isOpen = false;
                    menu.hidden = true;
                    root.classList.remove('is-open');
                    toggle.setAttribute('aria-expanded', 'false');
                    arrow.setAttribute('aria-expanded', 'false');
                }

                function openDropdown() {
                    if (group === 'filters') closeDropdowns(menu, group);
                    isOpen = true;
                    var viewport = window.visualViewport;
                    var viewportTop = viewport ? viewport.offsetTop : 0;
                    var viewportLeft = viewport ? viewport.offsetLeft : 0;
                    var viewportBottom = viewportTop + (viewport ? viewport.height : window.innerHeight);
                    var viewportRight = viewportLeft + (viewport ? viewport.width : document.documentElement.clientWidth);
                    var padding = 8;
                    var gap = 4;
                    var maxMenuHeight = 322;
                    var toggleRect = toggle.getBoundingClientRect();
                    var spaceBelow = Math.max(0, viewportBottom - toggleRect.bottom - gap - padding);
                    var spaceAbove = Math.max(0, toggleRect.top - viewportTop - gap - padding);

                    menu.style.width = toggleRect.width + 'px';
                    menu.style.maxHeight = maxMenuHeight + 'px';
                    menu.style.top = '0';
                    menu.hidden = false;

                    var desiredHeight = Math.min(menu.scrollHeight + 2, maxMenuHeight);
                    var opensUp = spaceBelow < desiredHeight && spaceAbove > spaceBelow;
                    var availableSpace = opensUp ? spaceAbove : spaceBelow;
                    menu.style.maxHeight = Math.min(maxMenuHeight, availableSpace) + 'px';

                    var menuHeight = menu.getBoundingClientRect().height;
                    var menuTop = opensUp ? toggleRect.top - gap - menuHeight : toggleRect.bottom + gap;
                    var maxLeft = viewportRight - padding - toggleRect.width;
                    menu.style.top = Math.max(viewportTop + padding, menuTop) + 'px';
                    menu.style.left = Math.max(viewportLeft + padding, Math.min(toggleRect.left, maxLeft)) + 'px';
                    root.classList.add('is-open');
                    toggle.setAttribute('aria-expanded', 'true');
                    arrow.setAttribute('aria-expanded', 'true');

                    var selected = menu.querySelector('[aria-selected="true"]');
                    if (selected) selected.scrollIntoView({ block: 'nearest' });
                }

                Array.from(select.options).forEach(function (nativeOption) {
                    var item = document.createElement('li');
                    var option = document.createElement('button');
                    option.type = 'button';
                    option.className = 'land-filter-dropdown-menu__option';
                    option.setAttribute('role', 'option');
                    option.dataset.value = nativeOption.value;
                    option.textContent = nativeOption.textContent;
                    option.addEventListener('click', function () {
                        select.value = nativeOption.value;
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                        syncDropdown();
                        closeDropdown();
                        toggle.focus();
                    });
                    item.appendChild(option);
                    menu.appendChild(item);
                });

                document.body.appendChild(menu);
                dropdowns.push({ menu: menu, group: group, close: closeDropdown });
                syncDropdown();
                select.addEventListener('change', syncDropdown);

                toggle.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    if (!isOpen) openDropdown();
                });
                arrow.addEventListener('mousedown', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                });
                arrow.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    if (isOpen) {
                        closeDropdown();
                    } else {
                        openDropdown();
                        toggle.focus({ preventScroll: true });
                    }
                });
                toggle.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' && isOpen) {
                        event.preventDefault();
                        event.stopPropagation();
                        closeDropdown();
                    } else if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        openDropdown();
                        (menu.querySelector('[aria-selected="true"]') || menu.querySelector('[role="option"]'))?.focus();
                    }
                });
                menu.addEventListener('keydown', function (event) {
                    var options = Array.from(menu.querySelectorAll('[role="option"]'));
                    var current = options.indexOf(document.activeElement);
                    if (event.key === 'Escape') {
                        event.stopPropagation();
                        closeDropdown();
                        toggle.focus();
                    } else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                        event.preventDefault();
                        var direction = event.key === 'ArrowDown' ? 1 : -1;
                        options[(current + direction + options.length) % options.length]?.focus();
                    } else if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        document.activeElement.click();
                    }
                });
                menu.addEventListener('click', function (event) {
                    event.stopPropagation();
                });
            });

            document.addEventListener('click', function (event) {
                if (!event.target.closest('.land-filter-dropdown') && !event.target.closest('.land-filter-dropdown-menu')) {
                    closeDropdowns();
                }
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') closeDropdowns();
            });
            window.addEventListener('resize', function () { closeDropdowns(); });
            window.addEventListener('scroll', function (event) {
                if (!event.target.closest || !event.target.closest('.land-filter-dropdown-menu')) closeDropdowns();
            }, true);
        }

        document.addEventListener('DOMContentLoaded', function () {
            smInitLandmarkListExpansion();
            smInitLandmarkControls();
            smInitLandmarkDeleteModal();
            smInitLandmarkFilterDropdowns();
            @if ($panelRoutePrefix === 'sitemanager')
            lmAttachCreateUploadGuard();
            lmAttachEditUploadGuards();
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
            if (event.target.classList && event.target.classList.contains('lm-edit-modal')) {
                lmCloseEditModal(event.target.id);
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
                return;
            }
            var editModal = document.querySelector('.lm-edit-modal[style*="flex"]');
            if (editModal) {
                lmCloseEditModal(editModal.id);
            }
            @endif
        });
    </script>
@endsection
