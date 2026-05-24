@php
    $modalSafe = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) ($landmarkId ?? 'landmark'));
    $d = is_array($data ?? null) ? $data : [];
    $editModalTitle = $d['name'] ?? 'Edit landmark';
@endphp

<div id="editModal_{{ $modalSafe }}"
     class="modal-cl"
     role="dialog"
     aria-modal="true"
     aria-labelledby="editModalTitle_{{ $modalSafe }}"
     aria-hidden="true">
    <div class="modal-content modal-content--editor">
        <button type="button"
                class="close-cl"
                onclick="cuCloseModalCl('editModal_{{ $modalSafe }}')"
                aria-label="Close">&times;</button>
        <header class="modal-cl__head">
            <h3 id="editModalTitle_{{ $modalSafe }}">{{ $editModalTitle }}</h3>
        </header>

        @if ($errors->any())
            <div class="modal-cl__errors" role="alert">
                <p class="modal-cl__errors-title">Please fix the following:</p>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @include('curators.landmarks.partials.edit-form', [
            'landmarkId' => $landmarkId,
            'data' => $d,
            'modalSafe' => $modalSafe,
            'mapboxToken' => $mapboxToken ?? null,
        ])
    </div>
</div>

@include('curators.landmarks.partials.landmark-editor-styles')

@once
    <style>
        .modal-cl {
            display: none;
            position: fixed;
            z-index: 10050;
            inset: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.55);
            padding: 1rem;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
        }
        .modal-cl.is-open { display: flex; }
        .modal-cl .modal-content {
            background: #f8fafc;
            margin: auto;
            padding: 1.25rem 1.35rem 1.35rem;
            border-radius: 16px;
            max-width: 560px;
            width: 100%;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.12);
            position: relative;
            max-height: 92vh;
            overflow-y: auto;
            border: 1px solid #e5e7eb;
        }
        .modal-cl .modal-content--editor {
            max-width: min(920px, 100%);
            padding: 1.35rem 1.5rem 1.5rem;
        }
        .modal-cl__head {
            margin: 0 2rem 1rem 0;
        }
        .modal-cl .modal-content h3 {
            margin: 0;
            font-size: 1.35rem;
            font-weight: 800;
            color: #7A2E1F;
            letter-spacing: -.02em;
        }
        .modal-cl__errors {
            margin: 0 0 1rem;
            padding: .75rem 1rem;
            border-radius: 10px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            font-size: .88rem;
        }
        .modal-cl__errors-title {
            margin: 0 0 .35rem;
            font-weight: 700;
        }
        .modal-cl__errors ul {
            margin: 0;
            padding-left: 1.15rem;
        }
        .modal-cl__form-actions {
            display: flex;
            justify-content: flex-end;
            gap: .6rem;
            flex-wrap: wrap;
            margin-top: 1.25rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }
        .modal-cl__btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .6rem 1.15rem;
            font-size: .9rem;
            border-radius: 10px;
            font-weight: 700;
            border: 1px solid #d1d5db;
            background: #fff;
            color: #374151;
            cursor: pointer;
            font-family: inherit;
        }
        .modal-cl__btn-secondary:hover { background: #f9fafb; }
        .modal-cl__btn-primary,
        .modal-cl .modal-content button[type="submit"] {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(180deg, #f3d073 0%, #E8B34B 100%);
            color: #461c14;
            padding: .6rem 1.25rem;
            font-size: .9rem;
            border-radius: 10px;
            font-weight: 700;
            border: 1px solid #F3C96A;
            cursor: pointer;
            font-family: inherit;
            margin: 0;
            box-shadow: 0 3px 10px rgba(122, 46, 31, 0.1);
        }
        .modal-cl__btn-primary:hover,
        .modal-cl .modal-content button[type="submit"]:hover {
            filter: brightness(1.02);
        }
        .modal-cl .close-cl {
            position: absolute;
            top: 12px;
            right: 16px;
            font-size: 26px;
            font-weight: bold;
            color: #6b7280;
            cursor: pointer;
            line-height: 1;
            background: none;
            border: none;
            padding: 0;
        }
        .modal-cl .close-cl:hover { color: #111827; }
    </style>
    <script>
        function cuOpenModalCl(id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.classList.add('is-open');
            el.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }
        function cuCloseModalCl(id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.classList.remove('is-open');
            el.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.js-lm-edit-open').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var modalId = btn.getAttribute('data-modal-id');
                    if (modalId) cuOpenModalCl(modalId);
                });
            });
            document.querySelectorAll('.modal-cl').forEach(function (modal) {
                modal.addEventListener('click', function (e) {
                    if (e.target === modal) cuCloseModalCl(modal.id);
                });
            });
            document.addEventListener('keydown', function (e) {
                if (e.key !== 'Escape') return;
                document.querySelectorAll('.modal-cl.is-open').forEach(function (modal) {
                    cuCloseModalCl(modal.id);
                });
            });
        });
    </script>
@endonce

@if ($errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            cuOpenModalCl('editModal_{{ $modalSafe }}');
        });
    </script>
@endif
