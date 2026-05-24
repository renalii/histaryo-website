@extends('layouts.sidebar')

@section('content')
    @php
        /** @var string $id */
        /** @var array $landmark */
        /** @var string|null $mapboxToken */
        $modalSafe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $id);
        $fieldHash = md5($id);
    @endphp

    <div class="cl-edit-wrap">
        <div class="cl-edit-head">
            <div>
                <h1 class="cl-edit-title">Edit landmark</h1>
                <p class="cl-edit-muted">Update details, location, and media for your assigned site.</p>
            </div>
            <a href="{{ route('landmarks.show', $id) }}" class="cl-back">← Back to landmark</a>
        </div>

        @if (session('success'))
            <p class="cl-flash-ok" role="status">{{ session('success') }}</p>
        @endif

        @if ($errors->any())
            <div class="cl-flash-err" role="alert">
                <strong>Please fix:</strong>
                <ul>
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('landmarks.update', $id) }}" enctype="multipart/form-data" class="lm-editor-form">
            @csrf
            @method('PUT')

            @include('curators.landmarks.partials.landmark-form-fields', [
                'landmarkId' => $id,
                'd' => $landmark,
                'modalSafe' => $modalSafe,
                'fieldHash' => $fieldHash,
                'mapboxToken' => $mapboxToken ?? null,
                'formContext' => 'page',
            ])

            <div class="cl-actions">
                <button type="submit" class="cl-btn-submit">Save changes</button>
                <a href="{{ route('landmarks.show', $id) }}" class="cl-back">Cancel</a>
            </div>
        </form>

        <div class="cl-card cl-danger">
            <div class="cl-card-inner">
                <h2 class="cl-section-title" style="margin-top:0;color:#991b1b;border-color:#fecaca;">Danger zone</h2>
                <p class="cl-edit-muted">Deleting removes this landmark record, QR links, trivia, and synced images tied to this id.</p>
                <form method="POST" action="{{ route('landmarks.destroy', $id) }}" style="margin-top:.85rem;"
                      onsubmit="return confirm('Delete this landmark permanently? This cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="cl-btn-delete">Delete landmark</button>
                </form>
            </div>
        </div>
    </div>

    @include('curators.landmarks.partials.landmark-editor-styles')
    <style>
        .cl-edit-wrap { width:100%; max-width:1100px; margin:0 auto 2rem; }
        .cl-edit-head {
            margin-bottom:1.25rem;
            display:flex; flex-wrap:wrap; justify-content:space-between; gap:.75rem; align-items:flex-start;
        }
        .cl-edit-title {
            margin:0; font-size:clamp(1.45rem,3vw,1.85rem); font-weight:800; color:#7A2E1F; letter-spacing:-.02em;
        }
        .cl-edit-muted { margin:.35rem 0 0 0; color:#6b7280; font-size:.92rem; }
        .cl-back {
            padding:.55rem 1rem; border-radius:10px; border:1px solid #e5e7eb;
            background:#fff; color:#374151; font-weight:600; font-size:.88rem;
            text-decoration:none; transition:background .15s ease, border-color .15s ease;
        }
        .cl-back:hover { background:#f9fafb; border-color:#d1d5db; }
        .cl-card {
            background:#fff; border-radius:14px;
            border:1px solid #eceff3; box-shadow:0 8px 30px rgba(15,23,42,.06); overflow:hidden;
            margin-top:1.75rem;
        }
        .cl-card-inner { padding:1.35rem 1.5rem 1.25rem; }
        .cl-flash-ok {
            padding:.85rem 1.1rem; border-radius:12px;
            background:#ecfdf5; color:#166534; border:1px solid #bbf7d0; margin-bottom:1rem; font-weight:600; font-size:.92rem;
        }
        .cl-flash-err {
            padding:.85rem 1.1rem; border-radius:12px;
            background:#fef2f2; border:1px solid #fecaca; color:#991b1b; margin-bottom:1rem;
        }
        .cl-flash-err ul { margin:.25rem 0 0; padding-left:1.15rem; }
        .cl-section-title {
            margin:1.35rem 0 1rem; font-size:.78rem; font-weight:700; text-transform:uppercase;
            letter-spacing:.06em; color:#7A2E1F; padding-bottom:.45rem; border-bottom:1px solid #f1f5f9;
        }
        .cl-actions {
            margin-top:1.5rem; padding-top:1.25rem; border-top:1px solid #f1f5f9;
            display:flex; flex-wrap:wrap; align-items:center; gap:.65rem;
        }
        .cl-btn-submit {
            padding:.78rem 1.35rem; border-radius:12px;
            border:1px solid #F3C96A;
            background:linear-gradient(180deg,#f3d073 0%,#E8B34B 100%);
            color:#461c14; font-weight:700; font-size:.95rem;
            cursor:pointer; box-shadow:0 4px 14px rgba(122,46,31,.12);
        }
        .cl-btn-submit:hover { filter:brightness(1.02); transform:translateY(-1px); }
        .cl-danger { border-color:#fecaca; }
        .cl-btn-delete {
            padding:.55rem 1rem; border-radius:10px;
            border:1px solid #fecaca; background:#fef2f2; color:#991b1b;
            font-weight:600; font-size:.87rem; cursor:pointer;
        }
        .cl-btn-delete:hover { background:#fee2e2; }
    </style>
@endsection
