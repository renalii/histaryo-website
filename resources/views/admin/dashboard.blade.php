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
    html:has(body .admin-dashboard-wrap),
    body:has(.admin-dashboard-wrap) {
        overflow-y: hidden;
    }

    body:has(.admin-dashboard-wrap) .main-content {
        height: 100vh;
        overflow-y: hidden;
    }

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
    .admin-summary-stat { padding: .8rem 1.1rem; }
    .admin-summary-stat p { margin-top: .3rem; line-height: 1; }
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
    .sm-section-title { grid-column: span 12; margin: .45rem 0 -.2rem; }
    .sm-section-title h2 { margin: 0; color: #3f261f; font-size: 1.2rem; }
    .sm-section-title p { margin: .3rem 0 0; color: #6b7280; font-size: .88rem; }
    .sm-stat { grid-column: span 2; }
    .sm-stat-total { grid-column: span 4; }
    .sm-chart { grid-column: span 12; }
    .sm-chart canvas { height: 320px !important; }
    .sm-chart-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: .8rem;
    }
    .sm-chart-header h3 { margin-bottom: .45rem; }
    .sm-chart-header p { margin: 0; }
    .sm-chart-filter { display: flex; align-items: center; gap: .55rem; flex-shrink: 0; }
    .sm-chart-filter label { color: #6b7280; font-size: .85rem; font-weight: 700; }
    .sm-chart-filter select {
        border: 1px solid #d9dee7;
        border-radius: 9px;
        background: #fffdf7;
        color: #3f261f;
        padding: .55rem 2rem .55rem .7rem;
        font: inherit;
        font-size: .88rem;
        font-weight: 700;
        cursor: pointer;
    }
    .sm-chart-filter select:focus { outline: 2px solid rgba(232, 179, 75, .45); border-color: #E8B34B; }
    .sm-leaderboard { grid-column: span 12; overflow: hidden; }
    .sm-table-wrap { overflow-x: auto; }
    .sm-table { width: 100%; border-collapse: collapse; min-width: 690px; }
    .sm-table th, .sm-table td { padding: .85rem .7rem; text-align: left; border-bottom: 1px solid #eceff3; }
    .sm-table th { color: #6b7280; font-size: .75rem; letter-spacing: .04em; text-transform: uppercase; }
    .sm-table td { color: #374151; font-size: .9rem; }
    .sm-table tbody tr:last-child td { border-bottom: 0; }
    .sm-rank {
        width: 2rem;
        height: 2rem;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fff5dd;
        color: #7A2E1F;
        font-weight: 800;
    }
    .sm-score { color: #7A2E1F; font-weight: 800; }
    .sm-empty { padding: 2.5rem 1rem !important; text-align: center !important; color: #6b7280 !important; }
    @media (max-width: 1024px) {
        .admin-stat { grid-column: span 6; }
        .admin-chart-lg, .admin-chart-sm { grid-column: span 12; }
        .sm-stat, .sm-stat-total { grid-column: span 6; }
    }
    @media (max-width: 640px) {
        .admin-stat { grid-column: span 12; }
        .sm-stat, .sm-stat-total { grid-column: span 12; }
        .admin-hero { padding: 1.2rem 1rem; }
        .admin-hero h1 { font-size: 1.55rem; }
        .sm-chart canvas { height: 230px !important; }
        .sm-chart-header { align-items: stretch; flex-direction: column; }
        .sm-chart-filter { justify-content: space-between; }
        .sm-chart-filter select { flex: 1; }
    }
</style>

<div class="admin-wrap {{ $isSiteManager ? 'site-manager-dashboard-wrap' : 'admin-dashboard-wrap' }}">
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
    <div class="admin-stat admin-summary-stat {{ $statColClass }}">
        <h3>Total Users</h3>
        <p>{{ $userCount ?? 0 }}</p>
    </div>
    @endif
    <div class="admin-stat admin-summary-stat {{ $statColClass }}">
        <h3>Curators</h3>
        <p>{{ $curatorCount ?? 0 }}</p>
    </div>
    <div class="admin-stat admin-summary-stat {{ $statColClass }}">
        <h3>Landmarks</h3>
        <p>{{ $landmarkCount ?? 0 }}</p>
    </div>
    @if ($showInsights)
    <div class="admin-stat admin-summary-stat">
        <h3>Logs</h3>
        <p>{{ $logCount ?? 0 }}</p>
    </div>
    @endif
</div>

@if ($isSiteManager)
@php($managerStats = $siteManagerStatistics ?? [])
<div class="admin-grid">
    <div class="sm-section-title">
        <h2>Visitor Statistics</h2>
        <p>Activity across the landmarks in your portfolio.</p>
    </div>

    <div class="admin-stat sm-stat sm-stat-total">
        <h3>Total Visitors</h3>
        <p>{{ number_format($managerStats['total_visitors'] ?? 0) }}</p>
        <small>Unique visitors recorded</small>
    </div>
    <div class="admin-stat sm-stat">
        <h3>Daily Visits</h3>
        <p>{{ number_format($managerStats['daily_visits'] ?? 0) }}</p>
        <small>Today</small>
    </div>
    <div class="admin-stat sm-stat">
        <h3>Weekly Visits</h3>
        <p>{{ number_format($managerStats['weekly_visits'] ?? 0) }}</p>
        <small>This week</small>
    </div>
    <div class="admin-stat sm-stat">
        <h3>Monthly Visits</h3>
        <p>{{ number_format($managerStats['monthly_visits'] ?? 0) }}</p>
        <small>This month</small>
    </div>
    <div class="admin-stat sm-stat">
        <h3>Yearly Visits</h3>
        <p>{{ number_format($managerStats['yearly_visits'] ?? 0) }}</p>
        <small>This year</small>
    </div>

    <div class="admin-chart sm-chart">
        <div class="sm-chart-header">
            <div>
                <h3>Visitor Activity Trend</h3>
                <p id="visitorActivityDescription">Visits during the last 7 days</p>
            </div>
            <div class="sm-chart-filter">
                <label for="visitorActivityPeriod">Filter:</label>
                <select id="visitorActivityPeriod">
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                    <option value="yearly">Yearly</option>
                </select>
            </div>
        </div>
        <canvas id="visitorActivityChart"></canvas>
    </div>

    <div class="admin-chart sm-leaderboard">
        <h3>Quiz Leaderboard</h3>
        <p>Top quiz scores from visitors to your landmarks</p>
        <div class="sm-table-wrap">
            <table class="sm-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Visitor name</th>
                        <th>Landmark</th>
                        <th>Quiz score</th>
                        <th>Date completed</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse (($managerStats['leaderboard'] ?? []) as $entry)
                    <tr>
                        <td><span class="sm-rank">{{ $loop->iteration }}</span></td>
                        <td>{{ $entry['visitor_name'] }}</td>
                        <td>{{ $entry['landmark'] }}</td>
                        <td><span class="sm-score">{{ $entry['score'] }}</span></td>
                        <td>{{ $entry['completed_at_label'] }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="sm-empty">No completed quizzes have been recorded for your landmarks yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

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

@if ($showInsights || $isSiteManager)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@endif

@if ($showInsights)
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
        labels: ['Curators', 'Visitors'],
        datasets: [{
            data: [
                {{ (int) ($curatorCount ?? 0) }},
                {{ (int) ($visitorCount ?? 0) }}
            ],
            backgroundColor: ['#E8B34B', '#4F46E5'],
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

@if ($isSiteManager)
<script>
    const managerCharts = @json(($siteManagerStatistics ?? [])['charts'] ?? []);
    const managerChartPeriods = {
        daily: { description: 'Visits during the last 7 days', color: '#7A2E1F' },
        weekly: { description: 'Visits during the last 8 weeks', color: '#B66B30' },
        monthly: { description: 'Visits during the last 12 months', color: '#D49A35' },
        yearly: { description: 'Visits during the last 5 years', color: '#5F3B32' }
    };
    const initialManagerChart = managerCharts.daily || { labels: [], values: [] };
    const visitorActivityChart = new Chart(document.getElementById('visitorActivityChart'), {
        type: 'line',
        data: {
            labels: initialManagerChart.labels,
            datasets: [{
                label: 'Visits',
                data: initialManagerChart.values,
                borderColor: managerChartPeriods.daily.color,
                backgroundColor: `${managerChartPeriods.daily.color}1f`,
                fill: true,
                tension: .35,
                pointRadius: 3,
                pointHoverRadius: 5,
                pointBackgroundColor: '#E8B34B'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } },
                x: { grid: { display: false } }
            }
        }
    });

    document.getElementById('visitorActivityPeriod').addEventListener('change', function () {
        const period = managerChartPeriods[this.value] || managerChartPeriods.daily;
        const chartData = managerCharts[this.value] || { labels: [], values: [] };

        document.getElementById('visitorActivityDescription').textContent = period.description;
        visitorActivityChart.data.labels = chartData.labels;
        visitorActivityChart.data.datasets[0].data = chartData.values;
        visitorActivityChart.data.datasets[0].borderColor = period.color;
        visitorActivityChart.data.datasets[0].backgroundColor = `${period.color}1f`;
        visitorActivityChart.update();
    });
</script>
@endif
@endsection
