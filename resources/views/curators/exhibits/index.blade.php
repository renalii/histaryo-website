@extends('layouts.sidebar')

@section('content')
@php
    $openCreate = old('_form') === 'create' || request('create') === '1';
    $openEditId = old('_edit_id', request('edit'));
    $categoryOptions = $categoryOptions ?? [];
    $landmarkOptions = $landmarkOptions ?? [$landmark];
    $canSelectLandmark = (bool) ($canSelectLandmark ?? false);
    $routePrefix = $routePrefix ?? (session('role') === 'site_manager' ? 'sitemanager' : 'curators');
@endphp

<style>
    .exhibits-wrap { max-width: 2000px; margin: 0 auto; }
    .exhibits-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    .exhibits-title { margin: 0; color: #7A2E1F; font-size: 1.9rem; font-weight: 800; }
    .exhibits-sub { margin: .2rem 0 0; color: #6b7280; font-size: .95rem; }
    .exhibits-context {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        margin-top: .55rem;
        padding: .38rem .62rem;
        border: 1px solid #f3dba7;
        border-radius: 999px;
        background: #fff7ed;
        color: #7A2E1F;
        font-size: .86rem;
        font-weight: 700;
    }
    .exhibits-toolbar {
        display: flex;
        gap: .6rem;
        flex-wrap: wrap;
        align-items: center;
        padding: .8rem;
        border: 1px solid #eceff3;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 4px 14px rgba(15, 23, 42, .05);
        margin-bottom: 1rem;
    }
    .exhibits-toolbar--compact {
        width: fit-content;
        max-width: 100%;
        border-color: transparent;
        background: transparent;
        box-shadow: none;
    }
    .exhibits-input,
    .exhibits-select,
    .exhibits-form input[type="text"],
    .exhibits-form input[type="file"],
    .exhibits-form textarea,
    .exhibits-form select {
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #fff;
        color: #111827;
        padding: .62rem .72rem;
        font: inherit;
    }
    .exhibits-form input[type="text"]:focus,
    .exhibits-form textarea:focus,
    .exhibits-form select:focus {
        border-color: #D1D5DB;
        outline: none;
        box-shadow: none;
        -webkit-appearance: none;
    }
    .exhibits-input:focus,
    .exhibits-input:focus-visible,
    .exhibits-select:focus,
    .exhibits-select:focus-visible {
        border-color: #D1D5DB;
        outline: none;
        box-shadow: none;
    }
    .exhibits-input[type="search"] {
        -webkit-appearance: none;
        appearance: none;
    }
    .exhibits-input[type="search"]::-webkit-search-cancel-button {
        -webkit-appearance: none;
        appearance: none;
        display: none;
    }
    .exhibits-input[type="search"]::-webkit-search-decoration {
        display: none;
    }
    .exhibits-input { width: 300px; max-width: 100%; }
    .exhibits-select { min-width: 150px; }
    .exhibits-toolbar .category-dropdown {
        width: 150px;
        min-width: 150px;
        flex: 0 0 150px;
    }
    .exhibits-toolbar .category-dropdown__toggle {
        font-size: .9rem;
        font-weight: 700;
    }
    .exhibits-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        border: 1px solid transparent;
        padding: .6rem .85rem;
        font-weight: 800;
        text-decoration: none;
        cursor: pointer;
        transition: all .15s ease;
        font: inherit;
    }
    .exhibits-btn--primary {
        background: #E8B34B;
        color: #7A2E1F;
        border-color: #F3C96A;
    }
    .exhibits-btn--primary:hover { background: #F3C96A; transform: translateY(-1px); }
    .exhibits-btn--soft {
        background: #f3f4f6;
        color: #374151;
        border-color: #e5e7eb;
    }
    .exhibits-btn--danger {
        background: #fff;
        color: #991b1b;
        border-color: #fecaca;
    }
    .exhibits-table-card {
        background: #fff;
        border: 1px solid #eceff3;
        border-radius: 12px;
        box-shadow: 0 6px 16px rgba(15, 23, 42, .05);
        overflow-x: auto;
        padding: 1rem;
    }
    .exhibits-table {
        width: 100%;
        min-width: 900px;
        border-collapse: separate;
        border-spacing: 0;
    }
    .exhibits-table th {
        padding: .78rem;
        text-align: left;
        color: #7A2E1F;
        background: #fff7ed;
        border-bottom: 1px solid #f1f5f9;
        font-size: .92rem;
    }
    .exhibits-table td {
        padding: .78rem;
        border-bottom: 1px solid #eef2f7;
        color: #1f2937;
        vertical-align: middle;
    }
    .exhibits-actions { display: inline-flex; gap: .4rem; flex-wrap: wrap; align-items: center; }
    .exhibits-actions .exhibits-btn--soft {
        padding: .35rem .65rem;
        border-radius: 8px;
        border-color: #d1d5db;
        background: #fff;
        color: #374151;
        font-size: .78rem;
        font-weight: 700;
    }
    .exhibits-actions .exhibits-btn--soft:hover { background: #f9fafb; }
    #exhibit-delete-modal .exhibits-btn {
        padding: .45rem .8rem;
        border-radius: 8px;
        font-size: .85rem;
        line-height: 1;
        font-weight: 700;
    }
    #exhibit-delete-modal .exhibits-btn--soft {
        background: #f3f4f6;
        border-color: #e5e7eb;
        color: #374151;
    }
    #exhibit-delete-modal .exhibits-btn--danger {
        background: #ef4444;
        border-color: #ef4444;
        color: #fff;
    }
    #exhibit-delete-modal .exhibits-btn--danger:hover {
        background: #dc2626;
        border-color: #dc2626;
    }
    .exhibits-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: .22rem .6rem;
        font-size: .78rem;
        font-weight: 800;
        text-transform: capitalize;
        border: 1px solid transparent;
    }
    .exhibits-pill--active { background: #ecfdf5; color: #166534; border-color: #bbf7d0; }
    .exhibits-pill--inactive { background: #f3f4f6; color: #4b5563; border-color: #d1d5db; }
    .exhibits-empty {
        background: #fff;
        border: 1px dashed #d1d5db;
        border-radius: 12px;
        padding: 1rem;
        color: #6b7280;
    }
    .exhibits-flash {
        border-radius: 10px;
        padding: .8rem 1rem;
        margin-bottom: 1rem;
        font-weight: 700;
    }
    .exhibits-flash--ok { background: #ecfdf5; color: #166534; border: 1px solid #bbf7d0; }
    .exhibits-flash--err { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
    .exhibits-pager {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: .6rem;
        padding-top: 1rem;
    }
    .exhibits-modal {
        position: fixed;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        background: rgba(15, 23, 42, .58);
        z-index: 1100;
        padding: 1rem;
    }
    .exhibits-modal.is-open { display: flex; }
    .exhibits-panel {
        width: min(760px, 100%);
        max-height: min(90vh, 860px);
        overflow-y: auto;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 24px 70px rgba(15, 23, 42, .28);
        padding: 1.35rem;
        position: relative;
    }
    .exhibits-panel--wide { width: min(960px, 100%); }
    .exhibits-panel--form {
        width: min(780px, 100%);
        max-height: min(92vh, 900px);
        padding: 1.45rem;
    }
    .exhibits-panel--view {
        max-height: min(92vh, 900px);
        overflow-y: auto;
        padding: 0;
    }
    .exhibits-close {
        position: absolute;
        top: .75rem;
        right: .9rem;
        border: 0;
        background: none;
        color: #6b7280;
        font-size: 1.8rem;
        line-height: 1;
        cursor: pointer;
    }
    .exhibits-modal-title { margin: 0 2.2rem 1.1rem 0; color: #7A2E1F; font-size: 1.35rem; font-weight: 800; }
    .exhibits-form { display: grid; gap: 1rem; }
    .exhibits-form label { display: grid; gap: .45rem; color: #374151; font-weight: 700; font-size: .9rem; }
    .exhibits-form textarea {
        min-height: 150px;
        resize: vertical;
        line-height: 1.55;
        overflow-y: hidden;
    }
    .exhibits-form label[for$="historical-info"] textarea { min-height: 220px; }
    .exhibits-form input[type="text"],
    .exhibits-form select {
        width: 100%;
        min-height: 46px;
    }
    .exhibits-form input[type="file"] {
        display: block;
        box-sizing: border-box;
        width: 100%;
        max-width: 100%;
    }
    .exhibits-form-note {
        border: 1px solid #f3dba7;
        border-radius: 10px;
        background: #fffaf0;
        color: #7A2E1F;
        padding: .75rem .85rem;
        line-height: 1.45;
        font-size: .9rem;
    }
    .exhibits-form-note strong { display: block; margin-bottom: .15rem; }
    .exhibits-grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .8rem; }
    .exhibits-grid-2 label { align-content: start; grid-template-rows: auto 46px; }
    .exhibits-grid-2 input[type="text"],
    .exhibits-grid-2 input[type="file"],
    .exhibits-grid-2 select {
        height: 46px;
        min-height: 46px;
        padding: .62rem .72rem;
        border-radius: 8px;
        font-size: inherit;
    }
    .category-dropdown { position: relative; display: block; width: 100%; height: 46px; }
    .category-dropdown__native {
        position: absolute;
        inset: 0;
        opacity: 0;
        pointer-events: none;
    }
    .category-dropdown__toggle {
        position: relative;
        width: 100%;
        height: 46px;
        padding: .62rem 2rem .62rem .72rem;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #fff;
        color: #111827;
        font: inherit;
        font-weight: inherit;
        text-align: left;
        cursor: pointer;
    }
    .category-dropdown__toggle::after {
        content: '';
        position: absolute;
        top: 50%;
        right: .8rem;
        width: .45rem;
        height: .45rem;
        border-right: 2px solid #374151;
        border-bottom: 2px solid #374151;
        transform: translateY(-70%) rotate(45deg);
        pointer-events: none;
    }
    .category-dropdown__toggle:disabled { cursor: default; }
    .exhibits-form .exhibits-status-select {
        box-sizing: border-box;
        width: 100%;
        height: 46px;
        min-height: 46px;
        margin: 0;
        padding: .62rem 2rem .62rem .72rem;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        outline: none;
        background-color: #fff;
        background-image:
            linear-gradient(45deg, transparent 50%, #374151 50%),
            linear-gradient(135deg, #374151 50%, transparent 50%);
        background-position:
            calc(100% - .98rem) calc(50% - 1px),
            calc(100% - .7rem) calc(50% - 1px);
        background-repeat: no-repeat;
        background-size: .3rem .3rem, .3rem .3rem;
        color: #111827;
        box-shadow: none;
        font: inherit;
        font-size: inherit;
        font-weight: inherit;
        appearance: none;
        -webkit-appearance: none;
    }
    .exhibits-form .exhibits-status-select:focus,
    .exhibits-form .exhibits-status-select:focus-visible {
        border-color: #d1d5db;
        outline: none;
        box-shadow: none;
    }
    .category-dropdown-menu {
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
    .category-dropdown-menu[hidden] { display: none; }
    .category-dropdown-menu::-webkit-scrollbar { width: 6px; }
    .category-dropdown-menu::-webkit-scrollbar-thumb { background: #c7c7c7; border-radius: 999px; }
    .category-dropdown-menu::-webkit-scrollbar-track { background: transparent; }
    .category-dropdown-menu__option {
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
    .category-dropdown-menu__option:hover,
    .category-dropdown-menu__option:focus,
    .category-dropdown-menu__option[aria-selected="true"] { background: #f3f4f6; outline: none; }
    .exhibits-form-alert {
        border: 1px solid #fde68a;
        border-radius: 8px;
        background: #fffbeb;
        color: #92400e;
        padding: .72rem .8rem;
        font-weight: 700;
        font-size: .9rem;
    }
    .exhibits-existing-title { font-weight: 800; color: #7A2E1F; margin-bottom: .55rem; }
    .exhibits-existing-media { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: .85rem; }
    .exhibits-existing-media { grid-template-columns: repeat(auto-fill, minmax(170px, 207px)); }
    .exhibits-existing-media label {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: .55rem;
        font-weight: 700;
        font-size: .85rem;
        background: #fff;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .05);
    }
    .exhibits-existing-media img {
        width: 100%;
        height: 150px;
        object-fit: cover;
        border-radius: 10px;
        background: #f3f4f6;
        border: 1px solid #eef2f7;
        display: block;
    }
    .exhibits-existing-remove {
        display: flex;
        align-items: center;
        gap: .45rem;
        margin-top: .55rem;
        color: #374151;
    }
    .exhibits-view-header {
        position: sticky;
        top: 0;
        z-index: 2;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.25rem 1.35rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        background: #fff;
    }
    .exhibits-view-header .exhibits-modal-title { margin: 0; padding-right: 0; }
    .exhibits-view-actions { display: inline-flex; align-items: center; gap: .45rem; flex-shrink: 0; }
    .exhibits-view-actions .exhibits-btn { padding: .48rem .72rem; font-size: .9rem; }
    .exhibits-view-actions .exhibits-close {
        position: static;
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        font-size: 1.55rem;
        background: #fff;
    }
    .exhibits-view-actions .exhibits-close:hover { background: #f9fafb; }
    .exhibits-view-body {
        display: grid;
        gap: .85rem;
        padding: 1rem 1.35rem 1.35rem;
    }
    .exhibits-view-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .85rem; }
    .exhibits-view-section {
        margin: 0;
        border: 1px solid #eef2f7;
        border-radius: 10px;
        background: #fff;
        padding: .9rem;
    }
    .exhibits-view-section--wide { grid-column: 1 / -1; }
    .exhibits-view-section h3 { margin: 0 0 .45rem; color: #7A2E1F; font-size: .82rem; text-transform: uppercase; letter-spacing: .04em; }
    .exhibits-view-section p { margin: 0; color: #374151; line-height: 1.6; white-space: pre-wrap; }
    .exhibits-view-gallery-row {
        grid-column: 1 / -1;
        display: grid;
        grid-template-columns: minmax(0, 2fr) minmax(220px, 1fr);
        gap: .85rem;
        align-items: stretch;
    }
    .exhibits-view-gallery-row .exhibits-gallery { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .exhibits-view-gallery-meta {
        display: grid;
        grid-template-rows: repeat(2, minmax(0, 1fr));
        gap: .85rem;
    }
    .exhibits-gallery {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
        justify-items: center;
        margin: 0;
    }
    .exhibits-gallery-item {
        width: 100%;
        overflow: hidden;
        border-radius: 12px;
        border: 1px solid #E8E8E8;
        background: #fff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .05);
    }
    .exhibits-gallery-item img {
        width: 100%;
        height: 220px;
        object-fit: cover;
        display: block;
        cursor: pointer;
        border-radius: 12px;
        transition: transform .25s ease;
    }
    .exhibits-gallery-item img:hover { transform: scale(1.03); }
    .exhibits-image-preview-panel {
        width: min(980px, 100%);
        max-height: min(92vh, 900px);
        padding: 1rem;
        overflow: auto;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 24px 70px rgba(15, 23, 42, .28);
        position: relative;
    }
    .exhibits-image-preview-panel img {
        display: block;
        width: 100%;
        max-height: 78vh;
        object-fit: contain;
        border-radius: 12px;
        background: #f9fafb;
    }
    .exhibits-image-preview-panel .exhibits-close {
        top: .5rem;
        right: .65rem;
        width: 36px;
        height: 36px;
        border-radius: 8px;
        background: rgba(255, 255, 255, .92);
    }
    @media (max-width: 900px) {
        .exhibits-gallery { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 700px) {
        .exhibits-header { flex-direction: column; }
        .exhibits-grid-2 { grid-template-columns: 1fr; }
        .exhibits-view-header { flex-direction: column; }
        .exhibits-view-actions { width: 100%; justify-content: flex-end; }
        .exhibits-view-grid { grid-template-columns: 1fr; }
        .exhibits-view-gallery-row { grid-template-columns: 1fr; }
        .exhibits-view-gallery-meta { grid-template-rows: none; }
        .exhibits-view-gallery-row .exhibits-gallery { grid-template-columns: 1fr; }
        .exhibits-gallery { grid-template-columns: 1fr; }
        .exhibits-existing-media { grid-template-columns: 1fr; }
        .exhibits-btn { width: 100%; }
        .exhibits-view-actions .exhibits-btn { width: auto; }
        .exhibits-pager { justify-content: flex-start; flex-wrap: wrap; }
    }
</style>

<div class="exhibits-wrap">
    <div class="exhibits-header">
        <div>
            <h2 class="exhibits-title">Exhibits</h2>
            <p class="exhibits-sub">{{ $exhibits->total() }} exhibit{{ $exhibits->total() !== 1 ? 's' : '' }}{{ $canSelectLandmark ? ' across your managed landmarks' : ' for '.$landmark['name'] }}</p>
            @if (! $canSelectLandmark && $routePrefix !== 'curators')
                <span class="exhibits-context">Landmark: {{ $landmark['name'] }}</span>
            @endif
        </div>
        <button type="button" class="exhibits-btn exhibits-btn--primary" data-open-modal="exhibit-create-modal">+ Add Exhibit</button>
    </div>

    @if (session('success'))
        <div class="exhibits-flash exhibits-flash--ok">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="exhibits-flash exhibits-flash--err">{{ $errors->first() }}</div>
    @endif

    <form method="GET" action="{{ route($routePrefix.'.exhibits.index') }}" class="exhibits-toolbar{{ $canSelectLandmark ? ' exhibits-toolbar--compact' : '' }}">
        <input class="exhibits-input" type="search" name="search" value="{{ request('search') }}" placeholder="Search exhibits..." aria-label="Search exhibits">
        @if (! $canSelectLandmark)
            <select class="exhibits-select" name="category" aria-label="Filter by category">
                <option value="all" @selected(($categoryFilter ?? 'all') === 'all')>All categories</option>
                @foreach ($categoryOptions as $categoryOption)
                    <option value="{{ $categoryOption }}" @selected(($categoryFilter ?? 'all') === $categoryOption)>{{ $categoryOption }}</option>
                @endforeach
            </select>
        @endif
        <span class="category-dropdown">
            <select class="category-dropdown__native" name="status" aria-label="Filter by status">
                <option value="all" @selected($statusFilter === 'all')>All status</option>
                <option value="active" @selected($statusFilter === 'active')>Active</option>
                <option value="inactive" @selected($statusFilter === 'inactive')>Inactive</option>
            </select>
            <button class="category-dropdown__toggle" type="button" aria-haspopup="listbox" aria-expanded="false" aria-label="Filter by status"></button>
            <ul class="category-dropdown-menu" role="listbox" aria-label="Status options" hidden></ul>
        </span>
        <button class="exhibits-btn exhibits-btn--primary" type="submit">Search</button>
        <a class="exhibits-btn exhibits-btn--soft" href="{{ route($routePrefix.'.exhibits.index') }}">Clear</a>
    </form>

    @if ($exhibits->isEmpty())
        <div class="exhibits-empty">No exhibits found.</div>
    @else
        <div class="exhibits-table-card">
            <table class="exhibits-table">
                <thead>
                    <tr>
                        <th>Exhibit Name</th>
                        <th>Category</th>
                        <th>Landmark</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($exhibits as $exhibit)
                        <tr>
                            <td><strong style="color:#7A2E1F;">{{ $exhibit['name'] }}</strong></td>
                            <td>{{ $exhibit['category'] !== '' ? $exhibit['category'] : '-' }}</td>
                            <td>{{ $exhibit['landmark_name'] }}</td>
                            <td><span class="exhibits-pill exhibits-pill--{{ $exhibit['status'] }}">{{ ucfirst($exhibit['status']) }}</span></td>
                            <td>
                                <div class="exhibits-actions">
                                    <button type="button" class="exhibits-btn exhibits-btn--soft" data-open-modal="exhibit-view-{{ $exhibit['id'] }}">View</button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($exhibits->hasPages())
                <div class="exhibits-pager">
                    @if ($exhibits->onFirstPage())
                        <span class="exhibits-btn exhibits-btn--soft" style="color:#9ca3af;">Prev</span>
                    @else
                        <a class="exhibits-btn exhibits-btn--soft" href="{{ $exhibits->previousPageUrl() }}">Prev</a>
                    @endif
                    <span style="color:#4b5563;font-weight:700;">Page {{ $exhibits->currentPage() }} of {{ $exhibits->lastPage() }}</span>
                    @if ($exhibits->hasMorePages())
                        <a class="exhibits-btn exhibits-btn--primary" href="{{ $exhibits->nextPageUrl() }}">Next</a>
                    @else
                        <span class="exhibits-btn exhibits-btn--soft" style="color:#9ca3af;">Next</span>
                    @endif
                </div>
            @endif
        </div>
    @endif
</div>

<div id="exhibit-create-modal" class="exhibits-modal{{ $openCreate ? ' is-open' : '' }}" aria-hidden="{{ $openCreate ? 'false' : 'true' }}">
    <div class="exhibits-panel">
        <button type="button" class="exhibits-close" data-close-modal aria-label="Close">&times;</button>
        <h2 class="exhibits-modal-title">Add Exhibit</h2>
        <form class="exhibits-form" method="POST" action="{{ route($routePrefix.'.exhibits.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_form" value="create">
            @include('curators.exhibits.partials.form-fields', ['exhibit' => null, 'landmark' => $landmark, 'landmarkOptions' => $landmarkOptions, 'canSelectLandmark' => $canSelectLandmark, 'categoryOptions' => $categoryOptions])
            <button class="exhibits-btn exhibits-btn--primary" type="submit" {{ count($categoryOptions) === 0 ? 'disabled' : '' }}>Save Exhibit</button>
        </form>
    </div>
</div>

@foreach ($exhibits as $exhibit)
    <div id="exhibit-view-{{ $exhibit['id'] }}" class="exhibits-modal" aria-hidden="true">
        <div class="exhibits-panel exhibits-panel--wide exhibits-panel--view">
            <div class="exhibits-view-header">
                <h2 class="exhibits-modal-title">{{ $exhibit['name'] }}</h2>
                <div class="exhibits-view-actions">
                    <button type="button"
                            class="exhibits-btn exhibits-btn--soft"
                            data-open-modal="exhibit-edit-{{ $exhibit['id'] }}"
                            data-return-modal="exhibit-view-{{ $exhibit['id'] }}">Edit</button>
                    <button type="button"
                            class="exhibits-btn exhibits-btn--danger"
                            data-delete-exhibit
                            data-delete-name="{{ $exhibit['name'] }}"
                            data-delete-action="{{ route($routePrefix.'.exhibits.destroy', $exhibit['id']) }}">
                        Delete
                    </button>
                    <button type="button" class="exhibits-close" data-close-modal aria-label="Close">&times;</button>
                </div>
            </div>

            <div class="exhibits-view-body">
                <div class="exhibits-view-grid">
                    <div class="exhibits-view-section">
                        <h3>Category</h3>
                        <p>{{ $exhibit['category'] !== '' ? $exhibit['category'] : '-' }}</p>
                    </div>
                    <div class="exhibits-view-section">
                        <h3>Year / Period</h3>
                        <p>{{ $exhibit['year_period'] !== '' ? $exhibit['year_period'] : '-' }}</p>
                    </div>
                    <div class="exhibits-view-section exhibits-view-section--wide">
                        <h3>Description</h3>
                        <p>{{ $exhibit['description'] !== '' ? $exhibit['description'] : 'No description provided.' }}</p>
                    </div>
                    <div class="exhibits-view-section exhibits-view-section--wide">
                        <h3>Historical Information</h3>
                        <p>{{ $exhibit['historical_info'] !== '' ? $exhibit['historical_info'] : 'No historical information provided.' }}</p>
                    </div>
                    <div class="exhibits-view-gallery-row">
                        <div class="exhibits-view-section">
                            <h3>Gallery of Images</h3>
                            @if (count($exhibit['images']) > 0)
                                <div class="exhibits-gallery">
                                    @foreach ($exhibit['images'] as $image)
                                        @if (is_array($image) && ! empty($image['url']))
                                            <div class="exhibits-gallery-item">
                                                <img src="{{ $image['url'] }}"
                                                     alt="{{ $image['filename'] ?? $exhibit['name'] }}"
                                                     data-preview-image
                                                     data-preview-src="{{ $image['url'] }}"
                                                     data-preview-alt="{{ $image['filename'] ?? $exhibit['name'] }}">
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @else
                                <p>No images uploaded.</p>
                            @endif
                        </div>
                        <div class="exhibits-view-gallery-meta">
                            <div class="exhibits-view-section">
                                <h3>Assigned Landmark</h3>
                                <p>{{ $exhibit['landmark_name'] }}</p>
                            </div>
                            <div class="exhibits-view-section">
                                <h3>Status</h3>
                                <p>{{ ucfirst($exhibit['status']) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="exhibit-edit-{{ $exhibit['id'] }}" class="exhibits-modal{{ (string) $openEditId === (string) $exhibit['id'] ? ' is-open' : '' }}" aria-hidden="{{ (string) $openEditId === (string) $exhibit['id'] ? 'false' : 'true' }}">
        <div class="exhibits-panel exhibits-panel--form">
            <button type="button" class="exhibits-close" data-close-modal aria-label="Close">&times;</button>
            <h2 class="exhibits-modal-title">Edit Exhibit</h2>
            <form class="exhibits-form" method="POST" action="{{ route($routePrefix.'.exhibits.update', $exhibit['id']) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" name="_form" value="edit">
                <input type="hidden" name="_edit_id" value="{{ $exhibit['id'] }}">
                @include('curators.exhibits.partials.form-fields', ['exhibit' => $exhibit, 'landmark' => $landmark, 'landmarkOptions' => $landmarkOptions, 'canSelectLandmark' => $canSelectLandmark, 'categoryOptions' => $categoryOptions])
                <div style="display:flex;justify-content:flex-end;gap:.6rem;flex-wrap:wrap;">
                    <button class="exhibits-btn exhibits-btn--soft" type="button" data-close-modal>Cancel</button>
                    <button class="exhibits-btn exhibits-btn--primary" type="submit">Save changes</button>
                </div>
            </form>
        </div>
    </div>
@endforeach

<div id="exhibit-delete-modal" class="exhibits-modal" aria-hidden="true">
    <div class="exhibits-panel" style="width:min(430px,100%);">
        <button type="button" class="exhibits-close" data-close-modal aria-label="Close">&times;</button>
        <h2 class="exhibits-modal-title">Delete this exhibit?</h2>
        <p style="margin:0 0 1rem;color:#374151;line-height:1.5;">Are you sure you want to delete &quot;<strong id="delete-exhibit-name"></strong>&quot;?</p>
        <form id="delete-exhibit-form" method="POST" action="">
            @csrf
            @method('DELETE')
            <div style="display:flex;justify-content:flex-end;gap:.6rem;flex-wrap:wrap;">
                <button type="button" class="exhibits-btn exhibits-btn--soft" data-close-modal>Cancel</button>
                <button type="submit" class="exhibits-btn exhibits-btn--danger">Delete</button>
            </div>
        </form>
    </div>
</div>

<div id="exhibit-image-preview-modal" class="exhibits-modal" aria-hidden="true">
    <div class="exhibits-image-preview-panel">
        <button type="button" class="exhibits-close" data-close-modal aria-label="Close">&times;</button>
        <img id="exhibit-image-preview" src="" alt="">
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var categoryDropdowns = [];

    function closeCategoryDropdowns(exceptMenu) {
        categoryDropdowns.forEach(function (dropdown) {
            if (dropdown.menu === exceptMenu) return;
            dropdown.menu.hidden = true;
            dropdown.toggle.setAttribute('aria-expanded', 'false');
        });
    }

    document.querySelectorAll('.category-dropdown').forEach(function (root) {
        var select = root.querySelector('.category-dropdown__native');
        var toggle = root.querySelector('.category-dropdown__toggle');
        var menu = root.querySelector('.category-dropdown-menu');
        var panel = root.closest('.exhibits-panel');
        if (!select || !toggle || !menu) return;

        function syncCategory() {
            var selectedOption = select.options[select.selectedIndex] || select.options[0];
            toggle.textContent = selectedOption ? selectedOption.textContent : 'Select category';
            menu.querySelectorAll('[role="option"]').forEach(function (option) {
                option.setAttribute('aria-selected', option.dataset.value === select.value ? 'true' : 'false');
            });
        }

        function closeCategory() {
            menu.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
        }

        function openCategory() {
            closeCategoryDropdowns(menu);
            var viewport = window.visualViewport;
            var viewportTop = viewport ? viewport.offsetTop : 0;
            var viewportLeft = viewport ? viewport.offsetLeft : 0;
            var viewportBottom = viewportTop + (viewport ? viewport.height : window.innerHeight);
            var viewportRight = viewportLeft + (viewport ? viewport.width : document.documentElement.clientWidth);
            var panelRect = panel ? panel.getBoundingClientRect() : null;
            var padding = 8;
            var gap = 4;
            var maxMenuHeight = 322;
            var boundaryTop = Math.max(viewportTop + padding, panelRect ? panelRect.top + padding : viewportTop + padding);
            var boundaryBottom = Math.min(viewportBottom - padding, panelRect ? panelRect.bottom - padding : viewportBottom - padding);
            var toggleRect = toggle.getBoundingClientRect();
            var spaceBelow = Math.max(0, boundaryBottom - toggleRect.bottom - gap);
            var spaceAbove = Math.max(0, toggleRect.top - boundaryTop - gap);

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
            var maxLeft = Math.min(viewportRight - padding, panelRect ? panelRect.right - padding : viewportRight - padding) - toggleRect.width;
            var minLeft = Math.max(viewportLeft + padding, panelRect ? panelRect.left + padding : viewportLeft + padding);
            menu.style.top = Math.max(boundaryTop, menuTop) + 'px';
            menu.style.left = Math.max(minLeft, Math.min(toggleRect.left, maxLeft)) + 'px';
            toggle.setAttribute('aria-expanded', 'true');

            var selected = menu.querySelector('[aria-selected="true"]');
            if (selected) selected.scrollIntoView({ block: 'nearest' });
        }

        Array.from(select.options).forEach(function (nativeOption) {
            var item = document.createElement('li');
            var option = document.createElement('button');
            option.type = 'button';
            option.className = 'category-dropdown-menu__option';
            option.setAttribute('role', 'option');
            option.dataset.value = nativeOption.value;
            option.textContent = nativeOption.textContent;
            option.addEventListener('click', function () {
                select.value = nativeOption.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                syncCategory();
                closeCategory();
                toggle.focus();
            });
            item.appendChild(option);
            menu.appendChild(item);
        });
        document.body.appendChild(menu);
        categoryDropdowns.push({ menu: menu, toggle: toggle });
        syncCategory();

        toggle.addEventListener('click', function (event) {
            event.preventDefault();
            menu.hidden ? openCategory() : closeCategory();
        });
        toggle.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openCategory();
                (menu.querySelector('[aria-selected="true"]') || menu.querySelector('[role="option"]'))?.focus();
            }
        });
        menu.addEventListener('keydown', function (event) {
            var options = Array.from(menu.querySelectorAll('[role="option"]'));
            var current = options.indexOf(document.activeElement);
            if (event.key === 'Escape') {
                event.stopPropagation();
                closeCategory();
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
        select.form?.addEventListener('reset', function () {
            setTimeout(function () { syncCategory(); closeCategory(); }, 0);
        });
    });

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.category-dropdown') && !event.target.closest('.category-dropdown-menu')) {
            closeCategoryDropdowns();
        }
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeCategoryDropdowns();
    });
    window.addEventListener('resize', function () { closeCategoryDropdowns(); });
    window.addEventListener('scroll', function (event) {
        if (!event.target.closest || !event.target.closest('.category-dropdown-menu')) closeCategoryDropdowns();
    }, true);

    function syncScrollLock() {
        document.body.style.overflow = document.querySelector('.exhibits-modal.is-open') ? 'hidden' : '';
    }

    function openModal(id) {
        var modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        syncScrollLock();
    }

    function closeModal(modal) {
        if (!modal) return;
        var form = modal.querySelector('form');
        if (form) form.reset();
        var returnModalId = modal.id.indexOf('exhibit-edit-') === 0
            ? (modal.dataset.returnModalId || '')
            : '';
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        modal.dataset.returnModalId = '';
        if (returnModalId) {
            openModal(returnModalId);
        } else {
            syncScrollLock();
        }
    }

    document.querySelectorAll('[data-open-modal]').forEach(function (button) {
        button.addEventListener('click', function () {
            var targetModal = document.getElementById(button.dataset.openModal);
            if (targetModal && button.dataset.returnModal) {
                targetModal.dataset.returnModalId = button.dataset.returnModal;
            }
            closeModal(button.closest('.exhibits-modal'));
            openModal(button.dataset.openModal);
        });
    });

    document.querySelectorAll('[data-close-modal]').forEach(function (button) {
        button.addEventListener('click', function () {
            closeModal(button.closest('.exhibits-modal'));
        });
    });

    document.querySelectorAll('.exhibits-modal').forEach(function (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal(modal);
            }
        });
    });

    document.querySelectorAll('[data-delete-exhibit]').forEach(function (button) {
        button.addEventListener('click', function () {
            var form = document.getElementById('delete-exhibit-form');
            var name = document.getElementById('delete-exhibit-name');
            if (form) form.action = button.dataset.deleteAction || '';
            if (name) name.textContent = button.dataset.deleteName || 'this exhibit';
            openModal('exhibit-delete-modal');
        });
    });

    document.querySelectorAll('[data-preview-image]').forEach(function (image) {
        image.addEventListener('click', function () {
            var preview = document.getElementById('exhibit-image-preview');
            if (!preview) return;
            preview.src = image.dataset.previewSrc || image.src || '';
            preview.alt = image.dataset.previewAlt || image.alt || 'Exhibit image';
            openModal('exhibit-image-preview-modal');
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        closeModal(document.querySelector('.exhibits-modal.is-open'));
    });

    syncScrollLock();
});
</script>
@endsection
