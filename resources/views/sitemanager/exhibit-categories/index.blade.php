@extends('layouts.sidebar')

@section('content')
@php
    $openCreate = old('_form') === 'create' || request('create') === '1';
    $openEditId = old('_edit_id', request('edit'));
    $openViewId = $openViewId ?? null;
    $routePrefix = $routePrefix ?? (session('role') === 'curator' ? 'curators' : 'sitemanager');
    $categoryIndexUrl = route($routePrefix.'.exhibit-categories.index');
@endphp

<style>
    @if ($routePrefix === 'sitemanager')
    html:has(body .sitemanager-exhibit-categories-page),
    body:has(.sitemanager-exhibit-categories-page) {
        height: 100%;
        overflow-y: hidden;
    }
    @endif

    .ec-wrap { max-width: 1200px; margin: 0 auto; }
    .ec-header { display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; margin-bottom:1rem; }
    .ec-title { margin:0; color:#7A2E1F; font-size:1.9rem; font-weight:800; }
    .ec-sub { margin:.2rem 0 0; color:#6b7280; font-size:.95rem; }
    .ec-card { background:#fff; border:1px solid #eceff3; border-radius:12px; box-shadow:0 6px 16px rgba(15,23,42,.05); overflow-x:auto; padding:1rem; }
    .ec-table { width:100%; min-width:720px; border-collapse:separate; border-spacing:0; }
    .ec-table th { padding:.78rem; text-align:left; color:#7A2E1F; background:#fff7ed; border-bottom:1px solid #f1f5f9; font-size:.92rem; }
    .ec-table td { padding:.78rem; border-bottom:1px solid #eef2f7; color:#1f2937; vertical-align:middle; }
    .ec-actions { display:inline-flex; gap:.4rem; flex-wrap:wrap; align-items:center; }
    .ec-btn { display:inline-flex; align-items:center; justify-content:center; border-radius:8px; border:1px solid transparent; padding:.6rem .85rem; font-weight:800; text-decoration:none; cursor:pointer; transition:all .15s ease; font:inherit; }
    .ec-btn--primary { background:#E8B34B; color:#7A2E1F; border-color:#F3C96A; }
    .ec-btn--primary:hover { background:#F3C96A; transform:translateY(-1px); }
    .ec-btn--header { padding:.45rem .8rem; border-radius:8px; font-size:.88rem; font-weight:700; background:#E8B34B; color:#7A2E1F; border-color:#F3C96A; }
    .ec-btn--soft { background:#f3f4f6; color:#374151; border-color:#e5e7eb; }
    .ec-btn--danger { background:#fff; color:#991b1b; border-color:#fecaca; }
    .ec-actions .ec-btn { padding:.35rem .65rem; border-radius:8px; font-weight:700; font-size:.78rem; }
    .ec-actions .ec-btn--soft { background:#fff; color:#374151; border-color:#D1D5DB; }
    .ec-actions .ec-btn--soft:hover { background:#f9fafb; }
    .ec-actions .ec-btn--danger { background:#fff; color:#DC2626; border-color:#FCA5A5; }
    .ec-actions .ec-btn--danger:hover { background:#fef2f2; }
    .ec-pill { display:inline-flex; align-items:center; border-radius:999px; padding:.22rem .6rem; font-size:.78rem; font-weight:800; text-transform:capitalize; border:1px solid transparent; }
    .ec-pill--active { background:#ecfdf5; color:#166534; border-color:#bbf7d0; }
    .ec-pill--inactive { background:#f3f4f6; color:#4b5563; border-color:#d1d5db; }
    .ec-empty { background:#fff; border:1px dashed #d1d5db; border-radius:12px; padding:1rem; color:#6b7280; }
    .ec-flash { border-radius:10px; padding:.8rem 1rem; margin-bottom:1rem; font-weight:700; }
    .ec-flash--ok { background:#ecfdf5; color:#166534; border:1px solid #bbf7d0; }
    .ec-flash--err { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
    .ec-modal { position:fixed; inset:0; display:none; align-items:center; justify-content:center; background:rgba(15,23,42,.58); z-index:1100; padding:1rem; }
    .ec-modal.is-open { display:flex; }
    .ec-panel { width:min(520px,100%); max-height:min(90vh,760px); overflow-y:auto; background:#fff; border-radius:16px; box-shadow:0 24px 70px rgba(15,23,42,.28); padding:1.35rem; position:relative; }
    .ec-close { position:absolute; top:.75rem; right:.9rem; border:0; background:none; color:#6b7280; font-size:1.8rem; line-height:1; cursor:pointer; }
    .ec-modal-title { margin:0 2.2rem 1rem 0; color:#7A2E1F; font-size:1.35rem; font-weight:800; }
    .ec-delete-panel { width:min(100%,430px); max-height:none; overflow:visible; border-radius:12px; box-shadow:0 24px 70px rgba(15,23,42,.28); padding:1.2rem; border:1px solid #f1f5f9; }
    .ec-delete-title { margin:0 0 .65rem; color:#7A2E1F; font-size:1.25rem; font-weight:800; }
    .ec-delete-message { margin:0; color:#374151; line-height:1.55; }
    .ec-delete-actions { display:flex; justify-content:flex-end; gap:.6rem; margin-top:1.25rem; }
    .ec-delete-btn { border-radius:8px; border:1px solid transparent; padding:.45rem .8rem; font-size:.85rem; line-height:1; font-weight:700; cursor:pointer; transition:all .15s ease; }
    .ec-delete-btn--secondary { background:#f3f4f6; color:#374151; border-color:#e5e7eb; }
    .ec-delete-btn--secondary:hover { background:#e5e7eb; }
    .ec-delete-btn--danger { background:#ef4444; color:#fff; border-color:#ef4444; }
    .ec-delete-btn--danger:hover { background:#dc2626; border-color:#dc2626; }
    .ec-category-view-actions { gap:8px; align-items:center; }
    .ec-category-view-actions .category-edit-btn,
    .ec-category-view-actions .category-delete-btn {
        display:inline-flex;
        align-items:center;
        justify-content:center;
        box-sizing:border-box;
        width:auto;
        height:36px;
        padding:0 14px;
        border-radius:7px;
        font-size:14px;
        font-weight:600;
        line-height:1;
        cursor:pointer;
    }
    .ec-category-view-actions .category-edit-btn {
        background:#F9FAFB;
        color:#374151;
        border:1px solid #D1D5DB;
    }
    .ec-category-view-actions .category-edit-btn:hover { background:#F3F4F6; }
    .ec-category-view-actions .category-delete-btn {
        background:#FFFFFF;
        color:#B91C1C;
        border:1px solid #FCA5A5;
    }
    .ec-category-view-actions .category-delete-btn:hover { background:#FEF2F2; }
    .ec-form { display:grid; gap:.85rem; }
    .ec-form label { display:grid; gap:.35rem; color:#374151; font-weight:700; font-size:.9rem; }
    .ec-form input[type="text"], .ec-form select { width:100%; min-height:46px; border:1px solid #d1d5db; border-radius:8px; background:#fff; color:#111827; padding:.62rem .72rem; font:inherit; outline:none; box-shadow:none; }
    .ec-form input[type="text"]:focus, .ec-form select:focus { border-color:#d1d5db; outline:none; box-shadow:none; }
    .ec-form .exhibits-status-select {
        box-sizing: border-box;
        width: 100%;
        height: 40px;
        min-height: 40px;
        margin: 0;
        padding: 0 2rem 0 .75rem;
        border: 1px solid #cbd5e1;
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
        font-size: 14px;
        font-weight: 700;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
    }
    .ec-form .exhibits-status-select:focus,
    .ec-form .exhibits-status-select:focus-visible {
        border-color: #cbd5e1;
        outline: none;
        box-shadow: none;
    }
    .ec-custom-select { position:relative; display:block; width:100%; height:40px; }
    .ec-custom-select__native { position:absolute; inset:0; opacity:0; pointer-events:none; }
    .ec-custom-select__field {
        box-sizing:border-box;
        width:100%;
        height:40px;
        padding:0 2rem 0 .75rem;
        border:1px solid #cbd5e1;
        border-radius:8px;
        background:#fff;
        color:#111827;
        font:inherit;
        font-size:14px;
        font-weight:700;
        text-align:left;
        cursor:pointer;
    }
    .ec-custom-select__field:focus,
    .ec-custom-select__field:focus-visible { border-color:#cbd5e1; outline:none; box-shadow:none; }
    .custom-select-arrow {
        position:absolute;
        z-index:1;
        top:1px;
        right:1px;
        width:2.5rem;
        height:38px;
        padding:0;
        border:0;
        border-radius:0 7px 7px 0;
        background:transparent;
        color:#374151;
        cursor:pointer;
    }
    .custom-select-arrow::after {
        content:'';
        position:absolute;
        top:50%;
        left:50%;
        width:.45rem;
        height:.45rem;
        border-right:2px solid currentColor;
        border-bottom:2px solid currentColor;
        transform:translate(-50%, -70%) rotate(45deg);
    }
    .ec-custom-select.is-open .custom-select-arrow::after {
        transform:translate(-50%, -30%) rotate(225deg);
    }
    .custom-select-arrow:focus-visible { outline:2px solid #9ca3af; outline-offset:-3px; }
    .ec-custom-select__menu {
        position:fixed;
        z-index:9999;
        box-sizing:border-box;
        max-height:322px;
        margin:0;
        padding:0;
        overflow-x:hidden;
        overflow-y:auto;
        scrollbar-width:thin;
        border:1px solid #d1d5db;
        border-radius:6px;
        background:#fff;
        box-shadow:0 8px 20px rgba(0,0,0,.15);
        list-style:none;
    }
    .ec-custom-select__menu[hidden] { display:none; }
    .ec-custom-select__option {
        display:block;
        width:100%;
        height:40px;
        padding:0 .72rem;
        border:0;
        background:transparent;
        color:#111827;
        font:inherit;
        line-height:40px;
        text-align:left;
        cursor:pointer;
    }
    .ec-custom-select__option:hover,
    .ec-custom-select__option:focus,
    .ec-custom-select__option[aria-selected="true"] { background:#f3f4f6; outline:none; }
    .ec-pager { display:flex; justify-content:flex-end; align-items:center; gap:.6rem; padding-top:1rem; }
    @media (max-width:700px) { .ec-header { flex-direction:column; } .ec-btn { width:100%; } .ec-pager { justify-content:flex-start; flex-wrap:wrap; } }
</style>

<div class="ec-wrap{{ $routePrefix === 'sitemanager' ? ' sitemanager-exhibit-categories-page' : '' }}">
    <div class="ec-header">
        <div>
            <h2 class="ec-title">Exhibit Categories</h2>
            @if ($routePrefix !== 'curators')
                <p class="ec-sub">{{ $categories->total() }} categor{{ $categories->total() === 1 ? 'y' : 'ies' }} available to curators across your landmarks.</p>
            @endif
        </div>
        <button type="button" class="ec-btn ec-btn--primary ec-btn--header" data-open-modal="category-create-modal">+ Add Category</button>
    </div>

    @if (session('status'))
        <div class="ec-flash ec-flash--ok">{{ session('status') }}</div>
    @endif
    @if (session('status_err'))
        <div class="ec-flash ec-flash--err">{{ session('status_err') }}</div>
    @endif
    @if ($errors->any())
        <div class="ec-flash ec-flash--err">{{ $errors->first() }}</div>
    @endif

    @if ($categories->isEmpty())
        <div class="ec-empty">No exhibit categories created yet.</div>
    @else
        <div class="ec-card">
            <table class="ec-table">
                <thead>
                    <tr>
                        <th>Category Name</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        <tr>
                            <td><strong style="color:#7A2E1F;">{{ $category['name'] }}</strong></td>
                            <td>
                                <div class="ec-actions">
                                    <button type="button"
                                            class="ec-btn ec-btn--soft"
                                            data-open-modal="category-view-{{ $category['id'] }}"
                                            @if ($routePrefix === 'sitemanager')
                                                data-category-id="{{ $category['id'] }}"
                                            @endif>View</button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($categories->hasPages())
                <div class="ec-pager">
                    @if ($categories->onFirstPage())
                        <span class="ec-btn ec-btn--soft" style="color:#9ca3af;">Prev</span>
                    @else
                        <a class="ec-btn ec-btn--soft" href="{{ $categories->previousPageUrl() }}">Prev</a>
                    @endif
                    <span style="color:#4b5563;font-weight:700;">Page {{ $categories->currentPage() }} of {{ $categories->lastPage() }}</span>
                    @if ($categories->hasMorePages())
                        <a class="ec-btn ec-btn--primary" href="{{ $categories->nextPageUrl() }}">Next</a>
                    @else
                        <span class="ec-btn ec-btn--soft" style="color:#9ca3af;">Next</span>
                    @endif
                </div>
            @endif
        </div>
    @endif
</div>

<div id="category-create-modal" class="ec-modal{{ $openCreate ? ' is-open' : '' }}" aria-hidden="{{ $openCreate ? 'false' : 'true' }}">
    <div class="ec-panel">
        <button type="button" class="ec-close" data-close-modal aria-label="Close">&times;</button>
        <h2 class="ec-modal-title">Add Category</h2>
        <form class="ec-form" method="POST" action="{{ route($routePrefix.'.exhibit-categories.store') }}">
            @csrf
            <input type="hidden" name="_form" value="create">
            <label for="create-category-name">
                Category Name
                <input id="create-category-name" type="text" name="name" value="{{ old('_form') === 'create' ? old('name') : '' }}" required>
            </label>
            <button class="ec-btn ec-btn--primary" type="submit">Save Category</button>
        </form>
    </div>
</div>

@foreach ($categories as $category)
    <div id="category-view-{{ $category['id'] }}"
         class="ec-modal{{ (string) $openViewId === (string) $category['id'] ? ' is-open' : '' }}"
         aria-hidden="{{ (string) $openViewId === (string) $category['id'] ? 'false' : 'true' }}"
         data-category-view-modal
         data-index-url="{{ $categoryIndexUrl }}">
        <div class="ec-panel">
            <button type="button" class="ec-close" data-close-modal aria-label="Close">&times;</button>
            <h2 class="ec-modal-title">{{ $category['name'] }}</h2>
            <div style="display:grid;gap:.8rem;color:#374151;">
                <div>
                    <div style="font-weight:800;color:#7A2E1F;text-transform:uppercase;font-size:.82rem;">Status</div>
                    <span class="ec-pill ec-pill--{{ $category['status'] }}">{{ ucfirst($category['status']) }}</span>
                </div>
                <div>
                    <div style="font-weight:800;color:#7A2E1F;text-transform:uppercase;font-size:.82rem;">Created</div>
                    <div>{{ $category['created_at'] !== '' ? $category['created_at'] : '-' }}</div>
                </div>
                <div>
                    <div style="font-weight:800;color:#7A2E1F;text-transform:uppercase;font-size:.82rem;">Updated</div>
                    <div>{{ $category['updated_at'] !== '' ? $category['updated_at'] : '-' }}</div>
                </div>
            </div>
            <div class="ec-delete-actions ec-category-view-actions">
                <button type="button" class="ec-btn ec-btn--soft category-edit-btn" data-open-modal="category-edit-{{ $category['id'] }}">Edit</button>
                <button type="button" class="ec-btn ec-btn--danger category-delete-btn" data-delete-category data-delete-name="{{ $category['name'] }}" data-delete-action="{{ route($routePrefix.'.exhibit-categories.destroy', $category['id']) }}">Delete</button>
            </div>
        </div>
    </div>

    <div id="category-edit-{{ $category['id'] }}" class="ec-modal{{ (string) $openEditId === (string) $category['id'] ? ' is-open' : '' }}" aria-hidden="{{ (string) $openEditId === (string) $category['id'] ? 'false' : 'true' }}">
        <div class="ec-panel">
            <button type="button" class="ec-close" data-close-modal aria-label="Close">&times;</button>
            <h2 class="ec-modal-title">Edit Category</h2>
            <form class="ec-form" method="POST" action="{{ route($routePrefix.'.exhibit-categories.update', $category['id']) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="_form" value="edit">
                <input type="hidden" name="_edit_id" value="{{ $category['id'] }}">
                <label for="edit-category-name-{{ $category['id'] }}">
                    Category Name
                    <input id="edit-category-name-{{ $category['id'] }}" type="text" name="name" value="{{ (string) $openEditId === (string) $category['id'] ? old('name', $category['name']) : $category['name'] }}" required>
                </label>
                <label for="edit-category-status-{{ $category['id'] }}">
                    Status
                    <span class="ec-custom-select">
                        <select id="edit-category-status-{{ $category['id'] }}" class="ec-custom-select__native" name="status" required>
                            @php $selectedStatus = (string) $openEditId === (string) $category['id'] ? old('status', $category['status']) : $category['status']; @endphp
                            <option value="active" @selected($selectedStatus === 'active')>Active</option>
                            <option value="inactive" @selected($selectedStatus === 'inactive')>Inactive</option>
                        </select>
                        <button type="button" class="ec-custom-select__field" aria-haspopup="listbox" aria-expanded="false"></button>
                        <button type="button" class="custom-select-arrow" aria-label="Toggle status options" aria-expanded="false"></button>
                        <ul class="ec-custom-select__menu" role="listbox" hidden></ul>
                    </span>
                </label>
                <div class="ec-delete-actions">
                    <button class="ec-btn ec-btn--soft" type="button" data-close-modal>Cancel</button>
                    <button class="ec-btn ec-btn--primary" type="submit">Save changes</button>
                </div>
            </form>
        </div>
    </div>
@endforeach

<div id="category-delete-modal" class="ec-modal" aria-hidden="true">
    <div class="ec-panel ec-delete-panel">
        <h2 class="ec-delete-title">Delete this category?</h2>
        <p id="delete-category-message" class="ec-delete-message">Are you sure you want to delete this category?</p>
        <form id="delete-category-form" method="POST" action="">
            @csrf
            @method('DELETE')
            <div class="ec-delete-actions">
                <button type="button" class="ec-delete-btn ec-delete-btn--secondary" data-close-modal>Cancel</button>
                <button type="submit" class="ec-delete-btn ec-delete-btn--danger">Delete</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var customSelects = [];

    function closeEcCustomSelects(exceptMenu) {
        var closedAny = false;
        customSelects.forEach(function (dropdown) {
            if (dropdown.menu === exceptMenu) return;
            if (dropdown.isOpen()) closedAny = true;
            dropdown.close();
        });
        return closedAny;
    }

    document.querySelectorAll('.ec-custom-select').forEach(function (root) {
        var select = root.querySelector('.ec-custom-select__native');
        var field = root.querySelector('.ec-custom-select__field');
        var arrow = root.querySelector('.custom-select-arrow');
        var menu = root.querySelector('.ec-custom-select__menu');
        var panel = root.closest('.ec-panel');
        var isOpen = false;
        if (!select || !field || !arrow || !menu) return;

        function syncSelect() {
            var selected = select.options[select.selectedIndex] || select.options[0];
            field.textContent = selected ? selected.textContent : '';
            menu.querySelectorAll('[role="option"]').forEach(function (option) {
                option.setAttribute('aria-selected', option.dataset.value === select.value ? 'true' : 'false');
            });
        }

        function closeSelect() {
            isOpen = false;
            menu.hidden = true;
            root.classList.remove('is-open');
            field.setAttribute('aria-expanded', 'false');
            arrow.setAttribute('aria-expanded', 'false');
        }

        function openSelect() {
            closeEcCustomSelects(menu);
            isOpen = true;
            var viewport = window.visualViewport;
            var viewportTop = viewport ? viewport.offsetTop : 0;
            var viewportLeft = viewport ? viewport.offsetLeft : 0;
            var viewportBottom = viewportTop + (viewport ? viewport.height : window.innerHeight);
            var viewportRight = viewportLeft + (viewport ? viewport.width : document.documentElement.clientWidth);
            var panelRect = panel ? panel.getBoundingClientRect() : null;
            var padding = 8;
            var gap = 4;
            var maxHeight = 322;
            var boundaryTop = Math.max(viewportTop + padding, panelRect ? panelRect.top + padding : viewportTop + padding);
            var boundaryBottom = Math.min(viewportBottom - padding, panelRect ? panelRect.bottom - padding : viewportBottom - padding);
            var fieldRect = field.getBoundingClientRect();
            var below = Math.max(0, boundaryBottom - fieldRect.bottom - gap);
            var above = Math.max(0, fieldRect.top - boundaryTop - gap);

            menu.style.width = fieldRect.width + 'px';
            menu.style.maxHeight = maxHeight + 'px';
            menu.hidden = false;
            var desiredHeight = Math.min(menu.scrollHeight + 2, maxHeight);
            var opensUp = below < desiredHeight && above > below;
            menu.style.maxHeight = Math.min(maxHeight, opensUp ? above : below) + 'px';
            var menuHeight = menu.getBoundingClientRect().height;
            var top = opensUp ? fieldRect.top - gap - menuHeight : fieldRect.bottom + gap;
            var maxLeft = viewportRight - padding - fieldRect.width;
            menu.style.top = Math.max(boundaryTop, top) + 'px';
            menu.style.left = Math.max(viewportLeft + padding, Math.min(fieldRect.left, maxLeft)) + 'px';
            root.classList.add('is-open');
            field.setAttribute('aria-expanded', 'true');
            arrow.setAttribute('aria-expanded', 'true');

            var selected = menu.querySelector('[aria-selected="true"]');
            if (selected) selected.scrollIntoView({ block: 'nearest' });
        }

        Array.from(select.options).forEach(function (nativeOption) {
            var item = document.createElement('li');
            var option = document.createElement('button');
            option.type = 'button';
            option.className = 'ec-custom-select__option';
            option.setAttribute('role', 'option');
            option.dataset.value = nativeOption.value;
            option.textContent = nativeOption.textContent;
            option.addEventListener('click', function () {
                select.value = nativeOption.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                syncSelect();
                closeSelect();
                field.focus();
            });
            item.appendChild(option);
            menu.appendChild(item);
        });
        document.body.appendChild(menu);
        customSelects.push({ menu: menu, close: closeSelect, isOpen: function () { return isOpen; } });
        syncSelect();

        field.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            if (!isOpen) openSelect();
        });
        arrow.addEventListener('mousedown', function (event) {
            event.preventDefault();
            event.stopPropagation();
        });
        arrow.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            if (isOpen) {
                closeSelect();
            } else {
                openSelect();
                field.focus({ preventScroll: true });
            }
        });
        menu.addEventListener('click', function (event) {
            event.stopPropagation();
        });
    });

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.ec-custom-select') && !event.target.closest('.ec-custom-select__menu')) {
            closeEcCustomSelects();
        }
    });
    window.addEventListener('resize', function () { closeEcCustomSelects(); });
    window.addEventListener('scroll', function (event) {
        if (!event.target.closest || !event.target.closest('.ec-custom-select__menu')) closeEcCustomSelects();
    }, true);

    function syncScrollLock() {
        document.body.style.overflow = document.querySelector('.ec-modal.is-open') ? 'hidden' : '';
    }

    function categoryIndexHistoryUrl(modal) {
        var configuredUrl = modal && modal.dataset.indexUrl
            ? modal.dataset.indexUrl
            : '/sitemanager/exhibit-categories';
        try {
            return new URL(configuredUrl, window.location.origin).pathname + window.location.search;
        } catch (error) {
            return '/sitemanager/exhibit-categories' + window.location.search;
        }
    }

    function openModal(id, categoryId) {
        var modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        syncScrollLock();
        if (modal.hasAttribute('data-category-view-modal') && categoryId) {
            try {
                var detailUrl = categoryIndexHistoryUrl(modal).split('?')[0]
                    + '/' + encodeURIComponent(categoryId)
                    + window.location.search;
                window.history.replaceState({ categoryId: categoryId }, '', detailUrl);
            } catch (error) {}
        }
    }

    function closeModal(modal) {
        if (!modal) return;
        var isCategoryView = modal.hasAttribute('data-category-view-modal');
        var form = modal.querySelector('form');
        if (form && !modal.classList.contains('is-open-after-validation')) form.reset();
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        syncScrollLock();
        if (isCategoryView && window.location.pathname !== categoryIndexHistoryUrl(modal).split('?')[0]) {
            try {
                window.history.replaceState({}, '', categoryIndexHistoryUrl(modal));
            } catch (error) {}
        }
    }

    document.querySelectorAll('[data-open-modal]').forEach(function (button) {
        button.addEventListener('click', function () {
            openModal(button.dataset.openModal, button.dataset.categoryId);
        });
    });

    document.querySelectorAll('[data-close-modal]').forEach(function (button) {
        button.addEventListener('click', function () {
            closeModal(button.closest('.ec-modal'));
        });
    });

    document.querySelectorAll('.ec-modal').forEach(function (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) closeModal(modal);
        });
    });

    document.querySelectorAll('[data-delete-category]').forEach(function (button) {
        button.addEventListener('click', function () {
            var form = document.getElementById('delete-category-form');
            var message = document.getElementById('delete-category-message');
            var categoryName = button.dataset.deleteName || 'this category';
            if (form) form.action = button.dataset.deleteAction || '';
            if (message) message.textContent = 'Are you sure you want to delete "' + categoryName + '"?';
            openModal('category-delete-modal');
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        if (closeEcCustomSelects()) return;
        var openModals = document.querySelectorAll('.ec-modal.is-open');
        closeModal(openModals.length ? openModals[openModals.length - 1] : null);
    });

    syncScrollLock();
});
</script>
@endsection
