@php
    $landmarkName = $landmarkName ?? 'this landmark';
    $modalSafe = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) ($landmarkId ?? 'landmark'));
@endphp

<a href="{{ route('curators.qr') }}"
   class="cu-lm-add-btn"
   data-qr-download-url="{{ route('curators.qr.byLandmark', $landmarkId) }}"
   onclick="cuDownloadQrAndGo(event, this)">Download QR</a>
<button type="button"
        class="cu-lm-add-btn js-lm-edit-open"
        style="background:#e0e7ff;border:1px solid #a5b4fc;"
        data-modal-id="editModal_{{ $modalSafe }}"
        aria-haspopup="dialog">Edit</button>
<button type="button"
        class="cu-lm-add-btn js-lm-delete-open"
        style="background:#fee2e2;border:1px solid #fecaca;color:#991b1b;"
        data-modal-id="lm-delete-modal-{{ $modalSafe }}"
        aria-haspopup="dialog">Delete</button>

<form id="lm-delete-form-{{ $modalSafe }}"
      action="{{ route('landmarks.destroy', $landmarkId) }}"
      method="POST"
      hidden>
    @csrf
    @method('DELETE')
</form>

<div id="lm-delete-modal-{{ $modalSafe }}"
     class="lm-delete-modal"
     role="dialog"
     aria-modal="true"
     aria-labelledby="lm-delete-title-{{ $modalSafe }}"
     aria-hidden="true"
     hidden>
    <div class="lm-delete-modal__panel" tabindex="-1">
        <h2 id="lm-delete-title-{{ $modalSafe }}" class="lm-delete-modal__title">Delete Landmark</h2>
        <p class="lm-delete-modal__message">
            Are you sure you want to delete "{{ $landmarkName }}"?
        </p>
        <div class="lm-delete-modal__actions">
            <button type="button" class="lm-delete-modal__btn lm-delete-modal__btn--cancel js-lm-delete-cancel">Cancel</button>
            <button type="button" class="lm-delete-modal__btn lm-delete-modal__btn--confirm js-lm-delete-confirm">Delete</button>
        </div>
    </div>
</div>

@once
    <style>
        .lm-delete-modal {
            position: fixed;
            inset: 0;
            z-index: 10000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(15, 23, 42, 0.55);
        }
        .lm-delete-modal.is-open {
            display: flex;
        }
        .lm-delete-modal__panel {
            width: min(480px, 96vw);
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            padding: 1.15rem 1.25rem 1.25rem;
        }
        .lm-delete-modal__title {
            margin: 0 0 .75rem;
            font-size: 1.05rem;
            font-weight: 700;
            color: #111827;
        }
        .lm-delete-modal__message {
            margin: 0 0 1.15rem;
            font-size: .95rem;
            line-height: 1.5;
            color: #374151;
        }
        .lm-delete-modal__actions {
            display: flex;
            justify-content: flex-end;
            gap: .6rem;
            flex-wrap: wrap;
        }
        .lm-delete-modal__btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .55rem 1.1rem;
            border-radius: 10px;
            font-weight: 700;
            font-size: .88rem;
            font-family: inherit;
            cursor: pointer;
            border: 1px solid transparent;
        }
        .lm-delete-modal__btn--cancel {
            background: #f8fafc;
            color: #374151;
            border-color: #d1d5db;
        }
        .lm-delete-modal__btn--cancel:hover {
            background: #f1f5f9;
        }
        .lm-delete-modal__btn--confirm {
            background: #b91c1c;
            color: #fff;
            border-color: #991b1b;
        }
        .lm-delete-modal__btn--confirm:hover {
            background: #991b1b;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var openBtns = document.querySelectorAll('.js-lm-delete-open');
            if (!openBtns.length) {
                return;
            }

            function closeModal(modal) {
                if (!modal) {
                    return;
                }
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                modal.hidden = true;
                document.body.style.overflow = '';
            }

            function openModal(modal) {
                if (!modal) {
                    return;
                }
                modal.hidden = false;
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                var panel = modal.querySelector('.lm-delete-modal__panel');
                if (panel) {
                    panel.focus();
                }
            }

            openBtns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var modalId = btn.getAttribute('data-modal-id');
                    var modal = modalId ? document.getElementById(modalId) : null;
                    openModal(modal);
                });
            });

            document.querySelectorAll('.lm-delete-modal').forEach(function (modal) {
                var cancelBtn = modal.querySelector('.js-lm-delete-cancel');
                var confirmBtn = modal.querySelector('.js-lm-delete-confirm');
                var modalId = modal.id;
                var form = modalId ? document.getElementById(modalId.replace('lm-delete-modal-', 'lm-delete-form-')) : null;

                if (cancelBtn) {
                    cancelBtn.addEventListener('click', function () {
                        closeModal(modal);
                    });
                }

                if (confirmBtn && form) {
                    confirmBtn.addEventListener('click', function () {
                        form.submit();
                    });
                }

                modal.addEventListener('click', function (e) {
                    if (e.target === modal) {
                        closeModal(modal);
                    }
                });
            });

            document.addEventListener('keydown', function (e) {
                if (e.key !== 'Escape') {
                    return;
                }
                document.querySelectorAll('.lm-delete-modal.is-open').forEach(function (modal) {
                    closeModal(modal);
                });
            });
        });
    </script>
@endonce
