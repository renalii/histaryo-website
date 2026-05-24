@extends('layouts.sidebar')

@php
    $panelRoutePrefix = $panelRoutePrefix ?? (session('role') === 'site_manager' ? 'sitemanager' : 'admin');
    $canApproveLandmark = $canApproveLandmark ?? false;
@endphp

@section('content')
    <style>
        .lm-detail-page {
            max-width: 980px;
            margin: 0 auto;
        }
        .lm-detail__back {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            margin-bottom: 1.25rem;
            font-weight: 600;
            font-size: .875rem;
            color: #7A2E1F;
            text-decoration: none;
            padding: .45rem .65rem;
            margin-left: -.65rem;
            border-radius: 8px;
            transition: background .15s ease;
        }
        .lm-detail__back:hover { background: rgba(122, 46, 31, 0.08); text-decoration: none; }
        .lm-detail__back:focus-visible { outline: 2px solid #E8B34B; outline-offset: 2px; }
        .lm-flash-ok, .lm-flash-err {
            margin-bottom: 1rem;
            padding: .75rem 1rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: .92rem;
        }
        .lm-flash-ok { background: #ecfdf5; color: #166534; border: 1px solid #bbf7d0; }
        .lm-flash-err { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .lm-approval-actions {
            margin-top: 1.25rem;
            display: flex;
            flex-wrap: wrap;
            gap: .65rem;
        }
        .lm-btn-approve {
            padding: .6rem 1.1rem;
            border-radius: 10px;
            border: 1px solid #bbf7d0;
            background: #ecfdf5;
            color: #166534;
            font-weight: 700;
            cursor: pointer;
        }
        .lm-btn-reject {
            padding: .6rem 1.1rem;
            border-radius: 10px;
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #991b1b;
            font-weight: 700;
            cursor: pointer;
        }
        .lm-confirm-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 10000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(15, 23, 42, 0.45);
        }
        .lm-confirm-overlay.is-open { display: flex; }
        .lm-confirm-dialog {
            width: min(440px, 96vw);
            background: #fff;
            border-radius: 14px;
            border: 1px solid rgba(122, 46, 31, 0.1);
            box-shadow: 0 20px 50px rgba(122, 46, 31, 0.18);
            overflow: hidden;
        }
        .lm-confirm-dialog__header {
            padding: 1rem 1.15rem;
            border-bottom: 1px solid #f0eef5;
        }
        .lm-confirm-dialog__title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
            color: #7A2E1F;
        }
        .lm-confirm-dialog__body {
            margin: 0;
            padding: 1rem 1.15rem 1.15rem;
            color: #44403c;
            line-height: 1.55;
            font-size: .95rem;
        }
        .lm-confirm-dialog__actions {
            display: flex;
            justify-content: flex-end;
            gap: .6rem;
            padding: 0 1.15rem 1.15rem;
        }
        .lm-confirm-btn {
            padding: .55rem 1rem;
            border-radius: 10px;
            font-weight: 700;
            font-size: .875rem;
            cursor: pointer;
            border: 1px solid transparent;
        }
        .lm-confirm-btn--cancel {
            background: #f8fafc;
            color: #374151;
            border-color: #d1d5db;
        }
        .lm-confirm-btn--cancel:hover { background: #f1f5f9; }
        .lm-confirm-btn--approve {
            background: #ecfdf5;
            color: #166534;
            border-color: #bbf7d0;
        }
        .lm-confirm-btn--approve:hover { background: #d1fae5; }
    </style>

    <div class="lm-detail-page">
        <a href="{{ route($panelRoutePrefix . '.landmarks') }}" class="lm-detail__back">← Back to landmarks</a>

        @if (session('status'))
            <p class="lm-flash-ok" role="status">{{ session('status') }}</p>
        @endif
        @if (session('status_err'))
            <p class="lm-flash-err" role="alert">{{ session('status_err') }}</p>
        @endif

        @include('partials.landmark-detail-card', [
            'landmarkId' => $landmarkId,
            'data' => $data,
            'canApproveLandmark' => $canApproveLandmark,
            'showApprovalActions' => false,
            'showPendingNote' => $panelRoutePrefix === 'sitemanager',
        ])

        @if ($canApproveLandmark)
            <div class="lm-approval-actions" aria-label="Landmark approval">
                <form id="lm-approve-form" method="POST" action="{{ route('admin.landmarks.approve', $landmarkId) }}">
                    @csrf
                    <button type="button" id="lm-approve-open" class="lm-btn-approve">Approve landmark</button>
                </form>
                <form method="POST" action="{{ route('admin.landmarks.reject', $landmarkId) }}" onsubmit="return confirm('Reject this landmark submission? The Site Manager will need to submit again.');">
                    @csrf
                    <button type="submit" class="lm-btn-reject">Reject</button>
                </form>
            </div>
        @endif
    </div>

    @if ($canApproveLandmark)
        <div id="lm-approve-modal" class="lm-confirm-overlay" role="dialog" aria-modal="true" aria-labelledby="lm-approve-modal-title" aria-hidden="true">
            <div class="lm-confirm-dialog">
                <div class="lm-confirm-dialog__header">
                    <h2 id="lm-approve-modal-title" class="lm-confirm-dialog__title">Approve Landmark</h2>
                </div>
                <p class="lm-confirm-dialog__body">Are you sure you want to approve and publish this landmark?</p>
                <div class="lm-confirm-dialog__actions">
                    <button type="button" id="lm-approve-cancel" class="lm-confirm-btn lm-confirm-btn--cancel">Cancel</button>
                    <button type="button" id="lm-approve-confirm" class="lm-confirm-btn lm-confirm-btn--approve">Approve</button>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('lm-approve-modal');
            const openBtn = document.getElementById('lm-approve-open');
            const cancelBtn = document.getElementById('lm-approve-cancel');
            const confirmBtn = document.getElementById('lm-approve-confirm');
            const form = document.getElementById('lm-approve-form');

            if (!modal || !openBtn || !cancelBtn || !confirmBtn || !form) return;

            function openModal() {
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                cancelBtn.focus();
            }

            function closeModal() {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }

            openBtn.addEventListener('click', openModal);
            cancelBtn.addEventListener('click', closeModal);

            confirmBtn.addEventListener('click', function () {
                form.submit();
            });

            modal.addEventListener('click', function (e) {
                if (e.target === modal) closeModal();
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && modal.classList.contains('is-open')) {
                    closeModal();
                }
            });
        });
        </script>
    @endif
@endsection
