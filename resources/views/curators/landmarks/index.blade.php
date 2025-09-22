@extends('layouts.sidebar')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 style="font-size: 1.5rem; font-weight: 700; color: #6d28d9; margin: 0;">📦 Landmarks</h2>

        <button onclick="openModal('createModal')" style="background-color: #8b5cf6; color: white; padding: 10px 16px; font-weight: 600; border-radius: 6px; border: none; cursor: pointer;">
            + Add New Landmark
        </button>
    </div>

    {{-- Success message --}}
    @if (session('success'))
        <div style="background-color: #d1fae5; color: #065f46; padding: 1rem 1.25rem; border-radius: 8px; margin-bottom: 1.5rem;">
            {{ session('success') }}
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
                    onmouseover="this.style.borderColor='#8b5cf6'"
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
                    color: #6b7280;
                ">
                    ▼
                </span>
            </div>
        </form>
    </div>

    {{-- Landmarks --}}
    @if ($landmarks->total() === 0)
        <p style="color: #6b7280;">No landmarks available.</p>
    @else
        <div style="display: flex; flex-wrap: wrap; gap: 1.5rem;">
            @foreach ($landmarks as $landmark)
                @php
                    $data = $landmark->data();
                    $videoUrl = $data['video_url'] ?? '';
                    $embedUrl = '';

                    if (strpos($videoUrl, 'youtube.com/watch') !== false) {
                        $embedUrl = str_replace('watch?v=', 'embed/', $videoUrl);
                    } elseif (strpos($videoUrl, 'youtu.be/') !== false) {
                        $videoId = explode('youtu.be/', $videoUrl)[1] ?? '';
                        $embedUrl = 'https://www.youtube.com/embed/' . $videoId;
                    }
                @endphp

                <div style="background: white; border-radius: 10px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05); width: 100%; max-width: 400px;">
                    <strong style="font-size: 1.2rem; color: #7c3aed;">{{ $data['name'] ?? 'Unnamed Landmark' }}</strong>
                    
                    {{-- Category --}}
                    <p style="margin: 0.25rem 0; font-size: 0.85rem; color: #2563eb; font-weight: 600;">
                        🏷️ {{ $data['category'] ?? 'Uncategorized' }}
                    </p>

                    <p style="margin: 0.75rem 0; color: #4b5563; font-size: 0.95rem;">
                        {{ $data['description'] ?? 'No description.' }}
                    </p>

                    @if (!empty($data['image_path']))
                        <img src="{{ asset('storage/' . $data['image_path']) }}" alt="Uploaded Image"
                             style="max-width: 100%; border-radius: 6px; margin-top: 0.5rem;">
                    @endif

                    <div style="margin-top: 1rem;">
                        <button onclick="openModal('showModal{{ $loop->index }}')" style="margin-right: 10px; color: #2563eb; background: none; border: none; cursor: pointer;">👁️ View</button>
                        <button onclick="openModal('editModal{{ $loop->index }}')" style="margin-right: 10px; color: #92400e; background: none; border: none; cursor: pointer;">✏️ Edit</button>

                        <form action="{{ route('landmarks.destroy', ['landmark' => $landmark->id()]) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" style="background: none; border: none; color: #dc2626; cursor: pointer;">🗑️ Delete</button>
                        </form>
                    </div>
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

                        @if (!empty($data['image_path']))
                            <img src="{{ asset('storage/' . $data['image_path']) }}" style="max-width: 100%; margin-top: 10px;">
                        @endif

                        @if ($embedUrl)
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
            <div style="display:flex; align-items:center; gap:.75rem; margin-top:1.5rem;">
                @if ($landmarks->onFirstPage())
                    <span style="padding:.5rem .75rem; border-radius:8px; background:#f3f4f6; color:#9ca3af; cursor:not-allowed;">← Prev</span>
                @else
                    <a href="{{ $landmarks->previousPageUrl() }}" style="padding:.5rem .75rem; border-radius:8px; background:#8b5cf6; color:#fff; text-decoration:none;">← Prev</a>
                @endif

                <span style="color:#6b7280;">
                    Page {{ $landmarks->currentPage() }} of {{ $landmarks->lastPage() }}
                </span>

                @if ($landmarks->hasMorePages())
                    <a href="{{ $landmarks->nextPageUrl() }}" style="padding:.5rem .75rem; border-radius:8px; background:#8b5cf6; color:#fff; text-decoration:none;">Next →</a>
                @else
                    <span style="padding:.5rem .75rem; border-radius:8px; background:#f3f4f6; color:#9ca3af; cursor:not-allowed;">Next →</span>
                @endif
            </div>
        @endif
    @endif

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
        background-color: #8b5cf6;
        color: white;
        padding: 0.5rem 1rem;
        border: none;
        font-size: 0.9rem;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.2s ease-in-out;
    }

    .modal-content button[type="submit"]:hover {
        background-color: #7c3aed;
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

    .close:hover {
        color: #111827;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    </style>

    {{-- Scripts --}}
    <script>
        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
            document.body.style.overflow = '';
        }

        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = "none";
                    document.body.style.overflow = '';
                }
            });
        };
    </script>
@endsection
