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
    .leaderboard-landmark-select { position: relative; width: 255px; }
    .leaderboard-landmark-select > select { display: none; }
    .leaderboard-landmark-select__toggle {
        width: 100%;
        border: 1px solid #d9dee7;
        border-radius: 9px;
        background: #fffdf7;
        color: #3f261f;
        padding: .55rem 2rem .55rem .7rem;
        font: inherit;
        font-size: .88rem;
        font-weight: 700;
        cursor: pointer;
        text-align: left;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .leaderboard-landmark-select__toggle::after {
        content: '';
        position: absolute;
        top: 50%;
        right: .75rem;
        width: .45rem;
        height: .45rem;
        border-right: 2px solid #3f261f;
        border-bottom: 2px solid #3f261f;
        transform: translateY(-70%) rotate(45deg);
        pointer-events: none;
    }
    .leaderboard-landmark-select__options {
        position: fixed;
        z-index: 9999;
        box-sizing: border-box;
        max-height: 322px;
        margin: 0;
        padding: 0;
        overflow-y: auto;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        background: #fff;
        box-shadow: 0 8px 20px rgba(0, 0, 0, .12);
        list-style: none;
    }
    .leaderboard-landmark-select__options.down,
    .leaderboard-landmark-select__options.up { bottom: auto; }
    .leaderboard-landmark-select__options[hidden] { display: none; }
    .leaderboard-landmark-select__option {
        display: block;
        width: 100%;
        height: 32px;
        padding: 0 .7rem;
        border: 0;
        background: transparent;
        color: #3f261f;
        font: inherit;
        font-size: .88rem;
        line-height: 32px;
        text-align: left;
        white-space: nowrap;
        cursor: pointer;
    }
    .leaderboard-landmark-select__option:hover,
    .leaderboard-landmark-select__option:focus,
    .leaderboard-landmark-select__option[aria-selected="true"] { background: #f3f4f6; outline: none; }
    .analytics-controls { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; }
    .chart-wrap { position: relative; height: 330px; }
    .visitor-landmark-chart { margin-top: .85rem; }
    .visitor-landmark-chart h3 { margin: 0 0 .55rem; color: #3f261f; font-size: 1rem; font-weight: 800; }
    .visitor-chart-controls {
        display: flex;
        align-items: center;
        gap: .75rem;
        flex-wrap: wrap;
        margin-bottom: .85rem;
    }
    .visitor-chart-scroll {
        overflow-x: auto;
        overflow-y: hidden;
        padding-bottom: .25rem;
    }
    .visitor-chart-canvas-wrap {
        position: relative;
        height: 230px;
        min-width: 100%;
    }
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
        .visitor-chart-controls { align-items: stretch; flex-direction: column; }
        .visitor-chart-canvas-wrap { height: 220px; }
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
                    <div class="leaderboard-landmark-select">
                        <select id="visitorAnalyticsLandmark">
                            @foreach (($statistics['landmark_options'] ?? [['id' => 'all', 'name' => 'All managed landmarks']]) as $option)
                                <option value="{{ $option['id'] }}">{{ $option['name'] }}</option>
                            @endforeach
                        </select>
                        <button type="button" id="visitorAnalyticsLandmarkToggle" class="leaderboard-landmark-select__toggle" aria-haspopup="listbox" aria-controls="visitorAnalyticsLandmarkOptions" aria-expanded="false">All managed landmarks</button>
                        <ul id="visitorAnalyticsLandmarkOptions" class="leaderboard-landmark-select__options" role="listbox" hidden></ul>
                    </div>
                </div>
                <div class="analytics-filter">
                    <label for="visitorAnalyticsPeriod">Period</label>
                    <select id="visitorAnalyticsPeriod">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="yearly">Yearly</option>
                        
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
        <div class="visitor-chart-controls">
            <div class="analytics-filter">
                <label for="visitsPerLandmarkPeriod">Time Period</label>
                <select id="visitsPerLandmarkPeriod">
                    <option value="7" selected>Last 7 Days</option>
                    <option value="30">Last 30 Days</option>
                    <option value="90">Last 90 Days</option>
                    <option value="all">All Time</option>
                </select>
            </div>
            <div class="analytics-filter">
                <label for="visitsPerLandmarkDisplay">Display</label>
                <select id="visitsPerLandmarkDisplay">
                    <option value="5">Top 5</option>
                    <option value="10" selected>Top 10</option>
                    <option value="20">Top 20</option>
                    <option value="all">All Landmarks</option>
                </select>
            </div>
        </div>
        <div class="visitor-landmark-chart">
            <h3>Visits per Landmark</h3>
            <div id="visitsPerLandmarkScroller" class="visitor-chart-scroll">
                <div id="visitsPerLandmarkCanvasWrap" class="visitor-chart-canvas-wrap">
                    <canvas id="visitsPerLandmarkChart"></canvas>
                </div>
            </div>
        </div>
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
                <div class="leaderboard-landmark-select">
                    <select id="leaderboardLandmark">
                        @foreach (($statistics['landmark_options'] ?? [['id' => 'all', 'name' => 'All managed landmarks']]) as $option)
                            <option value="{{ $option['id'] }}">{{ $option['name'] }}</option>
                        @endforeach
                    </select>
                    <button type="button" id="leaderboardLandmarkToggle" class="leaderboard-landmark-select__toggle" aria-haspopup="listbox" aria-controls="leaderboardLandmarkOptions" aria-expanded="false">All managed landmarks</button>
                    <ul id="leaderboardLandmarkOptions" class="leaderboard-landmark-select__options" role="listbox" hidden></ul>
                </div>
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
        <nav id="leaderboardPager" class="visitor-records-pager" aria-label="Quiz leaderboard pagination" hidden>
            <button type="button" id="leaderboardPrev" class="visitor-records-pager__btn">Prev</button>
            <span id="leaderboardPageText" class="visitor-records-pager__text">Page 1 of 1</span>
            <button type="button" id="leaderboardNext" class="visitor-records-pager__btn">Next</button>
        </nav>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const charts = @json($statistics['charts'] ?? []);
        const analyticsByLandmark = @json($statistics['analytics_by_landmark'] ?? []);
        const visitorRecords = @json($statistics['visitor_records'] ?? []);
        const leaderboardByLandmark = @json($statistics['leaderboard_by_landmark'] ?? ['all' => ($statistics['leaderboard'] ?? [])]);
        const leaderboardPerPage = 5;
        let leaderboardPage = 1;
        let visitsPerLandmarkChart = null;
        const periods = {
            daily: { description: 'Visits during the last 7 days', color: '#7A2E1F' },
            weekly: { description: 'Visits during the last 8 weeks', color: '#B66B30' },
            monthly: { description: 'Visits during the last 12 months', color: '#D49A35' },
            yearly: { description: 'Visits during the last 5 years', color: '#5F3B32' },
            
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

        function renderVisitsPerLandmarkChart() {
            const period = document.getElementById('visitsPerLandmarkPeriod').value || '7';
            const display = document.getElementById('visitsPerLandmarkDisplay').value || '10';
            const cutoff = period === 'all' ? null : Date.now() - (Number(period) * 24 * 60 * 60 * 1000);
            const totals = new Map();
            visitorRecords.forEach(function (record) {
                const lastVisit = Date.parse(record.last_visit_at || '');
                if (cutoff !== null && (!Number.isFinite(lastVisit) || lastVisit < cutoff)) {
                    return;
                }

                const landmark = String(record.landmark || 'Unknown landmark');
                const count = Number(record.visit_count || 0);
                totals.set(landmark, (totals.get(landmark) || 0) + count);
            });

            let rows = Array.from(totals.entries()).sort(function (a, b) {
                return b[1] - a[1] || a[0].localeCompare(b[0]);
            });
            if (display !== 'all') {
                rows = rows.slice(0, Number(display));
            }

            const wrap = document.getElementById('visitsPerLandmarkCanvasWrap');
            const scroller = document.getElementById('visitsPerLandmarkScroller');
            const minWidth = display === 'all'
                ? Math.max(scroller.clientWidth, rows.length * 130)
                : scroller.clientWidth;
            wrap.style.width = minWidth + 'px';

            if (visitsPerLandmarkChart) {
                visitsPerLandmarkChart.data.labels = rows.map(function (row) { return row[0]; });
                visitsPerLandmarkChart.data.datasets[0].data = rows.map(function (row) { return row[1]; });
                visitsPerLandmarkChart.resize();
                visitsPerLandmarkChart.update();

                return;
            }

            visitsPerLandmarkChart = new Chart(document.getElementById('visitsPerLandmarkChart'), {
                type: 'bar',
                data: {
                    labels: rows.map(function (row) { return row[0]; }),
                    datasets: [{
                        label: 'Visit Count',
                        data: rows.map(function (row) { return row[1]; }),
                        backgroundColor: '#E8B34B',
                        borderColor: '#7A2E1F',
                        borderWidth: 1,
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                title: function (items) {
                                    return items.length ? items[0].label : '';
                                },
                                label: function (context) {
                                    return 'Visits: ' + formatNumber(context.parsed.y);
                                }
                            }
                        }
                    },
                    scales: {
                        x: { title: { display: true, text: 'Landmark Visited' }, grid: { display: false } },
                        y: { beginAtZero: true, title: { display: true, text: 'Visit Count' }, ticks: { precision: 0 } }
                    }
                }
            });
        }

        function renderLeaderboard() {
            const landmarkId = document.getElementById('leaderboardLandmark').value || 'all';
            const rows = (leaderboardByLandmark[landmarkId] || []).slice().sort(function (a, b) {
                const scoreDiff = Number(b.sort_total_score || 0) - Number(a.sort_total_score || 0);
                if (scoreDiff !== 0) {
                    return scoreDiff;
                }

                return Date.parse(b.completed_at || '') - Date.parse(a.completed_at || '');
            });
            const tbody = document.getElementById('leaderboardRows');
            const pager = document.getElementById('leaderboardPager');
            const prev = document.getElementById('leaderboardPrev');
            const next = document.getElementById('leaderboardNext');
            const pageText = document.getElementById('leaderboardPageText');
            const totalPages = Math.max(1, Math.ceil(rows.length / leaderboardPerPage));
            leaderboardPage = Math.min(Math.max(1, leaderboardPage), totalPages);
            tbody.replaceChildren();

            if (rows.length === 0) {
                const row = document.createElement('tr');
                row.innerHTML = '<td colspan="5" class="empty-state">No completed quizzes have been recorded for this landmark yet.</td>';
                tbody.appendChild(row);
                pager.hidden = true;
                return;
            }

            const start = (leaderboardPage - 1) * leaderboardPerPage;
            rows.slice(start, start + leaderboardPerPage).forEach(function (entry, index) {
                const row = document.createElement('tr');
                row.innerHTML = '<td><span class="rank">' + (start + index + 1) + '</span></td>'
                    + '<td>' + escapeHtml(entry.visitor_name) + '</td>'
                    + '<td>' + escapeHtml(entry.landmark) + '</td>'
                    + '<td><span class="score">' + escapeHtml(entry.total_score) + '</span></td>'
                    + '<td>' + escapeHtml(entry.completed_at_label) + '</td>';
                tbody.appendChild(row);
            });

            pager.hidden = false;
            pageText.textContent = 'Page ' + leaderboardPage + ' of ' + totalPages;
            prev.disabled = leaderboardPage <= 1;
            next.disabled = leaderboardPage >= totalPages;
        }

        function setupLandmarkDropdown(selectId, toggleId, menuId) {
            const select = document.getElementById(selectId);
            const toggle = document.getElementById(toggleId);
            const menu = document.getElementById(menuId);

            function closeDropdown() {
                menu.hidden = true;
                menu.classList.remove('down', 'up');
                toggle.setAttribute('aria-expanded', 'false');
            }

            function openDropdown() {
                const viewport = window.visualViewport;
                const viewportTop = viewport ? viewport.offsetTop : 0;
                const viewportLeft = viewport ? viewport.offsetLeft : 0;
                const viewportBottom = viewportTop + (viewport ? viewport.height : window.innerHeight);
                const viewportRight = viewportLeft + (viewport ? viewport.width : document.documentElement.clientWidth);
                const viewportPadding = 8;
                const menuGap = 4;
                const maxMenuHeight = 322; // Ten 32px options plus the 1px top and bottom borders.
                const toggleRect = toggle.getBoundingClientRect();
                const spaceBelow = Math.max(0, viewportBottom - toggleRect.bottom - menuGap - viewportPadding);
                const spaceAbove = Math.max(0, toggleRect.top - viewportTop - menuGap - viewportPadding);

                menu.classList.remove('down', 'up');
                menu.style.maxHeight = maxMenuHeight + 'px';
                menu.style.top = '0';
                menu.style.width = toggleRect.width + 'px';
                menu.hidden = false;

                const desiredHeight = Math.min(menu.scrollHeight + 2, maxMenuHeight);
                const opensUp = spaceBelow < desiredHeight && spaceAbove > spaceBelow;
                const availableSpace = opensUp ? spaceAbove : spaceBelow;
                menu.classList.add(opensUp ? 'up' : 'down');
                menu.style.maxHeight = Math.min(maxMenuHeight, availableSpace) + 'px';

                const menuHeight = menu.getBoundingClientRect().height;
                const menuTop = opensUp
                    ? toggleRect.top - menuGap - menuHeight
                    : toggleRect.bottom + menuGap;
                const maxLeft = viewportRight - viewportPadding - toggleRect.width;
                menu.style.top = Math.max(viewportTop + viewportPadding, menuTop) + 'px';
                menu.style.left = Math.max(viewportLeft + viewportPadding, Math.min(toggleRect.left, maxLeft)) + 'px';
                toggle.setAttribute('aria-expanded', 'true');

                const selected = menu.querySelector('[aria-selected="true"]');
                if (selected) {
                    const selectedTop = selected.offsetTop;
                    const selectedBottom = selectedTop + selected.offsetHeight;
                    if (selectedTop < menu.scrollTop) {
                        menu.scrollTop = selectedTop;
                    } else if (selectedBottom > menu.scrollTop + menu.clientHeight) {
                        menu.scrollTop = selectedBottom - menu.clientHeight;
                    }
                }
            }

            Array.from(select.options).forEach(function (option) {
                const item = document.createElement('li');
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'leaderboard-landmark-select__option';
                button.setAttribute('role', 'option');
                button.setAttribute('aria-selected', option.selected ? 'true' : 'false');
                button.textContent = option.textContent;
                button.addEventListener('click', function () {
                    select.value = option.value;
                    toggle.textContent = option.textContent;
                    menu.querySelectorAll('[role="option"]').forEach(function (entry) {
                        entry.setAttribute('aria-selected', entry === button ? 'true' : 'false');
                    });
                    closeDropdown();
                    select.dispatchEvent(new Event('change'));
                    toggle.focus();
                });
                item.appendChild(button);
                menu.appendChild(item);
            });
            document.body.appendChild(menu);

            toggle.addEventListener('click', function () {
                menu.hidden ? openDropdown() : closeDropdown();
            });
            document.addEventListener('click', function (event) {
                if (!toggle.parentElement.contains(event.target) && !menu.contains(event.target)) {
                    closeDropdown();
                }
            });
            toggle.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeDropdown();
                } else if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openDropdown();
                    const selected = menu.querySelector('[aria-selected="true"]');
                    (selected || menu.querySelector('[role="option"]'))?.focus({ preventScroll: true });
                }
            });
            menu.addEventListener('keydown', function (event) {
                const options = Array.from(menu.querySelectorAll('[role="option"]'));
                const currentIndex = options.indexOf(document.activeElement);
                if (event.key === 'Escape') {
                    closeDropdown();
                    toggle.focus();
                } else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                    event.preventDefault();
                    const direction = event.key === 'ArrowDown' ? 1 : -1;
                    options[(currentIndex + direction + options.length) % options.length]?.focus();
                } else if (event.key === 'Home' || event.key === 'End') {
                    event.preventDefault();
                    options[event.key === 'Home' ? 0 : options.length - 1]?.focus();
                }
            });
            window.addEventListener('resize', closeDropdown);
            window.addEventListener('scroll', function (event) {
                if (!menu.contains(event.target)) {
                    closeDropdown();
                }
            }, true);
        }

        setupLandmarkDropdown('visitorAnalyticsLandmark', 'visitorAnalyticsLandmarkToggle', 'visitorAnalyticsLandmarkOptions');
        setupLandmarkDropdown('leaderboardLandmark', 'leaderboardLandmarkToggle', 'leaderboardLandmarkOptions');

        document.getElementById('visitorAnalyticsPeriod').addEventListener('change', updateVisitorAnalytics);
        document.getElementById('visitorAnalyticsLandmark').addEventListener('change', updateVisitorAnalytics);
        document.getElementById('visitsPerLandmarkPeriod').addEventListener('change', renderVisitsPerLandmarkChart);
        document.getElementById('visitsPerLandmarkDisplay').addEventListener('change', renderVisitsPerLandmarkChart);
        window.addEventListener('resize', renderVisitsPerLandmarkChart);
        document.getElementById('leaderboardPrev').addEventListener('click', function () {
            leaderboardPage--;
            renderLeaderboard();
        });
        document.getElementById('leaderboardNext').addEventListener('click', function () {
            leaderboardPage++;
            renderLeaderboard();
        });
        document.getElementById('leaderboardLandmark').addEventListener('change', function () {
            leaderboardPage = 1;
            renderLeaderboard();
        });
        updateVisitorAnalytics();
        renderVisitsPerLandmarkChart();
        renderLeaderboard();
    });
</script>
@endsection
