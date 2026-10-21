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

        .tip-review-card .tip-review-summary {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto auto;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.25rem;
            cursor: default;
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

        .tip-review-note-input:focus,
        .tip-review-note-input:focus-visible {
            outline: none;
            border-color: #d1d5db;
            box-shadow: 0 0 0 2px rgba(232, 179, 75, .2);
        }

        .tip-status-dropdown {
            position: relative;
            display: block;
            width: 120px;
            height: 38px;
        }

        .tip-status-dropdown__native {
            position: absolute;
            inset: 0;
            opacity: 0;
            pointer-events: none;
        }

        .tip-status-dropdown__toggle {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
            height: 100%;
            padding: 0 2rem 0 .7rem;
            overflow: hidden;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
            color: #111827;
            font: inherit;
            text-align: left;
            cursor: pointer;
        }

        .tip-status-dropdown__toggle::after {
            position: absolute;
            top: 50%;
            right: .72rem;
            width: 7px;
            height: 7px;
            border-right: 1.5px solid #6b7280;
            border-bottom: 1.5px solid #6b7280;
            content: '';
            transform: translateY(-65%) rotate(45deg);
            transition: transform .15s ease;
        }

        .tip-status-dropdown.is-open .tip-status-dropdown__toggle::after {
            transform: translateY(-25%) rotate(225deg);
        }

        .tip-status-dropdown__menu {
            position: absolute;
            z-index: 20;
            top: calc(100% + 4px);
            left: 0;
            width: 100%;
            margin: 0;
            padding: .3rem 0;
            overflow: auto;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 8px 18px rgba(15, 23, 42, .12);
            list-style: none;
        }

        .tip-status-dropdown__menu[hidden] { display: none; }
        .tip-status-dropdown__option {
            display: block;
            width: 100%;
            padding: .55rem .7rem;
            border: 0;
            background: transparent;
            color: #374151;
            font: inherit;
            text-align: left;
            cursor: pointer;
        }
        .tip-status-dropdown__option:hover,
        .tip-status-dropdown__option[aria-selected="true"] { background: #f3f4f6; }

        .tip-review-pager {
            margin: 24px 0 40px auto;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: .65rem;
            width: fit-content;
            max-width: 100%;
            padding-bottom: 32px;
        }

        .tip-review-pager__btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .48rem .82rem;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            text-decoration: none;
            font-weight: 700;
            font-size: .9rem;
            transition: all .15s ease;
        }

        .tip-review-pager__btn--active {
            background: #E8B34B;
            border-color: #F3C96A;
            color: #7A2E1F;
        }

        .tip-review-pager__btn--active:hover {
            background: #F3C96A;
            transform: translateY(-1px);
        }

        .tip-review-pager__btn--disabled {
            background: #f9fafb;
            color: #9ca3af;
            pointer-events: none;
            border-color: #e5e7eb;
        }

        .tip-review-pager__text {
            color: #6b7280;
            font-size: .9rem;
            font-weight: 600;
            padding: 0 .25rem;
            white-space: nowrap;
        }

        @media (max-width: 640px) {
            .tip-review-list {
                grid-template-columns: 1fr;
            }

            .tip-review-card .tip-review-summary,
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

            .tip-review-pager {
                gap: .4rem;
            }

            .tip-review-pager__btn {
                padding: .42rem .58rem;
                font-size: .8rem;
            }

            .tip-review-pager__text {
                padding: 0;
                font-size: .8rem;
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
        <span class="tip-status-dropdown" data-tip-status-dropdown>
            <select id="status-filter" class="tip-status-dropdown__native" name="status">
                <option value="pending" @selected(($statusFilter ?? 'all') === 'pending')>Pending</option>
                <option value="all" @selected(($statusFilter ?? 'all') === 'all')>All</option>
                <option value="accepted" @selected(($statusFilter ?? 'all') === 'accepted')>Accepted</option>
                <option value="rejected" @selected(($statusFilter ?? 'all') === 'rejected')>Rejected</option>
            </select>
            <button class="tip-status-dropdown__toggle" type="button" aria-haspopup="listbox" aria-expanded="false" aria-label="Filter by status"></button>
            <ul class="tip-status-dropdown__menu" role="listbox" aria-label="Status options" hidden></ul>
        </span>
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

                <article class="tip-review-card">
                    <div class="tip-review-summary">
                        <div style="font-weight:700; color:#111827; font-size:1.02rem;">
                            {{ $tip['landmark_name'] !== '' ? $tip['landmark_name'] : 'General Tip' }}
                        </div>
                        <span style="background:{{ $badgeBg }}; color:{{ $badgeColor }}; flex:0 0 auto; font-size:.82rem; font-weight:700; padding:.28rem .62rem; border-radius:999px;">
                            {{ $statusLabel }}
                        </span>
                    </div>

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
                </article>
            @endforeach
        </div>

        @if ($tips->hasPages())
            <nav class="tip-review-pager" aria-label="Review tips pagination">
                @if ($tips->onFirstPage())
                    <span class="tip-review-pager__btn tip-review-pager__btn--disabled">← Prev</span>
                @else
                    <a class="tip-review-pager__btn tip-review-pager__btn--active" href="{{ $tips->previousPageUrl() }}">← Prev</a>
                @endif

                <span class="tip-review-pager__text">Page {{ $tips->currentPage() }} of {{ $tips->lastPage() }}</span>

                @if ($tips->hasMorePages())
                    <a class="tip-review-pager__btn tip-review-pager__btn--active" href="{{ $tips->nextPageUrl() }}">Next →</a>
                @else
                    <span class="tip-review-pager__btn tip-review-pager__btn--disabled">Next →</span>
                @endif
            </nav>
        @endif
    @endif

    <script>
        (function () {
            var root = document.querySelector('[data-tip-status-dropdown]');
            if (!root) return;

            var form = root.closest('form');
            var select = root.querySelector('.tip-status-dropdown__native');
            var toggle = root.querySelector('.tip-status-dropdown__toggle');
            var menu = root.querySelector('.tip-status-dropdown__menu');

            function close() {
                root.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
                menu.hidden = true;
            }

            function render() {
                var selected = select.options[select.selectedIndex];
                toggle.textContent = selected ? selected.text : '';
                menu.innerHTML = '';
                Array.prototype.forEach.call(select.options, function (option) {
                    var item = document.createElement('li');
                    var button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'tip-status-dropdown__option';
                    button.textContent = option.text;
                    button.setAttribute('role', 'option');
                    button.setAttribute('aria-selected', option.selected ? 'true' : 'false');
                    button.addEventListener('click', function () {
                        select.value = option.value;
                        render();
                        close();
                        form.submit();
                    });
                    item.appendChild(button);
                    menu.appendChild(item);
                });
            }

            toggle.addEventListener('click', function () {
                var open = !root.classList.contains('is-open');
                if (open) {
                    render();
                    root.classList.add('is-open');
                    toggle.setAttribute('aria-expanded', 'true');
                    menu.hidden = false;
                } else {
                    close();
                }
            });

            document.addEventListener('click', function (event) {
                if (!root.contains(event.target)) close();
            });

            render();
        })();
    </script>
@endsection
