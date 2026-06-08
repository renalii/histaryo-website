@extends('layouts.sidebar')

@section('content')
@php
    use Carbon\Carbon;
    $today = Carbon::now()->format('F j, Y');
    $email = session('email');
    $name = $email ? ucfirst(explode('@', $email)[0]) : 'Admin';
    $showInsights = $showSystemInsights ?? false;
    $isSiteManager = session('role') === 'site_manager';
    $statColClass = $isSiteManager ? 'lm-stat-2col' : ($showInsights ? '' : 'lm-stat-3col');
@endphp

<style>
    .admin-wrap { max-width: 2000px; margin: 0 auto; }
    .admin-kicker { font-size: .82rem; letter-spacing: .04em; text-transform: uppercase; opacity: .9; }
    .admin-hero {
        background: linear-gradient(135deg, #7A2E1F, #E8B34B);
        color: #fffdf7;
        padding: 1.6rem 2rem;
        border-radius: 1rem;
        margin-bottom: 1.2rem;
        box-shadow: 0 12px 28px rgba(122, 46, 31, 0.22);
    }
    .admin-hero h1 { font-size: 2rem; font-weight: 800; margin: 0; line-height: 1.2; }
    .admin-hero p { margin: 0 0 0.35rem 0; opacity: 0.95; font-weight: 500; }
    .admin-hero-sub { margin-top: .45rem; opacity: .92; font-size: .95rem; }
    .admin-grid { display: grid; grid-template-columns: repeat(12, minmax(0,1fr)); gap: 1rem; }
    .admin-stat {
        grid-column: span 3;
        background: #fff;
        border: 1px solid #eceff3;
        border-radius: 14px;
        padding: 1rem 1.1rem;
        box-shadow: 0 6px 16px rgba(0,0,0,0.05);
        position: relative;
        overflow: hidden;
    }
    .admin-stat::before {
        content: "";
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, #7A2E1F, #E8B34B);
    }
    .admin-stat h3 { margin: 0; font-size: .95rem; color: #6b7280; font-weight: 700; }
    .admin-stat p { margin: .45rem 0 0 0; font-size: 2rem; font-weight: 800; color: #111827; }
    .admin-stat small { color: #6b7280; font-weight: 600; display: block; margin-top: .35rem; }
    @media (min-width: 641px) {
        .admin-stat.lm-stat-3col { grid-column: span 4 !important; }
        .admin-stat.lm-stat-2col { grid-column: span 6 !important; }
    }
    .admin-chart {
        background: #fff;
        border: 1px solid #eceff3;
        border-radius: 14px;
        padding: 1rem 1.1rem;
        box-shadow: 0 6px 16px rgba(0,0,0,0.05);
    }
    .admin-chart h3 { margin: 0 0 .85rem 0; color: #4b5563; font-size: 1rem; font-weight: 700; }
    .admin-chart p { margin: -0.45rem 0 .8rem 0; color: #6b7280; font-size: .85rem; }
    .admin-chart-lg { grid-column: span 7; }
    .admin-chart-sm { grid-column: span 5; }
    .admin-chart canvas { width: 100% !important; height: 320px !important; }
    @media (max-width: 1024px) {
        .admin-stat { grid-column: span 6; }
        .admin-chart-lg, .admin-chart-sm { grid-column: span 12; }
    }
    @media (max-width: 640px) {
        .admin-stat { grid-column: span 12; }
        .admin-hero { padding: 1.2rem 1rem; }
        .admin-hero h1 { font-size: 1.55rem; }
    }
</style>

<div class="admin-wrap">
@if (session('panel_error'))
    <div style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca;border-radius:10px;padding:.85rem 1rem;margin-bottom:1rem;font-weight:600;">
        {{ session('panel_error') }}
    </div>
@endif
<div class="admin-hero">
    <p class="admin-kicker">{{ $today }}</p>
    <h1>Welcome back, {{ $name }}!</h1>
    @if ($showInsights)
        <p class="admin-hero-sub">Here is today's platform snapshot and recent usage trend.</p>
    @elseif ($isSiteManager)
        <p class="admin-hero-sub">Manage your landmarks and curators.</p>
    @else
        <p class="admin-hero-sub">Platform overview.</p>
    @endif
</div>

<div class="admin-grid" style="margin-bottom: 1rem;">
    @if (! $isSiteManager)
    <div class="admin-stat {{ $statColClass }}">
        <h3>Total Users</h3>
        <p>{{ $userCount ?? 0 }}</p>
        <small>All registered accounts</small>
    </div>
    @endif
    <div class="admin-stat {{ $statColClass }}">
        <h3>Curators</h3>
        <p>{{ $curatorCount ?? 0 }}</p>
        <small>{{ $isSiteManager ? 'Linked to your landmarks' : 'Active content managers' }}</small>
    </div>
    <div class="admin-stat {{ $statColClass }}">
        <h3>Landmarks</h3>
        <p>{{ $landmarkCount ?? 0 }}</p>
        <small>{{ $isSiteManager ? 'In your portfolio' : 'Published places' }}</small>
    </div>
    @if ($showInsights)
    <div class="admin-stat">
        <h3>Logs</h3>
        <p>{{ $logCount ?? 0 }}</p>
        <small>Tracked system events</small>
    </div>
    @endif
</div>

@if ($showInsights)
<div class="admin-grid">
    <div class="admin-chart admin-chart-lg">
        <h3>Visits Overview</h3>
        <p>Daily visit activity for the current week</p>
        <canvas id="visitsChart" width="400" height="300"></canvas>
    </div>

    <div class="admin-chart admin-chart-sm">
        <h3>Usage by Role</h3>
        <p>Current role distribution</p>
        <canvas id="roleUsageChart" width="400" height="300"></canvas>
    </div>
</div>
@endif
</div>

@if ($showInsights)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    const visitsData = {
        labels: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        datasets: [{
            label: 'Visits',
            data: {!! json_encode($visitsByDay ?? []) !!},
            borderColor: '#7A2E1F',
            backgroundColor: 'rgba(122, 46, 31, 0.12)',
            tension: 0.3,
            fill: true,
            pointRadius: 5,
            pointBackgroundColor: '#E8B34B'
        }]
    };

    const usageData = {
        labels: ['Admins', 'Curators', 'Visitors'],
        datasets: [{
            data: [
                {{ (int) ($adminCount ?? 0) }},
                {{ (int) ($curatorCount ?? 0) }},
                {{ (int) ($visitorCount ?? 0) }}
            ],
            backgroundColor: ['#7A2E1F', '#E8B34B', '#4F46E5'],
            borderWidth: 1
        }]
    };

    new Chart(document.getElementById('visitsChart'), {
        type: 'line',
        data: visitsData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: true } }
        }
    });

    const roleUsageChart = new Chart(document.getElementById('roleUsageChart'), {
        type: 'doughnut',
        data: usageData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label(context) {
                            const values = context.dataset.data.map(Number);
                            const total = values.reduce((sum, value) => sum + value, 0);
                            const value = Number(context.raw || 0);
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';

                            return `${context.label}: ${value.toLocaleString()} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });

    const roleUsageUrl = @json(route('admin.dashboard.role-usage'));

    async function refreshRoleUsageChart() {
        try {
            const response = await fetch(roleUsageUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin'
            });
            if (!response.ok) return;

            const counts = await response.json();
            roleUsageChart.data.datasets[0].data = [
                Number(counts.admins || 0),
                Number(counts.curators || 0),
                Number(counts.visitors || 0)
            ];
            roleUsageChart.update();
        } catch (error) {
            console.error('Could not refresh dashboard role usage.', error);
        }
    }

    window.setInterval(refreshRoleUsageChart, 30000);
</script>
@endif
@endsection
