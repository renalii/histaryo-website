@php
    $landmarks = $landmarks ?? [];
    $allActiveLandmarksAssigned = $allActiveLandmarksAssigned ?? false;
    $openDrawer = request()->boolean('create')
        || session('open_curator_drawer')
        || ($errors->any() && ($errors->has('first_name') || $errors->has('email') || $errors->has('assigned_landmark_id') || $errors->has('password')));
    $selectedLandmarkId = old('assigned_landmark_id', '');
    $selectedLandmarkLabel = '';
    foreach ($landmarks as $landmark) {
        if ($landmark['id'] === $selectedLandmarkId) {
            $code = $landmark['landmarkcode'] !== '' ? ' ('.$landmark['landmarkcode'].')' : '';
            $selectedLandmarkLabel = $landmark['name'].$code;
            break;
        }
    }
    $landmarkOptions = array_map(function ($landmark) {
        $code = $landmark['landmarkcode'] !== '' ? ' ('.$landmark['landmarkcode'].')' : '';

        return [
            'id' => $landmark['id'],
            'label' => $landmark['name'].$code,
        ];
    }, $landmarks);
@endphp

<style>
    .curator-drawer-backdrop {
        position: fixed;
        inset: 0;
        z-index: 1100;
        background: rgba(15, 23, 42, 0.45);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 0.25s ease, visibility 0.25s ease;
    }
    .curator-drawer-backdrop.is-open {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }
    .curator-drawer {
        position: fixed;
        top: 0;
        right: 0;
        z-index: 1101;
        width: min(520px, 100vw);
        height: 100vh;
        background: #fff;
        box-shadow: -12px 0 40px rgba(15, 23, 42, 0.12);
        display: flex;
        flex-direction: column;
        transform: translateX(100%);
        transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .curator-drawer-backdrop.is-open .curator-drawer {
        transform: translateX(0);
    }
    .curator-drawer__head {
        flex-shrink: 0;
        padding: 1.25rem 1.5rem 1rem;
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
        overflow-y: auto;
        padding: 1.25rem 1.5rem 1.5rem;
    }
    .cd-section { margin-bottom: 1.35rem; }
    .cd-section:last-of-type { margin-bottom: 0; }
    .cd-section-title {
        font-size: .78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #7A2E1F;
        margin: 0 0 .85rem 0;
        padding-bottom: .45rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .cd-field { margin-bottom: 1rem; }
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
        border-color: #E8B34B;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(232, 179, 75, 0.22);
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
    .cd-combobox__list {
        position: absolute;
        z-index: 20;
        left: 0;
        right: 0;
        top: calc(100% + 4px);
        max-height: 220px;
        overflow-y: auto;
        margin: 0;
        padding: .35rem 0;
        list-style: none;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.12);
        display: none;
    }
    .cd-combobox__list.is-open { display: block; }
    .cd-combobox__option {
        padding: .55rem .8rem;
        font-size: .9rem;
        color: #111827;
        cursor: pointer;
    }
    .cd-combobox__option:hover,
    .cd-combobox__option.is-highlighted {
        background: #fef9ee;
        color: #7A2E1F;
    }
    .cd-combobox__option.is-selected { font-weight: 600; }
    .cd-combobox__empty {
        padding: .65rem .8rem;
        font-size: .85rem;
        color: #9ca3af;
    }
    .cd-actions {
        display: flex;
        flex-direction: column;
        gap: .55rem;
        margin-top: 1.25rem;
        padding-top: 1.15rem;
        border-top: 1px solid #f1f5f9;
    }
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
    .cd-flash-err {
        padding: .75rem .95rem;
        border-radius: 10px;
        margin-bottom: 1rem;
        font-weight: 600;
        font-size: .88rem;
        background: #fef2f2;
        color: #991b1b;
        border: 1px solid #fecaca;
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
                <h2 id="curatorDrawerTitle" class="curator-drawer__title">Create curator account</h2>
            </div>
            <button type="button"
                    class="curator-drawer__close"
                    id="closeCuratorDrawer"
                    aria-label="Close">&times;</button>
        </header>

        <div class="curator-drawer__body">
            @if ($errors->has('error'))
                <p class="cd-flash-err" role="alert">{{ $errors->first('error') }}</p>
            @endif

            @if (count($landmarks) === 0)
                <div class="cd-empty">
                    @if ($allActiveLandmarksAssigned)
                        <p style="margin:0 0 .5rem 0;">Every active landmark in your portfolio already has a curator assigned.</p>
                        <p style="margin:0;">Reassign or remove a curator, or <a href="{{ route('sitemanager.landmarks') }}#create-landmark">add another landmark</a>, before creating a new curator account.</p>
                    @else
                        <p style="margin:0 0 .5rem 0;">You need at least one active landmark before you can create a curator.</p>
                        <p style="margin:0;">
                            <a href="{{ route('sitemanager.landmarks') }}#create-landmark">Create a landmark</a> first, then return here.
                        </p>
                    @endif
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

                <form method="POST" action="{{ route('sitemanager.curators.store') }}" id="curatorCreateForm">
                    @csrf

                    <div class="cd-section">
                        <h3 class="cd-section-title">Curator profile</h3>
                        <div class="cd-grid-2">
                            <div class="cd-field">
                                <label for="drawer_first_name">First name</label>
                                <input class="cd-input" id="drawer_first_name" name="first_name" type="text"
                                       autocomplete="given-name" value="{{ old('first_name') }}" required>
                            </div>
                            <div class="cd-field">
                                <label for="drawer_last_name">Last name</label>
                                <input class="cd-input" id="drawer_last_name" name="last_name" type="text"
                                       autocomplete="family-name" value="{{ old('last_name') }}" required>
                            </div>
                        </div>
                        <div class="cd-field">
                            <label for="drawer_email">Email address</label>
                            <input class="cd-input" id="drawer_email" name="email" type="email"
                                   autocomplete="email" value="{{ old('email') }}" required>
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
                            <p class="cd-hint">Only active, unassigned landmarks in your portfolio are listed. Type to search.</p>
                        </div>
                    </div>

                    <div class="cd-section">
                        <h3 class="cd-section-title">Sign-in credentials</h3>
                        <div class="cd-grid-2">
                            <div class="cd-field">
                                <label for="drawer_password">Temporary password</label>
                                <input class="cd-input" id="drawer_password" name="password" type="password"
                                       autocomplete="new-password" minlength="8" required>
                                <p class="cd-hint">Minimum 8 characters.</p>
                            </div>
                            <div class="cd-field">
                                <label for="drawer_password_confirmation">Confirm password</label>
                                <input class="cd-input" id="drawer_password_confirmation" name="password_confirmation"
                                       type="password" autocomplete="new-password" minlength="8" required>
                            </div>
                        </div>
                    </div>

                    <div class="cd-actions">
                        <button type="submit" class="cd-btn-primary">Create curator</button>
                        <button type="button" class="cd-btn-secondary" id="cancelCuratorDrawer">Cancel</button>
                    </div>
                </form>
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
    if (!backdrop) return;

    function curatorsBaseUrl() {
        return @json(route('sitemanager.curators'));
    }

    function openCuratorDrawer() {
        backdrop.classList.add('is-open');
        backdrop.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        if (window.location.search.indexOf('create=1') === -1) {
            try {
                history.replaceState(null, '', curatorsBaseUrl() + '?create=1');
            } catch (e) { /* ignore */ }
        }
    }

    function closeCuratorDrawer() {
        backdrop.classList.remove('is-open');
        backdrop.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        try {
            if (window.location.search.indexOf('create=1') !== -1) {
                history.replaceState(null, '', curatorsBaseUrl());
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
                setExpanded(false);
            }
        });

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
    openCuratorDrawer();
    @endif
})();
</script>
