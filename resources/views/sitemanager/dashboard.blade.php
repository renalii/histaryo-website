@extends('layouts.sidebar')

@section('content')
@php
    use Carbon\Carbon;

    $statistics = $siteManagerStatistics ?? [];
    $today = Carbon::now()->format('F j, Y');
    $email = session('email');
    $managerName = session('name') ?? ($email ? ucfirst(explode('@', $email)[0]) : 'Site Manager');
@endphp

<style>
    .manager-dashboard { max-width: 1800px; margin: 0 auto; color: #374151; }
    .manager-hero {
        background: linear-gradient(135deg, #7A2E1F, #E8B34B);
        color: #fffdf7;
        padding: 1.8rem 2rem;
        border-radius: 1.25rem;
        margin-bottom: 1.25rem;
        box-shadow: 0 12px 28px rgba(122, 46, 31, .22);
    }
    .manager-hero-date { margin: 0 0 .35rem; font-size: .86rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; opacity: .9; }
    .manager-hero h1 { margin: 0; font-size: 2rem; line-height: 1.2; font-weight: 800; }
    .manager-hero-copy { margin: .5rem 0 0; font-size: 1rem; font-weight: 500; opacity: .95; }
    .manager-section-title { margin: 1.3rem 0 .7rem; color: #3f261f; font-size: 1.1rem; font-weight: 800; }
    .manager-card { background: #fff; border: 1px solid #eceff3; border-radius: 14px; box-shadow: 0 6px 18px rgba(0,0,0,.05); }
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
    }
    .summary-card { position: relative; overflow: hidden; padding: 1.2rem 1.3rem; text-align: center; }
    .summary-card::before { content: ""; position: absolute; inset: 0 0 auto; height: 4px; background: linear-gradient(90deg, #7A2E1F, #E8B34B); }
    .summary-label { margin: 0; color: #6b7280; font-size: .95rem; font-weight: 700; }
    .summary-value { margin: .55rem 0 0; color: #111827; font-size: 2rem; font-weight: 800; line-height: 1; }
    .analytics-card, .leaderboard-card { padding: 1.15rem; }
    .analytics-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }
    .analytics-header h2, .leaderboard-card h2 { margin: 0; color: #3f261f; font-size: 1.1rem; }
    .analytics-header p, .leaderboard-card > p { margin: .3rem 0 0; color: #6b7280; font-size: .86rem; }
    .analytics-filter { display: flex; align-items: center; gap: .55rem; flex-shrink: 0; }
    .analytics-filter label { color: #6b7280; font-size: .85rem; font-weight: 700; }
    .analytics-filter select { border: 1px solid #d9dee7; border-radius: 9px; background: #fffdf7; color: #3f261f; padding: .55rem 2rem .55rem .7rem; font: inherit; font-size: .88rem; font-weight: 700; cursor: pointer; }
    .analytics-filter select:focus { outline: none; border-color: #d1d5db; box-shadow: none; }
    .analytics-controls { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; }
    .chart-wrap { position: relative; height: 330px; }
    .leaderboard-card { margin-bottom: 2rem; overflow: hidden; }
    .table-wrap { margin-top: .9rem; overflow-x: auto; }
    .leaderboard-table { width: 100%; min-width: 720px; border-collapse: collapse; }
    .leaderboard-table th, .leaderboard-table td { padding: .85rem .7rem; text-align: left; border-bottom: 1px solid #eceff3; }
    .leaderboard-table th { color: #6b7280; font-size: .75rem; letter-spacing: .04em; text-transform: uppercase; }
    .leaderboard-table td { color: #374151; font-size: .9rem; }
    .leaderboard-table tbody tr:last-child td { border-bottom: 0; }
    .visitor-records-pager {
        margin-top: .9rem;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: .65rem;
        flex-wrap: nowrap;
    }
    .visitor-records-pager__btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: .48rem .82rem;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        background: #E8B34B;
        color: #7A2E1F;
        font: inherit;
        font-size: .9rem;
        font-weight: 700;
        cursor: pointer;
    }
    .visitor-records-pager__btn:disabled {
        background: #f9fafb;
        color: #9ca3af;
        cursor: default;
    }
    .visitor-records-pager__text {
        color: #6b7280;
        font-size: .9rem;
        font-weight: 600;
        padding: 0 .25rem;
        white-space: nowrap;
    }
    .rank { width: 2rem; height: 2rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; background: #fff5dd; color: #7A2E1F; font-weight: 800; }
    .score { color: #7A2E1F; font-weight: 800; }
    .empty-state { padding: 2.5rem 1rem !important; text-align: center !important; color: #6b7280 !important; }
    @media (max-width: 1024px) {
        .summary-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (max-width: 640px) {
        .manager-hero { padding: 1.3rem 1.15rem; }
        .manager-hero h1 { font-size: 1.55rem; }
        .summary-grid { grid-template-columns: 1fr; }
        .analytics-header { align-items: stretch; flex-direction: column; }
        .analytics-controls { align-items: stretch; flex-direction: column; }
        .analytics-filter { justify-content: space-between; }
        .analytics-filter select { flex: 1; }
        .chart-wrap { height: 250px; }
    }
</style>

<div class="manager-dashboard">
    <section class="manager-hero">
        <p class="manager-hero-date">{{ $today }}</p>
        <h1>Welcome back, {{ $managerName }}!</h1>
        <p class="manager-hero-copy">You manage visitors, curators, and activity across your landmarks.</p>
    </section>

    <h2 class="manager-section-title">Multiple Landmarks Summary</h2>
    <section class="summary-grid">
        <div class="manager-card summary-card">
            <p class="summary-label">Total Managed Landmarks</p>
            <p class="summary-value">{{ number_format($landmarkCount ?? 0) }}</p>
        </div>
        <div class="manager-card summary-card">
            <p class="summary-label">Total Visitors</p>
            <p class="summary-value">{{ number_format($statistics['total_visitors'] ?? 0) }}</p>
        </div>
        <div class="manager-card summary-card">
            <p class="summary-label">Total Curators</p>
            <p class="summary-value">{{ number_format($curatorCount ?? 0) }}</p>
        </div>
    </section>

    <h2 class="manager-section-title">Statistics</h2>
    <section class="manager-card analytics-card">
        <div class="analytics-header">
            <div>
                <h2>Visitor Activity Trend</h2>
                <p id="visitorAnalyticsDescription">Visits during the last 7 days</p>
            </div>
            <div class="analytics-controls">
                <div class="analytics-filter">
                    <label for="visitorAnalyticsLandmark">Landmark</label>
                    <select id="visitorAnalyticsLandmark">
                        @foreach (($statistics['landmark_options'] ?? [['id' => 'all', 'name' => 'All managed landmarks']]) as $option)
                            <option value="{{ $option['id'] }}">{{ $option['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="analytics-filter">
                    <label for="visitorAnalyticsPeriod">Period</label>
                    <select id="visitorAnalyticsPeriod">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="yearly">Yearly</option>
                        <option value="year_by_year">Year-by-Year</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="chart-wrap">
            <canvas id="visitorAnalyticsChart"></canvas>
        </div>
    </section>

    <h2 class="manager-section-title">Visitor Records by Username</h2>
    <section class="manager-card leaderboard-card">
        <h2>Visitor Records</h2>
        <p>Visitors grouped by username across your managed landmarks.</p>
        <div class="table-wrap">
            <table class="leaderboard-table">
                <thead>
                    <tr>
                        <th>Visitor Name / Username</th>
                        <th>Landmark Visited</th>
                        <th>Visit Count</th>
                        <th>Last Visit Date</th>
                    </tr>
                </thead>
                <tbody id="visitorRecordRows"></tbody>
            </table>
        </div>
        <nav id="visitorRecordsPager" class="visitor-records-pager" aria-label="Visitor records pagination" hidden>
            <button type="button" id="visitorRecordsPrev" class="visitor-records-pager__btn">Prev</button>
            <span id="visitorRecordsPageText" class="visitor-records-pager__text">Page 1 of 1</span>
            <button type="button" id="visitorRecordsNext" class="visitor-records-pager__btn">Next</button>
        </nav>
    </section>

    <h2 class="manager-section-title">Quiz Leaderboard per Landmark</h2>
    <section class="manager-card leaderboard-card">
        <div class="analytics-header">
            <div>
                <h2>Top Visitor Scores</h2>
                <p>Highest quiz scores achieved for the selected landmark.</p>
            </div>
            <div class="analytics-filter">
                <label for="leaderboardLandmark">Landmark</label>
                <select id="leaderboardLandmark">
                    @foreach (($statistics['landmark_options'] ?? [['id' => 'all', 'name' => 'All managed landmarks']]) as $option)
                        <option value="{{ $option['id'] }}">{{ $option['name'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="table-wrap">
            <table class="leaderboard-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Visitor Name</th>
                        <th>Landmark</th>
                        <th>Score</th>
                        <th>Date Achieved</th>
                    </tr>
                </thead>
                <tbody id="leaderboardRows"></tbody>
            </table>
        </div>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const charts = @json($statistics['charts'] ?? []);
        const analyticsByLandmark = @json($statistics['analytics_by_landmark'] ?? []);
        const visitorRecords = @json($statistics['visitor_records'] ?? []);
        const leaderboardByLandmark = @json($statistics['leaderboard_by_landmark'] ?? ['all' => ($statistics['leaderboard'] ?? [])]);
        const visitorRecordsPerPage = 5;
        let visitorRecordsPage = 1;
        const periods = {
            daily: { description: 'Visits during the last 7 days', color: '#7A2E1F' },
            weekly: { description: 'Visits during the last 8 weeks', color: '#B66B30' },
            monthly: { description: 'Visits during the last 12 months', color: '#D49A35' },
            yearly: { description: 'Visits during the last 5 years', color: '#5F3B32' },
            year_by_year: { description: 'Year-by-year visitors', color: '#7A2E1F' }
        };
        const initialAnalytics = analyticsByLandmark.all || { totals: {}, charts: charts };
        const initial = (initialAnalytics.charts && initialAnalytics.charts.daily) || charts.daily || { labels: [], values: [] };
        const chart = new Chart(document.getElementById('visitorAnalyticsChart'), {
            type: 'line',
            data: {
                labels: initial.labels,
                datasets: [{
                    label: 'Visits',
                    data: initial.values,
                    borderColor: periods.daily.color,
                    backgroundColor: `${periods.daily.color}1f`,
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

        function formatNumber(value) {
            return new Intl.NumberFormat().format(Number(value || 0));
        }

        function selectedAnalytics() {
            const landmarkId = document.getElementById('visitorAnalyticsLandmark').value || 'all';
            return analyticsByLandmark[landmarkId] || analyticsByLandmark.all || { totals: {}, charts: charts };
        }

        function updateVisitorAnalytics() {
            const selected = selectedAnalytics();
            const periodKey = document.getElementById('visitorAnalyticsPeriod').value || 'daily';
            const period = periods[periodKey] || periods.daily;
            const data = selected.charts && selected.charts[periodKey] ? selected.charts[periodKey] : { labels: [], values: [] };
            document.getElementById('visitorAnalyticsDescription').textContent = period.description;
            chart.data.labels = data.labels;
            chart.data.datasets[0].data = data.values;
            chart.data.datasets[0].borderColor = period.color;
            chart.data.datasets[0].backgroundColor = `${period.color}1f`;
            chart.update();
        }

        function escapeHtml(value) {
            return String(value || '').replace(/[&<>"']/g, function (char) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char];
            });
        }

        function renderVisitorRecords() {
            const tbody = document.getElementById('visitorRecordRows');
            const pager = document.getElementById('visitorRecordsPager');
            const prev = document.getElementById('visitorRecordsPrev');
            const next = document.getElementById('visitorRecordsNext');
            const pageText = document.getElementById('visitorRecordsPageText');
            const totalPages = Math.max(1, Math.ceil(visitorRecords.length / visitorRecordsPerPage));
            visitorRecordsPage = Math.min(Math.max(1, visitorRecordsPage), totalPages);
            tbody.replaceChildren();

            if (visitorRecords.length === 0) {
                const row = document.createElement('tr');
                row.innerHTML = '<td colspan="4" class="empty-state">No visitor records have been recorded for your managed landmarks yet.</td>';
                tbody.appendChild(row);
                pager.hidden = true;
                return;
            }

            visitorRecords
                .slice((visitorRecordsPage - 1) * visitorRecordsPerPage, visitorRecordsPage * visitorRecordsPerPage)
                .forEach(function (record) {
                    const row = document.createElement('tr');
                    row.innerHTML = '<td>' + escapeHtml(record.visitor_name) + '</td>'
                        + '<td>' + escapeHtml(record.landmark) + '</td>'
                        + '<td><span class="score">' + formatNumber(record.visit_count) + '</span></td>'
                        + '<td>' + escapeHtml(record.last_visit_date) + '</td>';
                    tbody.appendChild(row);
                });

            pager.hidden = totalPages <= 1;
            pageText.textContent = 'Page ' + visitorRecordsPage + ' of ' + totalPages;
            prev.disabled = visitorRecordsPage <= 1;
            next.disabled = visitorRecordsPage >= totalPages;
        }

        function renderLeaderboard() {
            const landmarkId = document.getElementById('leaderboardLandmark').value || 'all';
            const rows = leaderboardByLandmark[landmarkId] || [];
            const tbody = document.getElementById('leaderboardRows');
            tbody.replaceChildren();
            if (rows.length === 0) {
                const row = document.createElement('tr');
                row.innerHTML = '<td colspan="5" class="empty-state">No completed quizzes have been recorded for this landmark yet.</td>';
                tbody.appendChild(row);
                return;
            }
            rows.forEach(function (entry, index) {
                const row = document.createElement('tr');
                row.innerHTML = '<td><span class="rank">' + (index + 1) + '</span></td>'
                    + '<td>' + escapeHtml(entry.visitor_name) + '</td>'
                    + '<td>' + escapeHtml(entry.landmark) + '</td>'
                    + '<td><span class="score">' + escapeHtml(entry.score) + '</span></td>'
                    + '<td>' + escapeHtml(entry.completed_at_label) + '</td>';
                tbody.appendChild(row);
            });
        }

        document.getElementById('visitorAnalyticsPeriod').addEventListener('change', updateVisitorAnalytics);
        document.getElementById('visitorAnalyticsLandmark').addEventListener('change', updateVisitorAnalytics);
        document.getElementById('visitorRecordsPrev').addEventListener('click', function () {
            visitorRecordsPage--;
            renderVisitorRecords();
        });
        document.getElementById('visitorRecordsNext').addEventListener('click', function () {
            visitorRecordsPage++;
            renderVisitorRecords();
        });
        document.getElementById('leaderboardLandmark').addEventListener('change', renderLeaderboard);
        updateVisitorAnalytics();
        renderVisitorRecords();
        renderLeaderboard();
    });
</script>
@endsection
