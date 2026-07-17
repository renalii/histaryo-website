@php
    $landmarks = $landmarks ?? [];
    $editCurator = $editCurator ?? null;
    $isEditMode = $editCurator !== null;
    $openDrawer = request()->boolean('create')
        || $isEditMode
        || session('open_curator_drawer')
        || ($errors->any() && ($errors->has('first_name') || $errors->has('email') || $errors->has('assigned_landmark_id')));
    $selectedLandmarkId = old('assigned_landmark_id', $isEditMode ? ($editCurator->assigned_landmark_id ?? '') : '');
    $firstNameValue = old('first_name', $isEditMode ? ($editCurator->first_name ?? '') : '');
    $lastNameValue = old('last_name', $isEditMode ? ($editCurator->last_name ?? '') : '');
    $emailValue = old('email', $isEditMode ? ($editCurator->email ?? '') : '');
    $selectedLandmarkLabel = '';
    foreach ($landmarks as $landmark) {
        if ($landmark['id'] === $selectedLandmarkId) {
            $selectedLandmarkLabel = $landmark['name'];
            break;
        }
    }
    $landmarkOptions = array_map(function ($landmark) {
        return [
            'id' => $landmark['id'],
            'label' => $landmark['name'],
        ];
    }, $landmarks);
@endphp

<style>
    .curator-drawer-backdrop {
        position: fixed;
        inset: 0;
        z-index: 1100;
        background: rgba(15, 23, 42, 0.58);
        backdrop-filter: blur(3px);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.25rem;
        overflow-y: auto;
        transition: opacity 0.24s ease, visibility 0.24s ease;
    }
    .curator-drawer-backdrop.is-open {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }
    .curator-drawer {
        position: relative;
        z-index: 1101;
        width: min(680px, calc(100vw - 2.5rem));
        max-height: none;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 24px 70px rgba(15, 23, 42, 0.28);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        opacity: 0;
        transform: scale(0.96);
        transition: opacity 0.24s ease, transform 0.24s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .curator-drawer-backdrop.is-open .curator-drawer {
        opacity: 1;
        transform: scale(1);
    }
    .curator-drawer__head {
        flex-shrink: 0;
        padding: 1rem 1.35rem .85rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
    }
    .curator-drawer__title {
        font-size: 1.35rem;
        font-weight: 800;
        color: #7A2E1F;
        margin: 0;
        line-height: 1.25;
    }
    .curator-drawer__lead {
        color: #6b7280;
        font-size: .9rem;
        line-height: 1.5;
        margin: .5rem 0 0;
    }
    .curator-drawer__lead strong { color: #374151; font-weight: 600; }
    .curator-drawer__close {
        flex-shrink: 0;
        width: 2.25rem;
        height: 2.25rem;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #fff;
        color: #6b7280;
        font-size: 1.35rem;
        line-height: 1;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .curator-drawer__close:hover { background: #f9fafb; color: #111827; }
    .curator-drawer__body {
        flex: 1;
        overflow-y: visible;
        padding: 1rem 1.35rem 1rem;
    }
    .cd-section { margin-bottom: .95rem; }
    .cd-section:last-of-type { margin-bottom: 0; }
    .cd-section-title {
        font-size: .78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #7A2E1F;
        margin: 0 0 .65rem 0;
        padding-bottom: .35rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .cd-field { margin-bottom: .75rem; }
    .cd-field label {
        display: block;
        font-weight: 600;
        font-size: .88rem;
        color: #1f2937;
        margin-bottom: .35rem;
    }
    .cd-input, .cd-select {
        width: 100%;
        padding: .62rem .8rem;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        font-size: .92rem;
        color: #111827;
        background: #fafafa;
        box-sizing: border-box;
    }
    .cd-input:focus, .cd-select:focus {
        outline: none;
        border-color: #d1d5db;
        box-shadow: none;
    }
    .cd-password {
        position: relative;
    }
    .cd-password .cd-input {
        padding-right: 2.75rem;
    }
    .cd-password__toggle {
        position: absolute;
        top: 50%;
        right: .45rem;
        width: 2rem;
        height: 2rem;
        padding: 0;
        transform: translateY(-50%);
        border: 0;
        border-radius: 8px;
        background: transparent;
        color: #6b7280;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .cd-password__toggle:hover,
    .cd-password__toggle:focus-visible {
        background: #f3f4f6;
        color: #111827;
    }
    .cd-password__toggle svg {
        width: 1.15rem;
        height: 1.15rem;
    }
    .cd-select {
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right .7rem center;
        background-size: 1rem;
        padding-right: 2.2rem;
    }
    .cd-grid-2 { display: grid; gap: .85rem; }
    @media (min-width: 400px) { .cd-grid-2 { grid-template-columns: 1fr 1fr; } }
    .cd-hint { font-size: .78rem; color: #9ca3af; margin: .3rem 0 0; }
    .cd-combobox { position: relative; }
    .cd-combobox::after {
        content: '';
        position: absolute;
        top: 23px;
        right: .8rem;
        width: .45rem;
        height: .45rem;
        border-right: 2px solid #374151;
        border-bottom: 2px solid #374151;
        transform: translateY(-70%) rotate(45deg);
        pointer-events: none;
    }
    .cd-combobox .cd-input {
        height: 46px;
        padding: .62rem 2rem .62rem .72rem;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #fff;
        font-size: .9rem;
        font-weight: 600;
    }
    .cd-combobox .cd-input:focus,
    .cd-combobox .cd-input:focus-visible {
        border-color: #d1d5db;
        outline: none;
        box-shadow: none;
    }
    .cd-combobox__list {
        position: fixed;
        z-index: 1200;
        box-sizing: border-box;
        max-height: 322px;
        overflow-x: hidden;
        overflow-y: auto;
        margin: 0;
        padding: 0;
        list-style: none;
        background: #fff;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, .15);
        scrollbar-width: thin;
        scrollbar-color: #c7c7c7 transparent;
        display: none;
    }
    .cd-combobox__list.is-open { display: block; }
    .cd-combobox__list::-webkit-scrollbar { width: 6px; }
    .cd-combobox__list::-webkit-scrollbar-thumb { background: #c7c7c7; border-radius: 999px; }
    .cd-combobox__list::-webkit-scrollbar-track { background: transparent; }
    .cd-combobox__option {
        display: block;
        width: 100%;
        height: 40px;
        padding: 0 .72rem;
        font-size: .9rem;
        font-weight: 400;
        line-height: 40px;
        color: #111827;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        cursor: pointer;
    }
    .cd-combobox__option:hover,
    .cd-combobox__option.is-highlighted,
    .cd-combobox__option.is-selected { background: #f3f4f6; outline: none; }
    .cd-combobox__empty {
        padding: .65rem .8rem;
        font-size: .85rem;
        color: #9ca3af;
    }
    .cd-actions {
        display: flex;
        flex-direction: column;
        gap: .5rem;
        margin-top: .85rem;
        padding-top: .85rem;
        border-top: 1px solid #f1f5f9;
    }
    .cd-section + .cd-actions { margin-top: 0; }
    .cd-actions--create {
        flex-direction: row;
        justify-content: flex-end;
        align-items: center;
        gap: 10px;
    }
    .cd-actions--create .cd-btn-primary,
    .cd-actions--create .cd-btn-secondary {
        width: auto;
        min-width: 110px;
        height: 40px;
        padding: 0 18px;
    }
    .cd-actions__edit-row {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 10px;
    }
    .cd-actions__edit-wrapper {
        display: flex;
        flex-direction: column;
        gap: .5rem;
        width: max-content;
        max-width: 100%;
        margin-left: auto;
    }
    .cd-actions__edit-row .cd-btn-primary,
    .cd-actions__edit-row .cd-btn-secondary {
        width: auto;
        min-width: 110px;
        height: 40px;
        padding: 0 18px;
    }
    .cd-actions__edit-wrapper .cd-btn-reset { width: 100%; }
    .cd-btn-primary {
        width: 100%;
        padding: .75rem 1.2rem;
        border-radius: 12px;
        border: 1px solid #F3C96A;
        background: linear-gradient(180deg, #f3d073 0%, #E8B34B 100%);
        color: #461c14;
        font-weight: 700;
        font-size: .92rem;
        cursor: pointer;
    }
    .cd-btn-secondary {
        width: 100%;
        padding: .72rem 1.2rem;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        background: #fff;
        color: #374151;
        font-weight: 600;
        font-size: .9rem;
        cursor: pointer;
    }
    .cd-btn-reset {
        width: 100%;
        padding: .72rem 1.2rem;
        border-radius: 12px;
        border: 1px solid #efb4ac;
        background: #fff;
        color: #7A2E1F;
        font-weight: 600;
        font-size: .9rem;
        cursor: pointer;
    }
    .cd-flash-err {
        padding: .65rem .9rem;
        border-radius: 10px;
        margin-bottom: .75rem;
        font-weight: 600;
        font-size: .88rem;
        background: #fef2f2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }
    .cd-flash-ok {
        padding: .65rem .9rem;
        border-radius: 10px;
        margin-bottom: .75rem;
        font-weight: 600;
        font-size: .88rem;
        background: #ecfdf5;
        color: #166534;
        border: 1px solid #bbf7d0;
    }
    .cd-empty {
        background: #f9fafb;
        border: 1px dashed #d1d5db;
        border-radius: 12px;
        padding: 1rem;
        color: #6b7280;
        font-size: .9rem;
    }
    .cd-empty a { color: #7A2E1F; font-weight: 600; }
</style>

<div id="curatorCreateDrawerBackdrop"
     class="curator-drawer-backdrop{{ $openDrawer ? ' is-open' : '' }}"
     aria-hidden="{{ $openDrawer ? 'false' : 'true' }}">
    <aside class="curator-drawer"
           role="dialog"
           aria-modal="true"
           aria-labelledby="curatorDrawerTitle"
           onclick="event.stopPropagation()">
        <header class="curator-drawer__head">
            <div>
                <h2 id="curatorDrawerTitle" class="curator-drawer__title">{{ $isEditMode ? 'Edit curator account' : 'Create curator account' }}</h2>
            </div>
            <button type="button"
                    class="curator-drawer__close"
                    id="closeCuratorDrawer"
                    aria-label="Close">&times;</button>
        </header>

        <div class="curator-drawer__body">
            @if (session('status'))
                <p class="cd-flash-ok" role="status">{{ session('status') }}</p>
            @endif
            @if (session('status_err'))
                <p class="cd-flash-err" role="alert">{{ session('status_err') }}</p>
            @endif
            @if ($errors->has('error'))
                <p class="cd-flash-err" role="alert">{{ $errors->first('error') }}</p>
            @endif

            @if (count($landmarks) === 0)
                <div class="cd-empty">
                    <p style="margin:0 0 .5rem 0;">You need at least one active landmark before you can create a curator.</p>
                    <p style="margin:0;">
                        <a href="{{ route('sitemanager.landmarks') }}#create-landmark">Create a landmark</a> first, then return here.
                    </p>
                </div>
            @else
                @if ($errors->any() && ! $errors->has('error'))
                    <div class="cd-flash-err" role="alert">
                        <p style="margin:0 0 .35rem 0;font-weight:700;">Please fix the following:</p>
                        <ul style="margin:0;padding-left:1.15rem;">
                            @foreach ($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ $isEditMode ? route('sitemanager.curators.update', ['uid' => $editCurator->uid]) : route('sitemanager.curators.store') }}" id="curatorCreateForm">
                    @csrf
                    @if ($isEditMode)
                        @method('PUT')
                    @endif

                    <div class="cd-section">
                        <h3 class="cd-section-title">Curator profile</h3>
                        <div class="cd-grid-2">
                            <div class="cd-field">
                                <label for="drawer_first_name">First name</label>
                                <input class="cd-input" id="drawer_first_name" name="first_name" type="text"
                                       autocomplete="given-name" value="{{ $firstNameValue }}" required>
                            </div>
                            <div class="cd-field">
                                <label for="drawer_last_name">Last name</label>
                                <input class="cd-input" id="drawer_last_name" name="last_name" type="text"
                                       autocomplete="family-name" value="{{ $lastNameValue }}" required>
                            </div>
                        </div>
                        <div class="cd-field">
                            <label for="drawer_email">Email address</label>
                            <input class="cd-input" id="drawer_email" name="email" type="email"
                                   autocomplete="email" value="{{ $emailValue }}" required>
                        </div>
                    </div>

                    <div class="cd-section">
                        <h3 class="cd-section-title">Assignment</h3>
                        <div class="cd-field">
                            <label for="drawer_landmark_search">Landmark</label>
                            <div class="cd-combobox" id="landmarkCombobox">
                                <input type="hidden"
                                       name="assigned_landmark_id"
                                       id="drawer_assigned_landmark_id"
                                       value="{{ $selectedLandmarkId }}"
                                       required>
                                <input class="cd-input"
                                       type="text"
                                       id="drawer_landmark_search"
                                       role="combobox"
                                       aria-expanded="false"
                                       aria-controls="landmarkComboboxList"
                                       aria-autocomplete="list"
                                       autocomplete="off"
                                       placeholder="Search landmarks…"
                                       value="{{ $selectedLandmarkLabel }}">
                                <ul class="cd-combobox__list"
                                    id="landmarkComboboxList"
                                    role="listbox"
                                    aria-label="Landmarks"></ul>
                            </div>
                        </div>
                    </div>

                    <div class="cd-actions{{ $isEditMode ? '' : ' cd-actions--create' }}" id="curatorDrawerActions">
                        @if ($isEditMode)
                            <div class="cd-actions__edit-wrapper">
                                <div class="cd-actions__edit-row">
                                    <button type="submit" class="cd-btn-primary" id="curatorSubmitButton">Save changes</button>
                                    <button type="button" class="cd-btn-secondary" id="cancelCuratorDrawer">Cancel</button>
                                </div>
                                <button type="submit" class="cd-btn-reset" id="curatorResetPasswordButton" form="curatorPasswordResetForm">Reset password</button>
                            </div>
                        @else
                            <button type="submit" class="cd-btn-primary" id="curatorSubmitButton">Create curator</button>
                            <button type="button" class="cd-btn-secondary" id="cancelCuratorDrawer">Cancel</button>
                        @endif
                    </div>
                </form>
                @if ($isEditMode)
                    <form method="POST" action="{{ route('sitemanager.curators.reset-password', ['uid' => $editCurator->uid]) }}" id="curatorPasswordResetForm">
                        @csrf
                    </form>
                    @if (($editCurator->account_status ?? 'active') === 'inactive')
                        <div class="cd-actions" style="margin-top:.75rem;">
                            <form method="POST" action="{{ route('sitemanager.curators.activate', ['uid' => $editCurator->uid]) }}">
                                @csrf
                                <button type="button" class="cd-btn-secondary js-curator-activate-action">Activate</button>
                            </form>
                        </div>
                    @endif
                @endif
            @endif
        </div>
    </aside>
</div>

<script>
(function () {
    var backdrop = document.getElementById('curatorCreateDrawerBackdrop');
    var openBtn = document.getElementById('openCuratorDrawer');
    var closeBtn = document.getElementById('closeCuratorDrawer');
    var cancelBtn = document.getElementById('cancelCuratorDrawer');
    var form = document.getElementById('curatorCreateForm');
    var title = document.getElementById('curatorDrawerTitle');
    var submitButton = document.getElementById('curatorSubmitButton');
    var drawerActions = document.getElementById('curatorDrawerActions');
    var resetPasswordButton = document.getElementById('curatorResetPasswordButton');
    var initialEditMode = @json($isEditMode);
    var hasSelectedCurator = @json($isEditMode && ! empty($editCurator?->uid));
    var modalMode = initialEditMode && hasSelectedCurator ? 'edit' : 'create';
    var firstNameInput = document.getElementById('drawer_first_name');
    var lastNameInput = document.getElementById('drawer_last_name');
    var emailInput = document.getElementById('drawer_email');
    var assignedLandmarkInput = document.getElementById('drawer_assigned_landmark_id');
    var landmarkSearchInput = document.getElementById('drawer_landmark_search');
    if (!backdrop) return;
    if (backdrop.classList.contains('is-open')) {
        document.body.style.overflow = 'hidden';
    }

    function curatorsBaseUrl() {
        return @json(route('sitemanager.curators'));
    }

    function curatorsUrlWithoutDrawerParams() {
        var url = new URL(window.location.href);
        url.searchParams.delete('edit');
        url.searchParams.delete('create');

        var query = url.searchParams.toString();
        return curatorsBaseUrl() + (query ? '?' + query : '');
    }

    function setCreateMode() {
        modalMode = 'create';
        backdrop.dataset.modalMode = modalMode;
        if (title) title.textContent = 'Create curator account';
        if (submitButton) submitButton.textContent = 'Create curator';
        if (drawerActions) drawerActions.classList.add('cd-actions--create');
        if (resetPasswordButton) resetPasswordButton.hidden = true;
        if (form) {
            form.setAttribute('action', @json(route('sitemanager.curators.store')));
            form.querySelectorAll('input[name="_method"]').forEach(function (input) {
                input.remove();
            });
        }
        if (firstNameInput) firstNameInput.value = '';
        if (lastNameInput) lastNameInput.value = '';
        if (emailInput) emailInput.value = '';
        if (assignedLandmarkInput) assignedLandmarkInput.value = '';
        if (landmarkSearchInput) {
            landmarkSearchInput.value = '';
            landmarkSearchInput.setCustomValidity('');
        }
    }

    function setEditMode() {
        modalMode = hasSelectedCurator ? 'edit' : 'create';
        backdrop.dataset.modalMode = modalMode;
        if (drawerActions) drawerActions.classList.toggle('cd-actions--create', modalMode !== 'edit');
        if (resetPasswordButton) resetPasswordButton.hidden = modalMode !== 'edit' || !hasSelectedCurator;
    }

    function showCuratorDrawer() {
        backdrop.classList.add('is-open');
        backdrop.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function openCuratorDrawer() {
        setCreateMode();
        showCuratorDrawer();
        try {
            history.replaceState(null, '', curatorsBaseUrl() + '?create=1');
        } catch (e) { /* ignore */ }
    }

    function closeCuratorDrawer() {
        backdrop.classList.remove('is-open');
        backdrop.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        setCreateMode();
        try {
            if (window.location.search.indexOf('create=1') !== -1 || window.location.search.indexOf('edit=') !== -1) {
                history.replaceState(null, '', curatorsUrlWithoutDrawerParams());
            }
        } catch (e) { /* ignore */ }
    }

    if (openBtn) {
        openBtn.addEventListener('click', openCuratorDrawer);
    }
    if (closeBtn) {
        closeBtn.addEventListener('click', closeCuratorDrawer);
    }
    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeCuratorDrawer);
    }
    backdrop.addEventListener('click', function (e) {
        if (e.target === backdrop) {
            closeCuratorDrawer();
        }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && backdrop.classList.contains('is-open')) {
            closeCuratorDrawer();
        }
    });

    if (modalMode === 'edit') {
        setEditMode();
    } else if (resetPasswordButton) {
        resetPasswordButton.hidden = true;
    }

    (function initLandmarkCombobox() {
        var root = document.getElementById('landmarkCombobox');
        var hidden = document.getElementById('drawer_assigned_landmark_id');
        var search = document.getElementById('drawer_landmark_search');
        var list = document.getElementById('landmarkComboboxList');
        var form = document.getElementById('curatorCreateForm');
        if (!root || !hidden || !search || !list) return;

        var options = @json($landmarkOptions);
        var highlighted = -1;
        var listOpen = false;

        document.body.appendChild(list);

        function normalize(text) {
            return (text || '').toLowerCase().trim();
        }

        function filteredOptions(query) {
            var q = normalize(query);
            if (!q) return options.slice();
            return options.filter(function (opt) {
                return normalize(opt.label).indexOf(q) !== -1;
            });
        }

        function setExpanded(open) {
            listOpen = open;
            search.setAttribute('aria-expanded', open ? 'true' : 'false');
            list.classList.toggle('is-open', open);
            if (open) positionList();
        }

        function positionList() {
            var viewport = window.visualViewport;
            var viewportTop = viewport ? viewport.offsetTop : 0;
            var viewportLeft = viewport ? viewport.offsetLeft : 0;
            var viewportHeight = viewport ? viewport.height : window.innerHeight;
            var viewportWidth = viewport ? viewport.width : document.documentElement.clientWidth;
            var viewportBottom = viewportTop + viewportHeight;
            var viewportRight = viewportLeft + viewportWidth;
            var padding = 8;
            var gap = 4;
            var maxMenuHeight = 322;
            var triggerRect = search.getBoundingClientRect();
            var spaceBelow = Math.max(0, viewportBottom - padding - triggerRect.bottom - gap);
            var spaceAbove = Math.max(0, triggerRect.top - viewportTop - padding - gap);

            list.style.width = Math.min(triggerRect.width, viewportWidth - (padding * 2)) + 'px';
            list.style.maxHeight = maxMenuHeight + 'px';
            var desiredHeight = Math.min(list.scrollHeight + 2, maxMenuHeight);
            var opensUp = spaceBelow < desiredHeight && spaceAbove > spaceBelow;
            var availableSpace = opensUp ? spaceAbove : spaceBelow;
            list.style.maxHeight = Math.max(0, Math.min(maxMenuHeight, availableSpace)) + 'px';

            var menuHeight = list.getBoundingClientRect().height;
            var top = opensUp ? triggerRect.top - gap - menuHeight : triggerRect.bottom + gap;
            var left = Math.max(
                viewportLeft + padding,
                Math.min(triggerRect.left, viewportRight - padding - list.getBoundingClientRect().width)
            );
            list.style.top = Math.max(viewportTop + padding, top) + 'px';
            list.style.left = left + 'px';
        }

        function clearSelection() {
            hidden.value = '';
            highlighted = -1;
        }

        function selectOption(opt) {
            hidden.value = opt.id;
            search.value = opt.label;
            setExpanded(false);
            renderList();
        }

        function renderList() {
            var matches = filteredOptions(search.value);
            list.innerHTML = '';
            highlighted = -1;

            if (!matches.length) {
                var empty = document.createElement('li');
                empty.className = 'cd-combobox__empty';
                empty.textContent = 'No landmarks match your search.';
                list.appendChild(empty);
                return;
            }

            matches.forEach(function (opt) {
                var item = document.createElement('li');
                item.className = 'cd-combobox__option';
                if (hidden.value === opt.id) {
                    item.classList.add('is-selected');
                }
                item.setAttribute('role', 'option');
                item.setAttribute('data-id', opt.id);
                item.textContent = opt.label;
                item.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    selectOption(opt);
                });
                list.appendChild(item);
            });
        }

        function highlightOption(delta) {
            var items = list.querySelectorAll('.cd-combobox__option');
            if (!items.length) return;
            highlighted += delta;
            if (highlighted < 0) highlighted = items.length - 1;
            if (highlighted >= items.length) highlighted = 0;
            items.forEach(function (el, i) {
                el.classList.toggle('is-highlighted', i === highlighted);
            });
            items[highlighted].scrollIntoView({ block: 'nearest' });
        }

        search.addEventListener('focus', function () {
            renderList();
            setExpanded(true);
        });

        search.addEventListener('input', function () {
            var current = options.find(function (opt) {
                return opt.id === hidden.value;
            });
            if (!current || current.label !== search.value) {
                clearSelection();
            }
            renderList();
            setExpanded(true);
        });

        search.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (!listOpen) {
                    renderList();
                    setExpanded(true);
                }
                highlightOption(1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (!listOpen) {
                    renderList();
                    setExpanded(true);
                }
                highlightOption(-1);
            } else if (e.key === 'Enter' && listOpen) {
                var items = list.querySelectorAll('.cd-combobox__option');
                if (items.length && highlighted >= 0) {
                    e.preventDefault();
                    var id = items[highlighted].getAttribute('data-id');
                    var opt = options.find(function (o) { return o.id === id; });
                    if (opt) selectOption(opt);
                }
            } else if (e.key === 'Escape') {
                if (listOpen) e.stopPropagation();
                setExpanded(false);
            }
        });

        document.addEventListener('mousedown', function (e) {
            if (listOpen && !root.contains(e.target) && !list.contains(e.target)) {
                setExpanded(false);
            }
        });
        window.addEventListener('resize', function () { setExpanded(false); });
        window.addEventListener('scroll', function (e) {
            if (!list.contains(e.target)) setExpanded(false);
        }, true);

        search.addEventListener('blur', function () {
            window.setTimeout(function () {
                setExpanded(false);
                var selected = options.find(function (opt) {
                    return opt.id === hidden.value;
                });
                if (selected) {
                    search.value = selected.label;
                    return;
                }
                var match = options.find(function (opt) {
                    return opt.label === search.value;
                });
                if (match) {
                    hidden.value = match.id;
                    search.value = match.label;
                } else if (search.value.trim() === '') {
                    clearSelection();
                } else {
                    clearSelection();
                    search.value = '';
                }
            }, 150);
        });

        if (form) {
            form.addEventListener('submit', function (e) {
                if (!hidden.value) {
                    e.preventDefault();
                    search.focus();
                    renderList();
                    setExpanded(true);
                    search.setCustomValidity('Select a landmark from the list.');
                    search.reportValidity();
                } else {
                    search.setCustomValidity('');
                }
            });
        }
    })();

    @if ($openDrawer)
    showCuratorDrawer();
    @endif
})();
</script>
