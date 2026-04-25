@extends('layouts.sidebar')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 style="font-size: 1.5rem; font-weight: 700; color: #7A2E1F; margin: 0;">📦 Landmarks</h2>

        <button onclick="openModal('createModal')" style="background-color: #E8B34B; color: #7A2E1F; padding: 10px 16px; font-weight: 700; border-radius: 6px; border: 1px solid #F3C96A; cursor: pointer;">
            + Add New Landmark
        </button>
    </div>

    {{-- Flash --}}
    @if (session('success'))
        <div style="background-color: #d1fae5; color: #065f46; padding: 1rem 1.25rem; border-radius: 8px; margin-bottom: 1.5rem;">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div style="background-color: #fee2e2; color: #991b1b; padding: 1rem 1.25rem; border-radius: 8px; margin-bottom: 1.5rem;">
            {{ session('error') }}
        </div>
    @endif

    {{-- Filter --}}
    <div style="margin-bottom: 1.5rem; display:flex; align-items:center; gap:1rem;">
        <form method="GET" action="{{ route('landmarks.index') }}" style="display:flex; align-items:center; gap:.75rem;">
            <label for="category" style="font-weight:600; color:#374151;">Filter by Category:</label>
            <div style="position: relative;">
                <select name="category" id="category" onchange="this.form.submit()"
                        style="
                            appearance: none;
                            -webkit-appearance: none;
                            -moz-appearance: none;
                            padding: .6rem 2.5rem .6rem 1rem;
                            border: 1px solid #d1d5db;
                            border-radius: 10px;
                            font-size: .95rem;
                            font-weight: 500;
                            color: #374151;
                            background-color: #ffffff;
                            cursor: pointer;
                            transition: all .2s ease-in-out;
                            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
                        "
                        onmouseover="this.style.borderColor='#E8B34B'"
                        onmouseout="this.style.borderColor='#d1d5db'">
                    <option value="">-- All Categories --</option>
                    <option value="Historical" {{ ($selectedCategory ?? '') == 'Historical' ? 'selected' : '' }}>Historical</option>
                    <option value="Natural" {{ ($selectedCategory ?? '') == 'Natural' ? 'selected' : '' }}>Natural</option>
                    <option value="Cultural" {{ ($selectedCategory ?? '') == 'Cultural' ? 'selected' : '' }}>Cultural</option>
                    <option value="Religious" {{ ($selectedCategory ?? '') == 'Religious' ? 'selected' : '' }}>Religious</option>
                    <option value="Modern" {{ ($selectedCategory ?? '') == 'Modern' ? 'selected' : '' }}>Modern</option>
                </select>

                {{-- Custom arrow --}}
                <span style="
                    position: absolute;
                    right: 1rem;
                    top: 50%;
                    transform: translateY(-50%);
                    pointer-events: none;
                    font-size: .85rem;
                    color: #6b7280;">
                    ▼
                </span>
            </div>
        </form>
    </div>

    {{-- Landmarks --}}
    @if ($landmarks->total() === 0)
        <p style="color: #6b7280;">No landmarks available.</p>
    @else
        <div class="lm-landmark-grid" style="display: flex; flex-wrap: wrap; gap: 1.5rem; align-items: stretch;">
            @foreach ($landmarks as $landmark)
                @php
                    $data = $landmark->data();
                    $videoUrl = $data['video_url'] ?? '';
                    $embedUrl = '';
                    $imageSrc = null;

                    if (!empty($data['image_base64'])) {
                        $imageMime = $data['image_mime'] ?? 'image/jpeg';
                        $imageSrc = 'data:' . $imageMime . ';base64,' . $data['image_base64'];
                    }

                    if (strpos($videoUrl, 'youtube.com/watch') !== false) {
                        $embedUrl = str_replace('watch?v=', 'embed/', $videoUrl);
                    } elseif (strpos($videoUrl, 'youtu.be/') !== false) {
                        $videoId = explode('youtu.be/', $videoUrl)[1] ?? '';
                        $embedUrl = 'https://www.youtube.com/embed/' . $videoId;
                    } else {
                        $embedUrl = $videoUrl;
                    }
                @endphp

                <div class="lm-landmark-card" style="background: white; border-radius: 10px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05); width: 100%; max-width: 420px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 0.75rem;">
                        <div style="min-width: 0; flex: 1;">
                            <strong style="font-size: 1.2rem; color: #7A2E1F; display: block;">{{ $data['name'] ?? 'Unnamed Landmark' }}</strong>
                            {{-- Category --}}
                            <p style="margin: 0.25rem 0 0; font-size: 0.85rem; color: #2563eb; font-weight: 600;">
                                🏷️ {{ $data['category'] ?? 'Uncategorized' }}
                            </p>
                        </div>
                        <div class="lm-card-menu-wrap" style="position: relative; flex-shrink: 0;">
                            <button type="button"
                                    class="lm-card-menu-btn"
                                    aria-haspopup="true"
                                    aria-expanded="false"
                                    aria-label="Landmark actions"
                                    onclick="toggleLandmarkMenu(event, this)">⋮</button>
                            <div class="lm-card-menu" role="menu" hidden>
                                <button type="button" role="menuitem" class="lm-card-menu-item lm-card-menu-item--view"
                                        onclick="closeLandmarkMenus(); openModal('showModal{{ $loop->index }}')">View</button>
                                <button type="button" role="menuitem" class="lm-card-menu-item lm-card-menu-item--edit"
                                        onclick="closeLandmarkMenus(); openModal('editModal{{ $loop->index }}')">Edit</button>
                                <a href="{{ route('curators.qr.byLandmark', $landmark->id()) }}"
                                   role="menuitem"
                                   class="lm-card-menu-item lm-card-menu-item--qr"
                                   onclick="closeLandmarkMenus()">Download QR</a>
                                <form id="deleteLandmarkForm{{ $loop->index }}" action="{{ route('landmarks.destroy', ['landmark' => $landmark->id()]) }}" method="POST" class="lm-card-menu-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button"
                                            role="menuitem"
                                            class="lm-card-menu-item lm-card-menu-item--delete"
                                            data-delete-form="deleteLandmarkForm{{ $loop->index }}"
                                            data-landmark-name="{{ $data['name'] ?? 'Unnamed Landmark' }}"
                                            onclick="closeLandmarkMenus(); openDeleteLandmarkModal(this)">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <p style="margin: 0.75rem 0; color: #4b5563; font-size: 0.95rem;">
                        {{ $data['description'] ?? 'No description.' }}
                    </p>

                    @if (!empty($imageSrc))
                        <div class="lm-landmark-card__media">
                            <img src="{{ $imageSrc }}"
                                 alt="Uploaded Image"
                                 style="max-width: 100%; border-radius: 6px; display: block; width: 100%;">
                        </div>
                    @endif
                </div>

                {{-- View Modal --}}
                <div id="showModal{{ $loop->index }}" class="modal">
                    <div class="modal-content">
                        <span class="close" onclick="closeModal('showModal{{ $loop->index }}')">&times;</span>
                        <h3>{{ $data['name'] ?? 'Unnamed Landmark' }}</h3>
                        <p><strong>Category:</strong> {{ $data['category'] ?? 'Uncategorized' }}</p>
                        <p>{{ $data['description'] ?? 'No description.' }}</p>
                        <p>Latitude: {{ $data['latitude'] ?? 'N/A' }}</p>
                        <p>Longitude: {{ $data['longitude'] ?? 'N/A' }}</p>

                        @if (!empty($imageSrc))
                            <img src="{{ $imageSrc }}" style="max-width: 100%; margin-top: 10px;">
                        @endif

                        @if (!empty($embedUrl))
                            <div style="margin-top: 1rem;">
                                <iframe width="100%" height="250" src="{{ $embedUrl }}" frameborder="0" allowfullscreen></iframe>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Edit Modal --}}
                <div id="editModal{{ $loop->index }}" class="modal">
                    <div class="modal-content">
                        <span class="close" onclick="closeModal('editModal{{ $loop->index }}')">&times;</span>
                        <form method="POST" action="{{ route('landmarks.update', ['landmark' => $landmark->id()]) }}" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <label>Name:</label>
                            <input type="text" name="name" value="{{ $data['name'] }}" required>

                            <label>Category:</label>
                            <select name="category" required>
                                <option value="Historical" {{ ($data['category'] ?? '') == 'Historical' ? 'selected' : '' }}>Historical</option>
                                <option value="Natural" {{ ($data['category'] ?? '') == 'Natural' ? 'selected' : '' }}>Natural</option>
                                <option value="Cultural" {{ ($data['category'] ?? '') == 'Cultural' ? 'selected' : '' }}>Cultural</option>
                                <option value="Religious" {{ ($data['category'] ?? '') == 'Religious' ? 'selected' : '' }}>Religious</option>
                                <option value="Modern" {{ ($data['category'] ?? '') == 'Modern' ? 'selected' : '' }}>Modern</option>
                            </select>

                            <label>Description:</label>
                            <textarea name="description">{{ $data['description'] }}</textarea>

                            <label>Latitude:</label>
                            <input type="text" name="latitude" value="{{ $data['latitude'] }}" required>

                            <label>Longitude:</label>
                            <input type="text" name="longitude" value="{{ $data['longitude'] }}" required>

                            <label>Video URL:</label>
                            <input type="url" name="video_url" value="{{ $data['video_url'] ?? '' }}">

                            <label>Replace Image:</label>
                            <input type="file" name="image">

                            <button type="submit">Update</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if ($landmarks->hasPages())
            <div style="display:flex; justify-content:flex-end; margin-top:1.4rem;">
                <div style="display:flex; align-items:center; gap:.5rem; padding:.2rem;">
                    @if ($landmarks->onFirstPage())
                        <span aria-disabled="true" style="padding:.42rem .7rem; border-radius:7px; background:#f3f4f6; color:#c0c4cc; cursor:not-allowed; font-weight:600; border:1px solid #eef1f5;">← Prev</span>
                    @else
                        <a href="{{ $landmarks->previousPageUrl() }}" aria-label="Go to previous page" style="padding:.42rem .7rem; border-radius:7px; background:#f8fafc; color:#6b7280; text-decoration:none; border:1px solid #e5e7eb; font-weight:600;">← Prev</a>
                    @endif

                    <span style="color:#6b7280; font-weight:600; padding:0 .15rem;">
                        Page {{ $landmarks->currentPage() }} of {{ $landmarks->lastPage() }}
                    </span>

                    @if ($landmarks->hasMorePages())
                        <a href="{{ $landmarks->nextPageUrl() }}" aria-label="Go to next page" style="padding:.42rem .7rem; border-radius:7px; background:#E8B34B; color:#7A2E1F; text-decoration:none; border:1px solid #F3C96A; font-weight:700;">Next →</a>
                    @else
                        <span aria-disabled="true" style="padding:.42rem .7rem; border-radius:7px; background:#f3f4f6; color:#c0c4cc; cursor:not-allowed; font-weight:600; border:1px solid #eef1f5;">Next →</span>
                    @endif
                </div>
            </div>
        @endif
    @endif

    {{-- Delete confirmation --}}
    <div id="deleteLandmarkModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="deleteLandmarkModalTitle">
        <div class="modal-content lm-delete-modal-content">
            <span class="close" onclick="closeModal('deleteLandmarkModal')" aria-label="Close">&times;</span>
            <h3 id="deleteLandmarkModalTitle" class="lm-delete-modal-title">Delete Landmark</h3>
            <p id="deleteLandmarkModalMessage" class="lm-delete-modal-message"></p>
            <div class="lm-delete-modal-actions">
                <button type="button" class="lm-delete-confirm-btn" id="deleteLandmarkConfirmBtn">Delete</button>
                <button type="button" class="lm-delete-cancel-btn" onclick="closeModal('deleteLandmarkModal')">Cancel</button>
            </div>
        </div>
    </div>

    {{-- Create Modal --}}
    <div id="createModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('createModal')">&times;</span>
            <form method="POST" action="{{ route('landmarks.store') }}" enctype="multipart/form-data">
                @csrf

                <label>Landmark Name:</label>
                <input type="text" name="name" required>

                <label>Category:</label>
                <select name="category" required>
                    <option value="Historical">Historical</option>
                    <option value="Natural">Natural</option>
                    <option value="Cultural">Cultural</option>
                    <option value="Religious">Religious</option>
                    <option value="Modern">Modern</option>
                </select>

                <label>Description:</label>
                <textarea name="description" rows="4" cols="50"></textarea>

                <label>Latitude:</label>
                <input type="text" name="latitude" placeholder="e.g., 10.3157" required>

                <label>Longitude:</label>
                <input type="text" name="longitude" placeholder="e.g., 123.8854" required>

                <label>Video URL:</label>
                <input type="url" name="video_url">

                <label>Upload Old Photo:</label>
                <input type="file" name="image" accept="image/*">

                <button type="submit">Save</button>
            </form>
        </div>
    </div>

    {{-- Styles --}}
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            inset: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            padding: 2rem;
        }
        .modal-content {
            background: #fefefe;
            margin: auto;
            padding: 1.5rem 2rem;
            border-radius: 14px;
            max-width: 580px;
            width: 100%;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.1);
            position: relative;
            animation: fadeIn 0.3s ease-in-out;
            font-family: 'Segoe UI', sans-serif;
            max-height:93vh;
            overflow-y: auto;
        }
        .modal-content h3 {
            margin-top: 0;
            font-size: 1.4rem;
            font-weight: 600;
            color: #4c1d95;
            margin-bottom: 1rem;
        }
        .modal-content label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-top: 1rem;
            margin-bottom: 0.4rem;
        }
        .modal-content input[type="text"],
        .modal-content input[type="url"],
        .modal-content input[type="file"],
        .modal-content textarea,
        .modal-content select {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background-color: #f9fafb;
            font-size: 0.875rem;
            color: #111827;
            box-sizing: border-box;
        }
        .modal-content button[type="submit"] {
            margin-top: 1.5rem;
            background-color: #E8B34B;
            color: #7A2E1F;
            padding: 0.5rem 1rem;
            border: none;
            font-size: 0.9rem;
            border-radius: 8px;
            font-weight: 700;
            border: 1px solid #F3C96A;
            cursor: pointer;
            transition: background-color 0.2s ease-in-out;
        }
        .modal-content button[type="submit"]:hover {
            background-color: #F3C96A;
        }
        .close {
            position: absolute;
            top: 14px;
            right: 18px;
            font-size: 26px;
            font-weight: bold;
            color: #6b7280;
            cursor: pointer;
            transition: color 0.2s;
        }
        .close:hover { color: #111827; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

        .lm-card-menu-btn {
            width: 2.25rem;
            height: 2.25rem;
            padding: 0;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #f9fafb;
            color: #4b5563;
            font-size: 1.25rem;
            line-height: 1;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.15s, border-color 0.15s, color 0.15s;
        }
        .lm-card-menu-btn:hover {
            background: #f3f4f6;
            border-color: #d1d5db;
            color: #111827;
        }
        .lm-card-menu {
            position: absolute;
            top: calc(100% + 4px);
            right: 0;
            min-width: 11rem;
            padding: 0.35rem 0;
            margin: 0;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.1);
            z-index: 50;
        }
        .lm-card-menu:not([hidden]) {
            display: block;
        }
        .lm-card-menu[hidden] {
            display: none !important;
        }
        .lm-card-menu-item {
            display: block;
            width: 100%;
            text-align: left;
            padding: 0.55rem 1rem;
            border: none;
            background: none;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            font-family: inherit;
            box-sizing: border-box;
            transition: background 0.12s;
        }
        .lm-card-menu-item:hover {
            background: #f3f4f6;
        }
        .lm-card-menu-item--view { color: #2563eb; }
        .lm-card-menu-item--edit { color: #92400e; }
        .lm-card-menu-item--qr { color: #16a34a; }
        .lm-card-menu-item--delete { color: #dc2626; }
        .lm-card-menu-form {
            margin: 0;
            padding: 0;
        }

        /* Equal-height cards in a row; pin image to card bottom */
        .lm-landmark-card {
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
        }
        .lm-landmark-card__media {
            margin-top: auto;
            padding-top: 0.5rem;
        }

        .lm-delete-modal-content {
            max-width: 440px;
        }
        .modal-content .lm-delete-modal-title {
            color: #7A2E1F;
            font-size: 1.35rem;
            margin-bottom: 0.75rem;
        }
        .lm-delete-modal-message {
            color: #4b5563;
            line-height: 1.55;
            margin: 0;
            font-size: 0.95rem;
        }
        .lm-delete-modal-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.65rem;
            margin-top: 1.35rem;
        }
        .lm-delete-confirm-btn {
            background-color: #dc2626;
            color: #fff;
            border: 1px solid #b91c1c;
            padding: 0.5rem 1.15rem;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            font-family: inherit;
            transition: background-color 0.15s, border-color 0.15s;
        }
        .lm-delete-confirm-btn:hover {
            background-color: #b91c1c;
            border-color: #991b1b;
        }
        .lm-delete-cancel-btn {
            background: #fff;
            color: #374151;
            border: 1px solid #d1d5db;
            padding: 0.5rem 1.15rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            font-family: inherit;
            transition: background 0.15s, border-color 0.15s;
        }
        .lm-delete-cancel-btn:hover {
            background: #f9fafb;
            border-color: #9ca3af;
        }
    </style>

    {{-- Scripts --}}
    <script>
        var pendingDeleteLandmarkForm = null;

        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
            document.body.style.overflow = '';
            if (id === 'deleteLandmarkModal') {
                pendingDeleteLandmarkForm = null;
            }
        }
        function openDeleteLandmarkModal(btn) {
            var formId = btn.getAttribute('data-delete-form');
            var form = formId ? document.getElementById(formId) : null;
            if (!form) return;
            pendingDeleteLandmarkForm = form;
            var name = btn.getAttribute('data-landmark-name') || 'Unnamed Landmark';
            var msgEl = document.getElementById('deleteLandmarkModalMessage');
            if (msgEl) {
                msgEl.textContent = 'Are you sure you want to delete \u201c' + name + '\u201d?';
            }
            openModal('deleteLandmarkModal');
        }
        function confirmDeleteLandmark() {
            if (pendingDeleteLandmarkForm) {
                pendingDeleteLandmarkForm.submit();
            }
            pendingDeleteLandmarkForm = null;
            closeModal('deleteLandmarkModal');
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
        window.onclick = function(event) {
            if (!event.target.closest('.lm-card-menu-wrap')) {
                closeLandmarkMenus();
            }
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = "none";
                    document.body.style.overflow = '';
                    if (modal.id === 'deleteLandmarkModal') {
                        pendingDeleteLandmarkForm = null;
                    }
                }
            });
        };

        var deleteConfirmBtn = document.getElementById('deleteLandmarkConfirmBtn');
        if (deleteConfirmBtn) {
            deleteConfirmBtn.addEventListener('click', confirmDeleteLandmark);
        }
    </script>
@endsection
