@extends('layouts.sidebar')

@section('content')
<style>
    .qr-wrap { 
        padding: 1rem; 
    }

    .qr-title { 
        font-size: 1.8rem; 
        font-weight: 800; 
        margin-bottom: 1rem; 
        color: #7A2E1F; 
    }

    .qr-notice { 
        padding: .8rem 1rem; 
        border-radius: 10px; 
        margin-bottom: 1rem; 
        font-weight: 500; 
    }

    .qr-notice.ok { 
        background: #ecfdf5; 
        color: #065f46; 
        border: 1px solid #a7f3d0; 
    }

    .qr-notice.err { 
        background: #fef2f2; 
        color: #991b1b; 
        border: 1px solid #fecaca; 
    }

    .qr-card {
        background: #fff;
        border: 1px solid #eceff3;
        border-radius: 14px;
        padding: 1rem;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
    }
    
    .qr-card-title { 
        font-size: 1.15rem; 
        font-weight: 700; 
        margin-bottom: .2rem; 
        color: #111827; 
    }
    
    .qr-card-sub { 
        color: #6b7280; 
        margin-bottom: .9rem; 
        font-size: .9rem; 
    }

    .qr-label { 
        display: block; 
        font-weight: 600; 
        margin-bottom: .35rem; 
        color: #374151; 
    }   

    .qr-input {
        width: 100%;
        padding: .58rem .65rem;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #fff;
    }
    .qr-input:focus {
        outline: none;
        border-color: #E8B34B;
        box-shadow: 0 0 0 3px rgba(232, 179, 75, 0.25);
    }
    .qr-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .35rem;
        border-radius: 9px;
        padding: .55rem .95rem;
        font-weight: 700;
        cursor: pointer;
        transition: all .15s ease;
        text-decoration: none;
    }
    
    .qr-btn-primary {
        background: #E8B34B;
        color: #7A2E1F;
        border: 1px solid #F3C96A;
    }
    
    .qr-btn-primary:hover { 
        background: #F3C96A; 
        transform: translateY(-1px); 
    }

    .qr-table-wrap { 
        overflow-x: auto; 
        margin-top: 1rem; 
    }

    .qr-table { 
        width: 100%; 
        border-collapse: separate; 
        border-spacing: 0; 
        min-width: 680px; 
    }

    .qr-table thead th {
        text-align: left;
        padding: .78rem;
        font-weight: 700;
        background: #fff7ed;
        color: #7A2E1F;
        border-bottom: 1px solid #f1f5f9;
        position: sticky;
        top: 0;
    }
    
    .qr-table tbody td {
        padding: .8rem .78rem;
        border-bottom: 1px solid #eef2f7;
        vertical-align: middle;
    }
    
    .qr-table tbody tr:hover { 
        background: #fcfcfd; 
    }
    
    .qr-code { 
        font-weight: 700; 
        color: #111827; 
    }
    
    .qr-muted { 
        color: #6b7280; 
    }
    
    .qr-actions { 
        display: inline-flex; 
        gap: .4rem; 
        flex-wrap: wrap; 
        justify-content: center; 
    }
    
    .qr-action {
        font-size: .8rem;
        padding: .32rem .55rem;
        border-radius: 999px;
        text-decoration: none;
        font-weight: 700;
        border: 1px solid transparent;
        background: #f8fafc;
    }
    
    .qr-action.download { 
        color: #0f766e; 
        border-color: #99f6e4; 
        background: #f0fdfa; 
    }

    .qr-action.download:hover { 
        background: #ccfbf1; 
        border-color: #5eead4; 
    }
    
    .qr-action.open { 
        color: #1d4ed8; 
        border-color: #bfdbfe; 
        background: #eff6ff; 
    }
    
    .qr-action.open:hover { 
        background: #dbeafe; 
        border-color: #93c5fd; 
    }
    
    .qr-action.delete { 
        color: #b91c1c; 
        border-color: #fecaca; 
        background: #fef2f2; 
        cursor: pointer; 
    }

    @media (max-width: 720px) {
        .qr-wrap { padding: .65rem; }
    }
</style>
<div class="qr-wrap">
    <h1 class="qr-title">QR Codes Manager</h1>

    {{-- Success / Error flash --}}
    @if(session('success'))
        <div class="qr-notice ok">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="qr-notice err">
            {{ implode(', ', $errors->all()) }}
        </div>
    @endif

    {{-- Create new QR mapping --}}
    <div class="qr-card" style="margin-bottom:1.1rem;">
        <h2 class="qr-card-title">Create New QR</h2>
        <p class="qr-card-sub">Generate a QR code and map it to a landmark.</p>
        <form method="POST" action="{{ route('curators.qr.store') }}">
            @csrf
            <div style="display:flex; flex-wrap:wrap; gap:1rem;">
                <div style="flex:1; min-width:200px;">
                    <label for="code" class="qr-label">QR Code Text</label>
                    <input type="text" id="code" name="code" required
                        value="{{ old('code') }}"
                        class="qr-input">
                    <small class="qr-muted">Use a short unique ID (e.g. `L001`)</small>
                </div>

                <div style="flex:1; min-width:200px;">
                    <label for="landmark_id" class="qr-label">Landmark</label>
                    <select id="landmark_id" name="landmark_id" required
                        class="qr-input">
                        <option value="">-- Select Landmark --</option>
                        @foreach($landmarks as $lm)
                            <option value="{{ $lm['id'] }}" @selected(old('landmark_id')==$lm['id'])>
                                {{ $lm['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="flex:0 0 120px;">
                    <label for="format" class="qr-label">Format</label>
                    <select id="format" name="format"
                        class="qr-input">
                        <option value="png" @selected(old('format', 'png')=='png')>PNG</option>
                        <option value="svg" @selected(old('format', 'png')=='svg')>SVG</option>
                    </select>
                </div>
            </div>
            <div style="margin-top:1rem;">
                <button type="submit" class="qr-btn qr-btn-primary">+ Create QR</button>
            </div>
        </form>
    </div>

    {{-- Existing QR mappings --}}
    <div class="qr-card">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:.5rem; flex-wrap:wrap;">
            <h2 class="qr-card-title" style="margin:0;">Existing QR Mappings</h2>
            <span class="qr-muted" style="font-size:.9rem;">{{ count($qrs) }} item{{ count($qrs) !== 1 ? 's' : '' }}</span>
        </div>
        <div class="qr-table-wrap">
        <table class="qr-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Landmark ID</th>
                    <th>Created</th>
                    <th style="text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($qrs as $qr)
                    <tr>
                        <td class="qr-code">{{ e($qr['code']) }}</td>
                        <td>{{ e($qr['landmark_id']) }}</td>
                        <td class="qr-muted">
                            @if($qr['created_at'] instanceof \Google\Cloud\Core\Timestamp)
                                {{ \Carbon\Carbon::instance($qr['created_at']->get())->diffForHumans() }}
                            @else
                                —
                            @endif
                        </td>
                        <td style="text-align:center; white-space:nowrap;">
                            <div class="qr-actions">
                            <a href="{{ $qr['download_url'] }}"
                               class="qr-action download">Download</a>
                            <a href="{{ $qr['resolve_url'] }}"
                               class="qr-action open js-open-qr"
                               data-open-url="{{ $qr['resolve_url'] }}">Open</a>
                            <form method="POST" action="{{ route('curators.qr.destroy', $qr['id']) }}"
                                  style="display:inline;" class="js-delete-qr-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="qr-action delete"
                                    data-code="{{ e($qr['code']) }}">
                                    Delete
                                </button>
                            </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="padding:1rem; text-align:center; color:#6b7280;">
                            No QR mappings yet. Create your first QR code above.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>

<div id="qr-preview-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:9999; align-items:center; justify-content:center; padding:1rem;">
    <div style="background:#fff; width:min(860px, 96vw); border-radius:12px; overflow:hidden; box-shadow:0 20px 50px rgba(0,0,0,.3); border:1px solid #e5e7eb;">
        <div style="display:flex; justify-content:space-between; align-items:center; padding:.75rem 1rem; border-bottom:1px solid #e5e7eb;">
            <h3 style="margin:0; font-size:1rem; font-weight:700; color:#7A2E1F;">QR Preview</h3>
            <button id="qr-preview-close" type="button" style="background:none; border:none; font-size:1.25rem; cursor:pointer; line-height:1;">&times;</button>
        </div>
        <div style="padding:1rem; text-align:center;">
            <img id="qr-preview-image" src="" alt="QR preview" style="width:min(560px, 85vw); max-width:100%; max-height:70vh; object-fit:contain; border:1px solid #e5e7eb; border-radius:6px;" />
        </div>
    </div>
</div>

<div id="qr-delete-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:10000; align-items:center; justify-content:center; padding:1rem;">
    <div style="background:#fff; width:min(480px, 96vw); border-radius:12px; overflow:hidden; box-shadow:0 20px 50px rgba(0,0,0,.3); border:1px solid #e5e7eb;">
        <div style="padding:.95rem 1rem; border-bottom:1px solid #e5e7eb;">
            <h3 style="margin:0; font-size:1.05rem; font-weight:700; color:#111827;">Delete QR Code Mapping</h3>
        </div>
        <div style="padding:1rem;">
            <p id="qr-delete-message" style="margin:0; color:#374151; line-height:1.5;"></p>
        </div>
        <div style="display:flex; justify-content:flex-end; gap:.6rem; padding:0 1rem 1rem;">
            <button id="qr-delete-cancel" type="button" class="qr-btn" style="background:#f8fafc; color:#374151; border:1px solid #d1d5db;">
                ❌ Cancel
            </button>
            <button id="qr-delete-confirm" type="button" class="qr-btn" style="background:#b91c1c; color:#fff; border:1px solid #991b1b;">
                🗑️ Delete
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('qr-preview-modal');
    const image = document.getElementById('qr-preview-image');
    const closeBtn = document.getElementById('qr-preview-close');
    const deleteModal = document.getElementById('qr-delete-modal');
    const deleteMessage = document.getElementById('qr-delete-message');
    const deleteCancelBtn = document.getElementById('qr-delete-cancel');
    const deleteConfirmBtn = document.getElementById('qr-delete-confirm');

    if (!modal || !image || !closeBtn || !deleteModal || !deleteMessage || !deleteCancelBtn || !deleteConfirmBtn) return;

    let pendingDeleteForm = null;

    document.querySelectorAll('.js-open-qr').forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            const url = el.getAttribute('data-open-url');
            if (!url) return;
            image.src = url;
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        });
    });

    closeBtn.addEventListener('click', function () {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        image.src = '';
    });

    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
            image.src = '';
        }
    });

    function closeDeleteModal() {
        deleteModal.style.display = 'none';
        document.body.style.overflow = '';
        pendingDeleteForm = null;
    }

    document.querySelectorAll('.js-delete-qr-form .qr-action.delete').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            pendingDeleteForm = btn.closest('form');
            const code = btn.getAttribute('data-code') || '';
            deleteMessage.textContent = `Are you sure you want to delete this "${code}"`;
            deleteModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        });
    });

    deleteCancelBtn.addEventListener('click', closeDeleteModal);

    deleteConfirmBtn.addEventListener('click', function () {
        if (pendingDeleteForm) {
            pendingDeleteForm.submit();
        }
    });

    deleteModal.addEventListener('click', function (e) {
        if (e.target === deleteModal) {
            closeDeleteModal();
        }
    });

    window.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.style.display === 'flex') {
            modal.style.display = 'none';
            document.body.style.overflow = '';
            image.src = '';
        } else if (e.key === 'Escape' && deleteModal.style.display === 'flex') {
            closeDeleteModal();
        }
    });
});
</script>
@endsection
