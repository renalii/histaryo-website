<section class="lm-tip-review" aria-label="Tips Review">
    <style>
        .lm-tip-review {
            align-self: stretch;
            box-sizing: border-box;
            min-width: 0;
            height: 100%;
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid #e7e5e4;
            border-radius: 10px;
            background: #fafaf9;
            padding: 1rem;
        }
        .lm-tip-review h3 { margin: 0; color: #7A2E1F; font-size: 1.05rem; }
        .lm-tip-review__intro { margin: .3rem 0 .9rem; color: #6b7280; font-size: .82rem; }
        .lm-tip-list {
            display: grid;
            gap: .75rem;
            min-height: 0;
            overflow: hidden;
        }
        .lm-tip-list[aria-busy="true"] { visibility: hidden; }
        .lm-tip {
            padding: .8rem;
            border: 1px solid #e7e5e4;
            border-radius: 9px;
            background: #fff;
        }
        .lm-tip[hidden] { display: none; }
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
        .lm-tip-review__pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .65rem;
            margin-top: auto;
            padding-top: .75rem;
        }
        .lm-tip-review__page {
            margin: 0;
            color: #6b7280;
            font-size: .75rem;
            font-weight: 700;
            text-align: center;
        }
        .lm-tip-review__nav {
            padding: .38rem .65rem;
            border: 1px solid #d6d3d1;
            border-radius: 7px;
            background: #fff;
            color: #7A2E1F;
            font-size: .76rem;
            font-weight: 800;
            cursor: pointer;
        }
        .lm-tip-review__nav:disabled {
            color: #a8a29e;
            background: #f5f5f4;
            cursor: not-allowed;
        }
    </style>

    <h3>Review Tip</h3>
    <p class="lm-tip-review__intro">Visitor tips submitted for this landmark.</p>

    @if ($tips === [])
        <p class="lm-tip__empty">No visitor tips submitted yet.</p>
    @else
        <div class="lm-tip-list" data-tips-per-page="2" aria-live="polite" aria-busy="true">
            @foreach ($tips as $tip)
                @php
                    $status = $tip['status'] ?? 'pending';
                    $statusLabel = $status === 'accepted' ? 'Approved' : ucfirst($status);
                @endphp
                <article class="lm-tip" data-tip-card>
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
        <nav class="lm-tip-review__pagination" aria-label="Review tip pages">
            <button class="lm-tip-review__nav" type="button" data-tip-previous disabled>Previous</button>
            <p class="lm-tip-review__page" data-tip-page aria-live="polite"></p>
            <button class="lm-tip-review__nav" type="button" data-tip-next>Next</button>
        </nav>

        <script>
            (() => {
                const panel = document.currentScript.closest('.lm-tip-review');
                const list = panel?.querySelector('.lm-tip-list');
                if (!list) return;

                const cards = [...list.querySelectorAll('[data-tip-card]')];
                const pageSize = Number(list.dataset.tipsPerPage) || 2;
                const previous = panel.querySelector('[data-tip-previous]');
                const next = panel.querySelector('[data-tip-next]');
                const pageLabel = panel.querySelector('[data-tip-page]');
                let page = 0;
                const pageCount = Math.max(1, Math.ceil(cards.length / pageSize));

                const renderPage = () => {
                    const first = page * pageSize;
                    cards.forEach((card, index) => {
                        card.hidden = index < first || index >= first + pageSize;
                    });
                    previous.disabled = page === 0;
                    next.disabled = page === pageCount - 1;
                    pageLabel.textContent = `Page ${page + 1} of ${pageCount}`;
                    list.removeAttribute('aria-busy');
                };

                previous.addEventListener('click', () => {
                    if (page > 0) { page -= 1; renderPage(); }
                });
                next.addEventListener('click', () => {
                    if (page < pageCount - 1) { page += 1; renderPage(); }
                });
                renderPage();
            })();
        </script>
    @endif
</section>
