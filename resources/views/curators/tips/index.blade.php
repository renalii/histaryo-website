@extends('layouts.sidebar')

@section('content')
    <style>
        .tip-review-list {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
            width: 100%;
            align-items: start;
        }

        .tip-review-card {
            overflow: hidden;
            background: #fff;
            border: 1px solid #eee8e4;
            border-radius: 14px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, .06);
        }

        .tip-review-card summary {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto auto;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.25rem;
            cursor: pointer;
            list-style: none;
        }

        .tip-review-card summary::-webkit-details-marker {
            display: none;
        }

        .tip-review-card summary::after {
            margin-left: .25rem;
            color: #9ca3af;
            content: '+';
            font-size: 1.2rem;
            line-height: 1;
        }

        .tip-review-card[open] summary::after {
            content: '−';
        }

        .tip-review-content {
            padding: 0 1.25rem 1.25rem;
            border-top: 1px solid #f1eeec;
        }

        .tip-review-copy {
            margin: 1rem 0;
            color: #374151;
            line-height: 1.55;
            white-space: pre-line;
        }

        .tip-review-meta {
            display: flex;
            flex-direction: column;
            gap: .55rem;
            margin: 0 0 1rem;
            color: #6b7280;
            font-size: .88rem;
        }

        .tip-review-meta strong,
        .tip-review-note strong {
            color: #374151;
        }

        .tip-review-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: .6rem;
            padding-top: 1rem;
            border-top: 1px solid #f1eeec;
        }

        .tip-review-note-input {
            flex: 1 1 auto;
            min-width: 0;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: .52rem .7rem;
        }

        @media (max-width: 640px) {
            .tip-review-list {
                grid-template-columns: 1fr;
            }

            .tip-review-card summary,
            .tip-review-content {
                padding-left: 1rem;
                padding-right: 1rem;
            }

            .tip-review-actions {
                flex-wrap: wrap;
            }

            .tip-review-note-input {
                flex-basis: 100%;
            }
        }

        @media (min-width: 641px) and (max-width: 1050px) {
            .tip-review-list {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>

    <div style="display:flex; align-items:center; margin-bottom:1.25rem;">
        <h2 style="margin:0; font-size:1.5rem; color:#7A2E1F;">Review Tip</h2>
    </div>

    <form method="GET" action="{{ route('curators.tips.index') }}" style="display:flex; align-items:center; gap:.55rem; flex-wrap:wrap; margin-bottom:1rem;">
        <label for="status-filter" style="font-weight:600; color:#374151;">Status:</label>
        <select id="status-filter" name="status" onchange="this.form.submit()"
            style="border:1px solid #d1d5db; border-radius:8px; padding:.48rem .62rem; color:#111827; background:#fff;">
            <option value="pending" @selected(($statusFilter ?? 'all') === 'pending')>Pending</option>
            <option value="all" @selected(($statusFilter ?? 'all') === 'all')>All</option>
            <option value="accepted" @selected(($statusFilter ?? 'all') === 'accepted')>Accepted</option>
            <option value="rejected" @selected(($statusFilter ?? 'all') === 'rejected')>Rejected</option>
        </select>
        <noscript>
            <button type="submit"
                style="border:1px solid #d1d5db; border-radius:8px; padding:.48rem .75rem; background:#f9fafb; color:#374151; cursor:pointer;">
                Apply
            </button>
        </noscript>
    </form>

    @if (session('success'))
        <div style="background:#d1fae5; color:#065f46; padding:.9rem 1rem; border-radius:10px; margin-bottom:1rem;">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div style="background:#fee2e2; color:#991b1b; padding:.9rem 1rem; border-radius:10px; margin-bottom:1rem;">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div style="background:#fff7ed; color:#9a3412; padding:.9rem 1rem; border-radius:10px; margin-bottom:1rem;">
            {{ $errors->first() }}
        </div>
    @endif

    @if ($tips->isEmpty())
        <div style="background:#fff; border:1px dashed #d1d5db; border-radius:12px; padding:1.2rem; color:#6b7280;">
            @if (($statusFilter ?? 'all') === 'pending')
                No pending tips right now.
            @elseif (($statusFilter ?? 'all') === 'all')
                No submitted tips yet.
            @else
                No {{ $statusFilter }} tips found.
            @endif
        </div>
    @else
        <div class="tip-review-list">
            @foreach ($tips as $tip)
                @php
                    $status = $tip['status'] ?? 'pending';
                    $statusLabel = ucfirst($status);
                    $badgeBg = $status === 'accepted' ? '#dcfce7' : ($status === 'rejected' ? '#fee2e2' : '#fef3c7');
                    $badgeColor = $status === 'accepted' ? '#166534' : ($status === 'rejected' ? '#991b1b' : '#92400e');
                @endphp

                <details class="tip-review-card" {{ $loop->first ? 'open' : '' }}>
                    <summary>
                        <div style="font-weight:700; color:#111827; font-size:1.02rem;">
                            {{ $tip['landmark_name'] !== '' ? $tip['landmark_name'] : 'General Tip' }}
                        </div>
                        <span style="background:{{ $badgeBg }}; color:{{ $badgeColor }}; flex:0 0 auto; font-size:.82rem; font-weight:700; padding:.28rem .62rem; border-radius:999px;">
                            {{ $statusLabel }}
                        </span>
                    </summary>

                    <div class="tip-review-content">

                        <p class="tip-review-copy">
                            {{ $tip['content'] !== '' ? $tip['content'] : 'No tip content provided.' }}
                        </p>

                        @if (($tip['title'] ?? '') !== '' || ($tip['type'] ?? '') !== '')
                            <div style="display:flex; gap:.5rem; flex-wrap:wrap; margin:0 0 1rem;">
                                <span style="color:#6b7280; font-size:.86rem; font-weight:600; align-self:center;">Landmark tag:</span>
                                @if (($tip['title'] ?? '') !== '')
                                    <span style="background:#f3f4f6; color:#374151; font-size:.8rem; font-weight:600; border-radius:999px; padding:.25rem .55rem;">
                                        {{ $tip['title'] }}
                                    </span>
                                @endif
                                @if (($tip['type'] ?? '') !== '')
                                    <span style="background:#ede9fe; color:#5b21b6; font-size:.8rem; font-weight:600; border-radius:999px; padding:.25rem .55rem;">
                                        {{ ucfirst($tip['type']) }}
                                    </span>
                                @endif
                            </div>
                        @endif

                        <div class="tip-review-meta">
                            <div><strong>Submitted by:</strong> {{ $tip['submitted_by'] }}</div>
                            <div><strong>Submitted at:</strong> {{ $tip['created_at'] }}</div>
                            <div><strong>Reviewer email:</strong> {{ $tip['submitted_email'] !== '' ? $tip['submitted_email'] : '-' }}</div>
                            <div><strong>Reviewed at:</strong> {{ $tip['reviewed_at'] }}</div>
                        </div>

                        @if ($tip['reviewed_by'] !== '')
                            <p class="tip-review-note" style="margin:0 0 .65rem; color:#4b5563; font-size:.86rem;">
                                Reviewed by <strong>{{ $tip['reviewed_by'] }}</strong>
                            </p>
                        @endif

                        @if ($tip['review_note'] !== '')
                            <p class="tip-review-note" style="margin:0 0 .8rem; color:#4b5563; font-size:.9rem;">
                                <strong>Review note:</strong> {{ $tip['review_note'] }}
                            </p>
                        @endif

                        @if ($status === 'pending')
                            <form method="POST" action="{{ route('curators.tips.review', ['tipId' => $tip['id']]) }}" class="tip-review-actions">
                                @csrf
                                <input type="hidden" name="source_collection" value="{{ $tip['source_collection'] ?? 'crowdsourced_tips' }}">
                                <input type="hidden" name="page" value="{{ $tips->currentPage() }}">
                                <input type="hidden" name="status_filter" value="{{ $statusFilter ?? 'all' }}">
                                <input class="tip-review-note-input" type="text" name="review_note" placeholder="Optional review note">

                                <button type="submit" name="decision" value="accepted"
                                    style="border:1px solid #86efac; background:#dcfce7; color:#166534; font-weight:700; border-radius:8px; padding:.52rem .9rem; cursor:pointer;">
                                    Accept
                                </button>

                                <button type="submit" name="decision" value="rejected"
                                    style="border:1px solid #fca5a5; background:#fee2e2; color:#991b1b; font-weight:700; border-radius:8px; padding:.52rem .9rem; cursor:pointer;">
                                    Reject
                                </button>
                            </form>
                        @endif
                    </div>
                </details>
            @endforeach
        </div>

        @if ($tips->hasPages())
            <div style="margin-top:1rem; display:flex; align-items:center; justify-content:space-between; gap:.75rem; flex-wrap:wrap;">
                <div style="font-size:.86rem; color:#6b7280;">
                    Showing {{ $tips->firstItem() }}-{{ $tips->lastItem() }} of {{ $tips->total() }} tips
                </div>
                <div style="display:flex; gap:.4rem; align-items:center;">
                    @if ($tips->onFirstPage())
                        <span style="padding:.45rem .7rem; border:1px solid #e5e7eb; border-radius:8px; color:#9ca3af;">Prev</span>
                    @else
                        <a href="{{ $tips->previousPageUrl() }}" style="padding:.45rem .7rem; border:1px solid #d1d5db; border-radius:8px; color:#374151; text-decoration:none;">Prev</a>
                    @endif

                    <span style="padding:.45rem .7rem; border:1px solid #d1d5db; border-radius:8px; color:#374151;">
                        Page {{ $tips->currentPage() }} of {{ $tips->lastPage() }}
                    </span>

                    @if ($tips->hasMorePages())
                        <a href="{{ $tips->nextPageUrl() }}" style="padding:.45rem .7rem; border:1px solid #d1d5db; border-radius:8px; color:#374151; text-decoration:none;">Next</a>
                    @else
                        <span style="padding:.45rem .7rem; border:1px solid #e5e7eb; border-radius:8px; color:#9ca3af;">Next</span>
                    @endif
                </div>
            </div>
        @endif
    @endif
@endsection
