@extends('layouts.sidebar')

@section('content')
    @php
        use Carbon\Carbon;
        $today = Carbon::now()->format('F j, Y');

        $email = session('email');
        $name = session('name') ?? ($email ? ucfirst(explode('@', $email)[0]) : 'Curator');

        $stats = $stats ?? [
            'landmarks' => $landmarksCount ?? 0,
            'quiz' => $quizCount ?? 0,
            'pending' => $pendingReviews ?? 0,
            'logs' => $logsCount ?? 0,
        ];

        $recentLandmarks = $recentLandmarks ?? [];
        $recentQuiz = $recentQuiz ?? [];
        $recentLogs = $recentLogs ?? [];
    @endphp

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
            You manage content for your <strong>assigned landmark</strong> only display QR codes and exhibit scoped to it.
        </p>
    </div>

    <div class="grid" style="display:grid; grid-template-columns: repeat(12, minmax(0,1fr)); gap: 1rem; margin-bottom:1rem;">
        <div class="card stat" style="grid-column: span 4; background:#fff; border-radius:14px; padding:1rem; box-shadow: 0 4px 16px rgba(0,0,0,0.06); border:1px solid #f3f4f6;">
            <p style="margin:0; color:#6b7280; font-size:.9rem;">Landmarks</p>
            <h3 style="margin:.25rem 0 0; font-size:1.75rem; color:#4c1d95;">{{ number_format($stats['landmarks']) }}</h3>
        </div>
        <div class="card stat" style="grid-column: span 4; background:#fff; border-radius:14px; padding:1rem; box-shadow: 0 4px 16px rgba(0,0,0,0.06); border:1px solid #f3f4f6;">
            <p style="margin:0; color:#6b7280; font-size:.9rem;">Quiz Bank</p>
            <h3 style="margin:.25rem 0 0; font-size:1.75rem; color:#4c1d95;">{{ number_format($stats['quiz']) }}</h3>
        </div>
        <div class="card stat" style="grid-column: span 4; background:#fff; border-radius:14px; padding:1rem; box-shadow: 0 4px 16px rgba(0,0,0,0.06); border:1px solid #f3f4f6;">
            <p style="margin:0; color:#6b7280; font-size:.9rem;">Pending Tips</p>
            <h3 style="margin:.25rem 0 0; font-size:1.75rem; color:#4c1d95;">{{ number_format($stats['pending']) }}</h3>
        </div>
    </div>

    <div class="grid" style="display:grid; grid-template-columns: repeat(12, minmax(0,1fr)); gap: 1rem; margin-bottom:1rem;">
        <div class="card" style="grid-column: span 4; background:#fff; border-radius:14px; padding:1rem; box-shadow: 0 4px 16px rgba(0,0,0,0.06);">
            <h4 style="margin:0 0 .75rem; color:#111827;">Quick Actions</h4>
            <div style="display:flex; flex-direction:column; gap:.5rem;">
                <a href="{{ route('landmarks.show', session('assigned_landmark_id')) }}" style="text-decoration:none; background:#E8B34B; color:#7A2E1F; padding:.75rem 1rem; border-radius:10px; font-weight:700; text-align:center;">Landmark</a>
                <a href="{{ route('curators.quiz.all') }}" style="text-decoration:none; background:#f3f4f6; color:#111827; padding:.75rem 1rem; border-radius:10px; font-weight:600; text-align:center;">Quiz Bank</a>
                <a href="{{ route('curators.map') }}" style="text-decoration:none; background:#F3C96A; color:#7A2E1F; padding:.75rem 1rem; border-radius:10px; font-weight:700; text-align:center;">Map</a>
            </div>
        </div>

        <div class="card" style="grid-column: span 8; background:#fff; border-radius:14px; padding:1rem; box-shadow: 0 4px 16px rgba(0,0,0,0.06);">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:.75rem;">
                <h4 style="margin:0; color:#111827;">Last 8 Weeks - Items Added</h4>
                <div style="font-size:.85rem; color:#6b7280;">Landmarks & Quiz Bank</div>
            </div>
            <canvas id="lineChart" height="110"></canvas>
        </div>
    </div>

    <div class="grid" style="display:grid; grid-template-columns: repeat(12, minmax(0,1fr)); gap: 1rem; margin-bottom:2rem;">
        <div class="card" style="grid-column: span 6; background:#fff; border-radius:14px; padding:1rem; box-shadow: 0 4px 16px rgba(0,0,0,0.06);">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:.75rem;">
                <h4 style="margin:0; color:#111827;">Content Mix</h4>
                <div style="font-size:.85rem; color:#6b7280;">Share by type</div>
            </div>
            <canvas id="donutChart" height="180"></canvas>
            <div style="display:flex; justify-content:center; gap:1rem; margin-top:.5rem; color:#374151; font-size:.9rem;">
                <span>Landmarks</span>
                <span>Quiz Bank</span>
            </div>
        </div>

        <div class="card" style="grid-column: span 6; background:#fff; border-radius:14px; padding:1rem; box-shadow: 0 4px 16px rgba(0,0,0,0.06);">
            <h4 style="margin:0 0 .75rem; color:#111827;">Tips & Shortcuts</h4>
            <div style="display:flex; flex-direction:column; gap:.6rem; color:#374151;">
                <div style="background:#f8fafc; border:1px dashed #e5e7eb; padding:.75rem; border-radius:10px;">Use the <strong>Map</strong> view to verify coordinates visually before publishing.</div>
                <div style="background:#f8fafc; border:1px dashed #e5e7eb; padding:.75rem; border-radius:10px;">Upload optimized <strong>photos and videos</strong> for faster loading.</div>
                <div style="background:#f8fafc; border:1px dashed #e5e7eb; padding:.75rem; border-radius:10px;">Keep descriptions concise. Aim for <strong>80-120 words</strong>.</div>
            </div>
        </div>
    </div>

    <style>
        @media (max-width: 1024px) {
            .grid > .card { grid-column: span 12 !important; }
            .grid > .stat { grid-column: span 6 !important; }
        }
        @media (max-width: 640px) {
            .grid > .stat { grid-column: span 12 !important; }
        }
        .card:hover { transform: translateY(-2px); transition: transform .15s ease, box-shadow .15s ease; box-shadow: 0 10px 24px rgba(0,0,0,0.08) !important; }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            new Chart(document.getElementById('lineChart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: @json($weekLabels),
                    datasets: [
                        { label: 'Landmarks', data: @json($landmarksPerWeek), tension: .35, borderWidth: 2, borderColor: '#7A2E1F', pointRadius: 0 },
                        { label: 'Quiz Bank', data: @json($quizPerWeek), tension: .35, borderWidth: 2, borderColor: '#E8B34B', pointRadius: 0 },
                    ]
                },
                options: { responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true, ticks:{stepSize:1}}} }
            });

            new Chart(document.getElementById('donutChart').getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Landmarks', 'Quiz Bank'],
                    datasets: [{
                        data: [{{ (int) ($stats['landmarks'] ?: 0) }}, {{ (int) ($stats['quiz'] ?: 0) }}],
                        borderWidth: 0,
                        backgroundColor: ['#7A2E1F', '#E8B34B']
                    }]
                },
                options: { cutout:'60%', plugins:{legend:{display:false}} }
            });
        });
    </script>
@endsection
