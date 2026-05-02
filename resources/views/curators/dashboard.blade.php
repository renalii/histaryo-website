@extends('layouts.sidebar')

@section('content')
    @php
        use Carbon\Carbon;
        $today = Carbon::now()->format('F j, Y');

        // ✅ Use stored session name if available
        $email = session('email');
        $name = session('name') ?? ($email ? ucfirst(explode('@', $email)[0]) : 'Curator');

        $stats = $stats ?? [
            'landmarks' => $landmarksCount ?? 0,
            'trivia' => $triviaCount ?? 0,
            'pending' => $pendingReviews ?? 0,
            'logs' => $logsCount ?? 0,
        ];

        $recentLandmarks = $recentLandmarks ?? [];
        $recentTrivia = $recentTrivia ?? [];
        $recentLogs = $recentLogs ?? [];
    @endphp

    {{-- Welcome Banner --}}
    <div style="
        background: linear-gradient(135deg, #7A2E1F, #E8B34B);
        color: #fff;
        padding: 2rem 2.25rem;
        border-radius: 1.25rem;
        margin-bottom: 2rem;
        box-shadow: 0 12px 24px rgba(122, 46, 31, 0.2);
        display: flex; flex-direction: column; gap: 0.5rem;">
        <p style="margin: 0; font-size: 0.9rem; opacity: 0.85;">{{ $today }}</p>
        <h2 style="font-size: 2rem; font-weight: 700; margin: 0;">Welcome back, {{ $name }}</h2>
        <p style="margin: 0; font-size: 1rem; opacity: 0.95;">
            You manage content for your <strong>assigned landmark</strong> only—display QR codes and trivia scoped to it.
        </p>
    </div>

    {{-- Top Stats --}}
    <div class="grid" style="display:grid; grid-template-columns: repeat(12, minmax(0,1fr)); gap: 1rem; margin-bottom:1rem;">
        <div class="card stat" style="grid-column: span 4; background:#fff; border-radius:14px; padding:1rem; box-shadow: 0 4px 16px rgba(0,0,0,0.06); border:1px solid #f3f4f6;">
            <div style="display:flex; align-items:center; justify-content:space-between;">
                <div>
                    <p style="margin:0; color:#6b7280; font-size:.9rem;">Landmarks</p>
                    <h3 style="margin:.25rem 0 0 0; font-size:1.75rem; color:#4c1d95;">{{ number_format($stats['landmarks']) }}</h3>
                </div>
                <div class="pill" style="background:#f5f3ff; color:#6d28d9; padding:.4rem .6rem; border-radius:999px; font-size:.8rem;">All-time</div>
            </div>
        </div>

        <div class="card stat" style="grid-column: span 4; background:#fff; border-radius:14px; padding:1rem; box-shadow: 0 4px 16px rgba(0,0,0,0.06); border:1px solid #f3f4f6;">
            <div style="display:flex; align-items:center; justify-content:space-between;">
                <div>
                    <p style="margin:0; color:#6b7280; font-size:.9rem;">Trivia</p>
                    <h3 style="margin:.25rem 0 0 0; font-size:1.75rem; color:#4c1d95;">{{ number_format($stats['trivia']) }}</h3>
                </div>
                <div class="pill" style="background:#fdf2f8; color:#be185d; padding:.4rem .6rem; border-radius:999px; font-size:.8rem;">Published</div>
            </div>
        </div>

        <div class="card stat" style="grid-column: span 4; background:#fff; border-radius:14px; padding:1rem; box-shadow: 0 4px 16px rgba(0,0,0,0.06); border:1px solid #f3f4f6;">
            <div style="display:flex; align-items:center; justify-content:space-between;">
                <div>
                    <p style="margin:0; color:#6b7280; font-size:.9rem;">Pending Tips</p>
                    <h3 style="margin:.25rem 0 0 0; font-size:1.75rem; color:#4c1d95;">{{ number_format($stats['pending']) }}</h3>
                </div>
                <a href="{{ route('curators.tips.index') }}" class="pill" style="background:#ecfeff; color:#0e7490; padding:.4rem .6rem; border-radius:999px; font-size:.8rem; text-decoration:none;">Review</a>
            </div>
        </div>
    </div>

    {{-- Actions + Charts --}}
    <div class="grid" style="display:grid; grid-template-columns: repeat(12, minmax(0,1fr)); gap: 1rem; margin-bottom:1rem;">
        {{-- Quick Actions --}}
        <div class="card" style="grid-column: span 4; background:#fff; border-radius:14px; padding:1rem; box-shadow: 0 4px 16px rgba(0,0,0,0.06);">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:.75rem;">
                <h4 style="margin:0; color:#111827;">Quick Actions</h4>
            </div>
            <div style="display:flex; flex-direction:column; gap:.5rem;">
                <a href="{{ route('landmarks.index') }}" style="text-decoration:none; background:#E8B34B; color:#7A2E1F; padding:.75rem 1rem; border-radius:10px; font-weight:700; text-align:center; border:1px solid #F3C96A;">Your Landmark</a>
                <a href="{{ route('curators.trivia.all') }}" style="text-decoration:none; background:#f3f4f6; color:#111827; padding:.75rem 1rem; border-radius:10px; font-weight:600; text-align:center; border:1px solid #e5e7eb;">Displays / Trivia</a>
                <a href="{{ route('curators.map') }}" style="text-decoration:none; background:#F3C96A; color:#7A2E1F; padding:.75rem 1rem; border-radius:10px; font-weight:700; text-align:center; border:1px solid #E8B34B;">View Map</a>
            </div>
        </div>

        {{-- Line Chart --}}
        <div class="card" style="grid-column: span 8; background:#fff; border-radius:14px; padding:1rem; box-shadow: 0 4px 16px rgba(0,0,0,0.06);">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:.75rem;">
                <h4 style="margin:0; color:#111827;">Last 8 Weeks — Items Added</h4>
                <div style="font-size:.85rem; color:#6b7280;">Landmarks & Trivia</div>
            </div>
            <canvas id="lineChart" height="110"></canvas>
        </div>
    </div>

    {{-- Two Columns --}}
    <div class="grid" style="display:grid; grid-template-columns: repeat(12, minmax(0,1fr)); gap: 1rem; margin-bottom:1rem;">
        {{-- Recent Landmarks --}}
        <div class="card" style="grid-column: span 7; background:#fff; border-radius:14px; padding:1rem; box-shadow: 0 4px 16px rgba(0,0,0,0.06);">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:.75rem;">
                <h4 style="margin:0; color:#111827;">Recent Landmarks</h4>
                <a href="{{ route('landmarks.index') }}" style="font-size:.9rem; color:#7A2E1F; text-decoration:none; font-weight:700;">View all</a>
            </div>

            @if (empty($recentLandmarks))
                <p style="color:#6b7280; margin:.5rem 0; background:#f9fafb; border:1px dashed #e5e7eb; border-radius:10px; padding:.8rem;">Your assigned landmark will appear here after your account is fully approved.</p>
            @else
                <div style="overflow:auto;">
                    <table style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr style="text-align:left; color:#6b7280; font-size:.85rem;">
                                <th style="padding:.5rem .25rem;">Name</th>
                                <th style="padding:.5rem .25rem;">Created</th>
                                <th style="padding:.5rem .25rem;">Location</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentLandmarks as $l)
                                <tr style="border-top:1px solid #f3f4f6;">
                                    <td style="padding:.6rem .25rem; font-weight:600; color:#111827;">{{ $l['name'] ?? 'Untitled' }}</td>
                                    <td style="padding:.6rem .25rem; color:#374151;">{{ $l['created_at'] ?? '—' }}</td>
                                    <td style="padding:.6rem .25rem; color:#374151;">{{ $l['location'] ?? (($l['latitude'] ?? '').', '.($l['longitude'] ?? '')) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Doughnut Chart --}}
        <div class="card" style="grid-column: span 5; background:#fff; border-radius:14px; padding:1rem; box-shadow: 0 4px 16px rgba(0,0,0,0.06);">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:.75rem;">
                <h4 style="margin:0; color:#111827;">Content Mix</h4>
                <div style="font-size:.85rem; color:#6b7280;">Share by type</div>
            </div>
            <canvas id="donutChart" height="180"></canvas>
            <div style="display:flex; justify-content:center; gap:1rem; margin-top:.5rem; color:#374151; font-size:.9rem;">
                <span>Landmarks</span>
                <span>Trivia</span>
            </div>
        </div>
    </div>

    {{-- Activity Feed & Tips --}}
    <div class="grid" style="display:grid; grid-template-columns: repeat(12, minmax(0,1fr)); gap: 1rem; margin-bottom:2rem;">
        {{-- Activity Feed --}}
        <div class="card" style="grid-column: span 7; background:#fff; border-radius:14px; padding:1rem; box-shadow: 0 4px 16px rgba(0,0,0,0.06);">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:.75rem;">
                <h4 style="margin:0; color:#111827;">Recent Activity</h4>
                <div style="font-size:.85rem; color:#6b7280;">Last 24 hours</div>
            </div>

            @if (empty($recentLogs))
                <p style="color:#6b7280; margin:.5rem 0; background:#f9fafb; border:1px dashed #e5e7eb; border-radius:10px; padding:.8rem;">No recent activity in the last 24 hours.</p>
            @else
                <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:.75rem;">
                    @foreach ($recentLogs as $log)
                        <li style="display:flex; align-items:flex-start;">
                            <div>
                                <div style="font-weight:600; color:#111827;">{{ $log['action'] ?? 'Action' }}</div>
                                <div style="color:#6b7280; font-size:.9rem;">{{ $log['email'] ?? 'user' }} • {{ $log['timestamp'] ?? '' }}</div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Tips --}}
        <div class="card" style="grid-column: span 5; background:#fff; border-radius:14px; padding:1rem; box-shadow: 0 4px 16px rgba(0,0,0,0.06);">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:.75rem;">
                <h4 style="margin:0; color:#111827;">Tips & Shortcuts</h4>
            </div>
            <div style="display:flex; flex-direction:column; gap:.6rem; color:#374151;">
                <div style="background:#f8fafc; border:1px dashed #e5e7eb; padding:.75rem; border-radius:10px;">
                    Use the <strong>Map</strong> view to verify coordinates visually before publishing.
                </div>
                <div style="background:#f8fafc; border:1px dashed #e5e7eb; padding:.75rem; border-radius:10px;">
                    Add a short <strong>video URL</strong> to boost engagement on landmark pages.
                </div>
                <div style="background:#f8fafc; border:1px dashed #e5e7eb; padding:.75rem; border-radius:10px;">
                    Keep descriptions concise. Aim for <strong>80–120 words</strong>.
                </div>
            </div>
        </div>
    </div>

    {{-- Page Styles --}}
    <style>
        @media (max-width: 1024px) {
            .grid > .card { grid-column: span 12 !important; }
            .grid > .stat { grid-column: span 6 !important; }
        }
        @media (max-width: 640px) {
            .grid > .stat { grid-column: span 12 !important; }
        }
        .card:hover { transform: translateY(-2px); transition: transform .15s ease, box-shadow .15s ease; box-shadow: 0 10px 24px rgba(0,0,0,0.08) !important; }
        .card tbody tr:hover { background:#fafafa; }
        .card a, .card button { transition: all .15s ease-in-out; }
        .card a:hover { transform: translateY(-1px); }
    </style>

    {{-- Charts --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const weeks = @json($weekLabels);
            const sampleLandmarks = @json($landmarksPerWeek);
            const sampleTrivia = @json($triviaPerWeek);

            
            const lineCtx = document.getElementById('lineChart').getContext('2d');
            new Chart(lineCtx, {
                type: 'line',
                data: {
                    labels: weeks,
                    datasets: [
                        { label: 'Landmarks', data: sampleLandmarks, tension: 0.35, borderWidth: 2, borderColor: '#7A2E1F', backgroundColor: '#7A2E1F', fill: false, pointRadius: 0, pointHoverRadius: 0 },
                        { label: 'Trivia', data: sampleTrivia, tension: 0.35, borderWidth: 2, borderColor: '#E8B34B', backgroundColor: '#E8B34B', fill: false, pointRadius: 0, pointHoverRadius: 0 },
                    ]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
                    interaction: { mode: 'nearest', axis: 'x', intersect: false },
                    scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                }
            });

            
            const donutCtx = document.getElementById('donutChart').getContext('2d');
            new Chart(donutCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Landmarks', 'Trivia'],
                    datasets: [{ data: [{{ (int)($stats['landmarks'] ?: 0) }}, {{ (int)($stats['trivia'] ?: 0) }}], borderWidth: 0, backgroundColor: ['#7A2E1F', '#E8B34B'] }]
                },
                options: { cutout: '60%', plugins: { legend: { display: false } } }
            });
        });
    </script>

@endsection
