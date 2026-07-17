@php
    use App\Support\LandmarkActivation;
    use App\Support\LandmarkEvidence;

    $activationStatus = strtolower((string) ($data['activation_status'] ?? 'active'));
    $evidenceDocuments = is_array($data['evidence_documents'] ?? null) ? $data['evidence_documents'] : [];
    $latRaw = $data['latitude'] ?? $data['lati'] ?? null;
    $lngRaw = $data['longitude'] ?? $data['longti'] ?? null;
    $hasCoords = is_numeric($latRaw) && is_numeric($lngRaw);
    $locationDisplay = trim((string) ($data['location'] ?? ''));
    $categoryDisplay = trim((string) ($data['category'] ?? ''));
    $descriptionDisplay = trim((string) ($data['description'] ?? ''));
    $mapContainerId = 'lm-view-map-' . ($modalSafe ?? 'landmark');
    $isAdminApprovalView = ($panelRoutePrefix ?? '') !== 'sitemanager';
    $activationLabel = $isAdminApprovalView
        ? match ($activationStatus) {
            'pending' => 'Pending',
            'active' => 'Approved',
            'rejected' => 'Rejected',
            default => LandmarkActivation::label($activationStatus),
        }
        : LandmarkActivation::label($activationStatus);
    $isSiteManagerView = ($panelRoutePrefix ?? '') === 'sitemanager';
@endphp

<div id="{{ $viewModalId }}"
     class="lm-view-modal"
     role="dialog"
     aria-modal="true"
     aria-labelledby="viewTitle_{{ $modalSafe }}"
     aria-hidden="true">
    <div class="lm-view-modal__panel" tabindex="-1">
        <div class="lm-view-modal__header">
            <div class="lm-view-modal__heading">
                <p class="lm-view-modal__eyebrow">Landmark detail</p>
                <h2 id="viewTitle_{{ $modalSafe }}" class="lm-view-modal__title">{{ $data['name'] ?? 'Unnamed landmark' }}</h2>
            </div>
            <div class="lm-view-modal__top-actions" aria-label="Landmark actions">
                @if ($isSiteManagerView)
                    <button type="button"
                            class="lm-view-modal__action lm-view-modal__action--edit"
                            onclick="lmOpenEditModal('lmEditLandmarkModal_{{ $modalSafe }}', '{{ $viewModalId }}')">
                        Edit
                    </button>
                    <form method="POST"
                          action="{{ route('sitemanager.landmarks.destroy', $landmarkId) }}"
                          id="land-delete-form-{{ $modalSafe }}"
                          class="land-delete-form">
                        @csrf
                        @method('DELETE')
                        <button type="button"
                                class="lm-view-modal__action lm-view-modal__action--delete"
                                data-landmark-delete
                                data-delete-form-id="land-delete-form-{{ $modalSafe }}"
                                data-landmark-name="{{ $data['name'] ?? 'this landmark' }}">
                            Delete
                        </button>
                    </form>
                @endif
                <button type="button"
                        class="lm-view-modal__close"
                        onclick="smCloseLandmarkViewModal('{{ $viewModalId }}')"
                        aria-label="Close">&times;</button>
            </div>
        </div>

        <div class="lm-view-modal__body">
            <div class="lm-view-modal__chips" aria-label="Landmark metadata">
            <span class="lm-view-chip">
                <span class="lm-view-chip__k">Location</span>
                <span class="lm-view-chip__v">{{ $locationDisplay !== '' ? $locationDisplay : 'N/A' }}</span>
            </span>
            <span class="lm-view-chip">
                <span class="lm-view-chip__k">Category</span>
                <span class="lm-view-chip__v">{{ $categoryDisplay !== '' ? $categoryDisplay : 'N/A' }}</span>
            </span>
            <span class="lm-view-status lm-view-status--{{ $activationStatus === 'pending' || $activationStatus === 'rejected' ? $activationStatus : 'active' }}">
                {{ $activationLabel }}
            </span>
        </div>

        <h3 class="lm-view-modal__section">Full description</h3>
        <p class="lm-view-modal__desc">{{ $descriptionDisplay !== '' ? $descriptionDisplay : 'No description available.' }}</p>

        @if ($hasCoords)
            <h3 class="lm-view-modal__section">Location</h3>
            @include('partials.landmark-map-embed', [
                'latitude' => $latRaw,
                'longitude' => $lngRaw,
                'mapContainerId' => $mapContainerId,
                'landmarkName' => $data['name'] ?? 'Landmark',
                'mapboxToken' => $mapboxToken ?? null,
            ])
        @endif

        <h3 class="lm-view-modal__section">Landmark photo</h3>
        @if ($imageSrc)
            <div class="lm-view-media-grid">
                <figure class="lm-view-media-frame">
                    <img src="{{ $imageSrc }}" alt="Photo of {{ $data['name'] ?? 'landmark' }}" loading="lazy" decoding="async">
                    <figcaption class="lm-view-media-frame__cap">Featured image</figcaption>
                </figure>
            </div>
        @else
            <p class="lm-view-modal__muted">No photo available.</p>
        @endif

        <h3 class="lm-view-modal__section">Evidence &amp; supporting documents</h3>
        @if ($evidenceDocuments === [])
            <p class="lm-view-modal__muted">No evidence files were uploaded.</p>
        @else
            <ul class="lm-view-evidence-list">
                @foreach ($evidenceDocuments as $doc)
                    @php
                        $filename = (string) ($doc['filename'] ?? 'document');
                        $href = LandmarkEvidence::documentHref($doc);
                    @endphp
                    <li class="lm-view-evidence-item">
                        <span>{{ $filename }}</span>
                        @if ($href)
                            <a href="{{ $href }}" download="{{ $filename }}" target="_blank" rel="noopener noreferrer">View / download</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($canApproveLandmark ?? false)
            <div class="lm-view-approval-actions" aria-label="Landmark approval" onclick="event.stopPropagation()">
                <form id="lm-approve-form-{{ $modalSafe }}" method="POST" action="{{ route('admin.landmarks.approve', $landmarkId) }}">
                    @csrf
                    <button type="button" class="lm-view-btn-approve" onclick="lmOpenApproveModal('lm-approve-modal-{{ $modalSafe }}')">Approve landmark</button>
                </form>
                <form id="lm-reject-form-{{ $modalSafe }}" method="POST" action="{{ route('admin.landmarks.reject', $landmarkId) }}">
                    @csrf
                    <button type="button" class="lm-view-btn-reject" onclick="lmOpenRejectModal('lm-reject-modal-{{ $modalSafe }}')">Reject</button>
                </form>
            </div>

            <style>
                .lm-approve-confirm-overlay {
                    display: none;
                    position: fixed;
                    inset: 0;
                    z-index: 1200;
                    background: rgba(15, 23, 42, 0.55);
                    align-items: center;
                    justify-content: center;
                    padding: 1.25rem;
                }
                .lm-approve-confirm-overlay.is-open { display: flex; }
                .lm-reject-confirm-overlay {
                    display: none;
                    position: fixed;
                    inset: 0;
                    z-index: 1200;
                    background: rgba(15, 23, 42, 0.55);
                    align-items: center;
                    justify-content: center;
                    padding: 1.25rem;
                }
                .lm-reject-confirm-overlay.is-open { display: flex; }
                .lm-approve-confirm-dialog {
                    width: min(480px, 100%);
                    background: #fff;
                    border-radius: 16px;
                    border: 1px solid rgba(122, 46, 31, 0.12);
                    box-shadow: 0 24px 50px rgba(15, 23, 42, 0.16);
                    overflow: hidden;
                }
                .lm-reject-confirm-dialog {
                    width: min(480px, 100%);
                    background: #fff;
                    border-radius: 16px;
                    border: 1px solid rgba(122, 46, 31, 0.12);
                    box-shadow: 0 24px 50px rgba(15, 23, 42, 0.16);
                    overflow: hidden;
                }
                .lm-approve-confirm-dialog__header {
                    padding: 1.2rem 1.3rem 0.9rem;
                    border-bottom: 1px solid #f3ede0;
                }
                .lm-reject-confirm-dialog__header {
                    padding: 1.2rem 1.3rem 0.9rem;
                    border-bottom: 1px solid #f3ede0;
                }
                .lm-approve-confirm-dialog__title {
                    margin: 0;
                    font-size: 1.25rem;
                    font-weight: 800;
                    color: #7A2E1F;
                }
                .lm-reject-confirm-dialog__title {
                    margin: 0;
                    font-size: 1.25rem;
                    font-weight: 800;
                    color: #7A2E1F;
                }
                .lm-approve-confirm-dialog__body {
                    margin: 0;
                    padding: 1.1rem 1.3rem 1.3rem;
                    font-size: .96rem;
                    line-height: 1.65;
                    color: #42403d;
                }
                .lm-reject-confirm-dialog__body {
                    margin: 0;
                    padding: 1.1rem 1.3rem 1.3rem;
                    font-size: .96rem;
                    line-height: 1.65;
                    color: #42403d;
                }
                .lm-approve-confirm-dialog__actions,
                .lm-reject-confirm-dialog__actions {
                    display: flex;
                    align-items: center;
                    justify-content: flex-end;
                    gap: 10px;
                    padding: 0 1.3rem 1.3rem;
                }
                .lm-approve-confirm-btn,
                .lm-reject-confirm-btn {
                    box-sizing: border-box;
                    height: 38px;
                    padding: 0 18px;
                    border-radius: 8px;
                    font-weight: 600;
                    font-size: 14px;
                    cursor: pointer;
                    border: 1px solid transparent;
                }
                .lm-approve-confirm-btn--cancel,
                .lm-reject-confirm-btn--cancel {
                    min-width: 74px;
                    background: #fff;
                    color: #374151;
                    border-color: #d1d5db;
                }
                .lm-approve-confirm-btn--cancel:hover,
                .lm-reject-confirm-btn--cancel:hover {
                    background: #f9fafb;
                    border-color: #9ca3af;
                }
                .lm-approve-confirm-btn--approve {
                    min-width: 86px;
                    background: #166534;
                    color: #fff;
                    border: 0;
                }
                .lm-approve-confirm-btn--approve:hover { background: #14532d; }
                .lm-reject-confirm-btn--reject {
                    min-width: 86px;
                    background: #ef4444;
                    color: #fff;
                    border: 0;
                }
                .lm-reject-confirm-btn--reject:hover { background: #dc2626; }
            </style>

            <div id="lm-approve-modal-{{ $modalSafe }}" class="lm-approve-confirm-overlay" role="dialog" aria-modal="true" aria-labelledby="lm-approve-modal-title-{{ $modalSafe }}" aria-hidden="true">
                <div class="lm-approve-confirm-dialog">
                    <div class="lm-approve-confirm-dialog__header">
                        <h2 id="lm-approve-modal-title-{{ $modalSafe }}" class="lm-approve-confirm-dialog__title">Approve Landmark</h2>
                    </div>
                    <p class="lm-approve-confirm-dialog__body">Are you sure you want to approve and publish "{{ $data['name'] ?? 'Unnamed landmark' }}"?</p>
                    <div class="lm-approve-confirm-dialog__actions">
                        <button type="button" class="lm-approve-confirm-btn lm-approve-confirm-btn--cancel" onclick="lmCloseApproveModal('lm-approve-modal-{{ $modalSafe }}')">Cancel</button>
                        <button type="button" class="lm-approve-confirm-btn lm-approve-confirm-btn--approve" onclick="lmConfirmApprove('lm-approve-form-{{ $modalSafe }}', 'lm-approve-modal-{{ $modalSafe }}')">Approve</button>
                    </div>
                </div>
            </div>

            <div id="lm-reject-modal-{{ $modalSafe }}" class="lm-reject-confirm-overlay" role="dialog" aria-modal="true" aria-labelledby="lm-reject-modal-title-{{ $modalSafe }}" aria-hidden="true">
                <div class="lm-reject-confirm-dialog">
                    <div class="lm-reject-confirm-dialog__header">
                        <h2 id="lm-reject-modal-title-{{ $modalSafe }}" class="lm-reject-confirm-dialog__title">Reject Landmark</h2>
                    </div>
                    <p class="lm-reject-confirm-dialog__body">Are you sure you want to reject the landmark submission for "{{ $data['name'] ?? 'Unnamed landmark' }}"?<br><br>The Site Manager will need to submit it again.</p>
                    <div class="lm-reject-confirm-dialog__actions">
                        <button type="button" class="lm-reject-confirm-btn lm-reject-confirm-btn--cancel" onclick="lmCloseRejectModal('lm-reject-modal-{{ $modalSafe }}')">Cancel</button>
                        <button type="button" class="lm-reject-confirm-btn lm-reject-confirm-btn--reject" onclick="lmConfirmReject('lm-reject-form-{{ $modalSafe }}', 'lm-reject-modal-{{ $modalSafe }}')">Reject</button>
                    </div>
                </div>
            </div>

            <script>
                function lmOpenApproveModal(modalId) {
                    var modal = document.getElementById(modalId);
                    if (modal) {
                        modal.classList.add('is-open');
                        modal.setAttribute('aria-hidden', 'false');
                        document.body.style.overflow = 'hidden';
                    }
                }

                function lmCloseApproveModal(modalId) {
                    var modal = document.getElementById(modalId);
                    if (modal) {
                        modal.classList.remove('is-open');
                        modal.setAttribute('aria-hidden', 'true');
                        document.body.style.overflow = '';
                    }
                }

                function lmConfirmApprove(formId, modalId) {
                    var form = document.getElementById(formId);
                    if (form) {
                        form.submit();
                    }
                }

                function lmOpenRejectModal(modalId) {
                    var modal = document.getElementById(modalId);
                    if (modal) {
                        modal.classList.add('is-open');
                        modal.setAttribute('aria-hidden', 'false');
                        document.body.style.overflow = 'hidden';
                    }
                }

                function lmCloseRejectModal(modalId) {
                    var modal = document.getElementById(modalId);
                    if (modal) {
                        modal.classList.remove('is-open');
                        modal.setAttribute('aria-hidden', 'true');
                        document.body.style.overflow = '';
                    }
                }

                function lmConfirmReject(formId, modalId) {
                    var form = document.getElementById(formId);
                    if (form) {
                        form.submit();
                    }
                }

                document.addEventListener('DOMContentLoaded', function() {
                    var approveModal = document.getElementById('lm-approve-modal-{{ $modalSafe }}');
                    if (approveModal) {
                        approveModal.addEventListener('click', function(e) {
                            if (e.target === approveModal) {
                                lmCloseApproveModal('lm-approve-modal-{{ $modalSafe }}');
                            }
                        });
                    }

                    var rejectModal = document.getElementById('lm-reject-modal-{{ $modalSafe }}');
                    if (rejectModal) {
                        rejectModal.addEventListener('click', function(e) {
                            if (e.target === rejectModal) {
                                lmCloseRejectModal('lm-reject-modal-{{ $modalSafe }}');
                            }
                        });
                    }
                });

                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        var approveModal = document.querySelector('.lm-approve-confirm-overlay.is-open');
                        if (approveModal) {
                            lmCloseApproveModal(approveModal.id);
                            return;
                        }
                        var rejectModal = document.querySelector('.lm-reject-confirm-overlay.is-open');
                        if (rejectModal) {
                            lmCloseRejectModal(rejectModal.id);
                        }
                    }
                });
            </script>
        @endif
        </div>
    </div>
</div>
