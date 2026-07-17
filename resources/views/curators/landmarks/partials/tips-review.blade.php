<section class="lm-tip-review" aria-label="Tips Review">
    <style>
        .lm-tip-review {
            min-width: 0;
            max-height: 34rem;
            overflow-y: auto;
            border: 1px solid #e7e5e4;
            border-radius: 10px;
            background: #fafaf9;
            padding: 1rem;
        }
        .lm-tip-review h3 { margin: 0; color: #7A2E1F; font-size: 1.05rem; }
        .lm-tip-review__intro { margin: .3rem 0 .9rem; color: #6b7280; font-size: .82rem; }
        .lm-tip-list { display: grid; gap: .75rem; }
        .lm-tip {
            padding: .8rem;
            border: 1px solid #e7e5e4;
            border-radius: 9px;
            background: #fff;
        }
        .lm-tip__header { display: flex; justify-content: space-between; gap: .65rem; align-items: flex-start; }
        .lm-tip__title { margin: 0; color: #292524; font-size: .92rem; font-weight: 800; }
        .lm-tip__content { margin: .55rem 0; color: #44403c; font-size: .86rem; line-height: 1.45; }
        .lm-tip__meta { margin: 0; color: #78716c; font-size: .75rem; line-height: 1.5; }
        .lm-tip__badge { flex-shrink: 0; padding: .22rem .5rem; border-radius: 999px; font-size: .7rem; font-weight: 800; }
        .lm-tip__badge--pending { background: #fef3c7; color: #92400e; }
        .lm-tip__badge--accepted { background: #dcfce7; color: #166534; }
        .lm-tip__badge--rejected { background: #fee2e2; color: #991b1b; }
        .lm-tip__actions { display: flex; gap: .45rem; margin-top: .65rem; }
        .lm-tip__action { padding: .38rem .65rem; border-radius: 7px; font-weight: 800; cursor: pointer; }
        .lm-tip__action--accept { border: 1px solid #86efac; background: #dcfce7; color: #166534; }
        .lm-tip__action--reject { border: 1px solid #fca5a5; background: #fee2e2; color: #991b1b; }
        .lm-tip__empty { margin: 1.25rem 0; color: #6b7280; text-align: center; font-size: .86rem; }
    </style>

    <h3>Tips Review</h3>
    <p class="lm-tip-review__intro">Visitor tips submitted for this landmark.</p>

    @if ($tips === [])
        <p class="lm-tip__empty">No visitor tips submitted yet.</p>
    @else
        <div class="lm-tip-list">
            @foreach ($tips as $tip)
                @php
                    $status = $tip['status'] ?? 'pending';
                    $statusLabel = $status === 'accepted' ? 'Approved' : ucfirst($status);
                @endphp
                <article class="lm-tip">
                    <div class="lm-tip__header">
                        <h4 class="lm-tip__title">{{ $tip['title'] !== '' ? $tip['title'] : 'Visitor tip' }}</h4>
                        <span class="lm-tip__badge lm-tip__badge--{{ $status }}">{{ $statusLabel }}</span>
                    </div>
                    <p class="lm-tip__content">{{ $tip['content'] !== '' ? $tip['content'] : 'No tip content provided.' }}</p>
                    <p class="lm-tip__meta">
                        {{ $tip['submitted_by'] }}
                        @if ($tip['submitted_email'] !== '' && $tip['submitted_email'] !== $tip['submitted_by'])
                            · {{ $tip['submitted_email'] }}
                        @endif
                        <br>{{ $tip['created_at'] }}
                    </p>
                </article>
            @endforeach
        </div>
    @endif
</section>
