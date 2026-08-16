@extends('layouts.sidebar')

@section('content')
@php
    use Carbon\Carbon;

    $landmark = $assignedLandmark ?? [];
    $statistics = $visitorStatistics ?? [];
    $today = Carbon::now()->format('F j, Y');
    $email = session('email');
    $curatorName = session('name') ?? ($email ? ucfirst(explode('@', $email)[0]) : 'Curator');
    $assignedLandmarkName = trim((string) ($landmark['name'] ?? ''));
@endphp

<style>
    .curator-dashboard { max-width: 1800px; min-height: 1050px; margin: 0 auto; color: #374151; }
    .curator-hero {
        background: linear-gradient(135deg, #7A2E1F, #E8B34B);
        color: #fffdf7;
        padding: 1.8rem 2rem;
        border-radius: 1.25rem;
        margin-bottom: 1.25rem;
        box-shadow: 0 12px 28px rgba(122, 46, 31, .22);
    }
    .curator-hero-date {
        margin: 0 0 .35rem;
        font-size: .86rem;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        opacity: .9;
    }
    .curator-hero h1 { margin: 0; font-size: 2rem; line-height: 1.2; font-weight: 800; }
    .curator-hero-copy { margin: .5rem 0 0; font-size: 1rem; font-weight: 500; opacity: .95; }
    .curator-hero-landmark { margin: .35rem 0 0; font-size: .9rem; opacity: .9; }
    .curator-section-title { margin: 1.3rem 0 .7rem; color: #3f261f; font-size: 1.1rem; font-weight: 800; }
    .curator-card {
        background: #fff;
        border: 1px solid #eceff3;
        border-radius: 14px;
        box-shadow: 0 6px 18px rgba(0,0,0,.05);
    }
    .assigned-card {
        display: grid;
        grid-template-columns: minmax(0, 2fr) repeat(2, minmax(150px, 1fr));
        gap: 1rem;
        align-items: center;
        padding: 1.25rem 1.4rem;
        border-top: 4px solid #E8B34B;
    }
    .assigned-label { color: #6b7280; font-size: .82rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
    .assigned-name { margin: .35rem 0 0; color: #7A2E1F; font-size: 1.55rem; font-weight: 800; }
    .assigned-value { margin: .35rem 0 0; color: #111827; font-size: 1.15rem; font-weight: 800; }
    .status-badge { display: inline-flex; padding: .35rem .7rem; border-radius: 999px; background: #fff5dd; color: #7A2E1F; font-size: .85rem; font-weight: 800; }
    .analytics-card, .leaderboard-card { padding: 1.15rem; }
    .analytics-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }
    .analytics-header h2, .leaderboard-card h2 { margin: 0; color: #3f261f; font-size: 1.1rem; }
    .analytics-header p, .leaderboard-card > p { margin: .3rem 0 0; color: #6b7280; font-size: .86rem; }
    .analytics-filter { display: flex; align-items: center; gap: .55rem; flex-shrink: 0; }
    .analytics-filter label { color: #6b7280; font-size: .85rem; font-weight: 700; }
    .analytics-filter select { border: 1px solid #d9dee7; border-radius: 9px; background: #fffdf7; color: #3f261f; padding: .55rem 2rem .55rem .7rem; font: inherit; font-size: .88rem; font-weight: 700; cursor: pointer; }
    .analytics-filter select:focus { outline: none; border-color: #d1d5db; box-shadow: none; }
    .curator-period-select { position: relative; width: 110px; height: 40px; }
    .curator-period-select > select { display: none; }
    .curator-period-select__field {
        box-sizing: border-box;
        width: 100%;
        height: 40px;
        padding: 0 2.25rem 0 .7rem;
        border: 1px solid #d9dee7;
        border-radius: 9px;
        background: #fffdf7;
        color: #3f261f;
        font: inherit;
        font-size: .88rem;
        font-weight: 700;
        text-align: left;
        cursor: pointer;
    }
    .curator-period-select__field:focus,
    .curator-period-select__field:focus-visible { outline: none; border-color: #d1d5db; box-shadow: none; }
    .curator-period-select__arrow {
        position: absolute;
        z-index: 1;
        top: 1px;
        right: 1px;
        width: 2.5rem;
        height: 38px;
        padding: 0;
        border: 0;
        border-radius: 0 8px 8px 0;
        background: transparent;
        color: #3f261f;
        cursor: pointer;
    }
    .curator-period-select__arrow::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: .45rem;
        height: .45rem;
        border-right: 2px solid currentColor;
        border-bottom: 2px solid currentColor;
        transform: translate(-50%, -70%) rotate(45deg);
    }
    .curator-period-select.is-open .curator-period-select__arrow::after {
        transform: translate(-50%, -30%) rotate(225deg);
    }
    .curator-period-select__arrow:focus-visible { outline: 2px solid #9ca3af; outline-offset: -3px; }
    .curator-period-select__menu {
        position: fixed;
        z-index: 10000;
        box-sizing: border-box;
        max-height: 280px;
        margin: 0;
        padding: 0;
        overflow-x: hidden;
        overflow-y: auto;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 8px 18px rgba(0, 0, 0, .14);
        list-style: none;
    }
    .curator-period-select__menu[hidden] { display: none; }
    .curator-period-select__option {
        display: flex;
        align-items: center;
        box-sizing: border-box;
        width: 100%;
        height: 40px;
        padding: 0 12px;
        border: 0;
        background: #fff;
        color: #111827;
        font: inherit;
        font-size: 14px;
        text-align: left;
        white-space: nowrap;
        cursor: pointer;
    }
    .curator-period-select__option:hover,
    .curator-period-select__option:focus,
    .curator-period-select__option[aria-selected="true"] { background: #f8fafc; outline: none; }
    .chart-wrap { position: relative; height: 330px; }
    .leaderboard-card { margin-bottom: 2rem; overflow: hidden; }
    .table-wrap { margin-top: .9rem; overflow-x: auto; }
    .leaderboard-table { width: 100%; min-width: 620px; border-collapse: collapse; }
    .leaderboard-table th, .leaderboard-table td { padding: .85rem .7rem; text-align: left; border-bottom: 1px solid #eceff3; }
    .leaderboard-table th { color: #6b7280; font-size: .75rem; letter-spacing: .04em; text-transform: uppercase; }
    .leaderboard-table td { color: #374151; font-size: .9rem; }
    .leaderboard-table tbody tr:last-child td { border-bottom: 0; }
    .rank { width: 2rem; height: 2rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; background: #fff5dd; color: #7A2E1F; font-weight: 800; }
    .score { color: #7A2E1F; font-weight: 800; }
    .empty-state { padding: 2.5rem 1rem !important; text-align: center !important; color: #6b7280 !important; }
    @media (max-width: 1024px) {
        .assigned-card { grid-template-columns: 1fr 1fr; }
        .assigned-card > :first-child { grid-column: span 2; }
    }
    @media (max-width: 640px) {
        .curator-hero { padding: 1.3rem 1.15rem; }
        .curator-hero h1 { font-size: 1.55rem; }
        .assigned-card { grid-template-columns: 1fr; }
        .assigned-card > :first-child { grid-column: span 1; }
        .analytics-header { align-items: stretch; flex-direction: column; }
        .analytics-filter { justify-content: space-between; }
        .analytics-filter select { flex: 1; }
        .chart-wrap { height: 250px; }
    }
</style>

<div class="curator-dashboard">
    <section class="curator-hero">
        <p class="curator-hero-date">{{ $today }}</p>
        <h1>Welcome back, {{ $curatorName }}!</h1>
        <p class="curator-hero-copy">You manage content for your assigned landmark.</p>

    </section>

    <h2 class="curator-section-title">Assigned Landmark</h2>
    <section class="curator-card assigned-card">
        <div>
            <div class="assigned-label">Landmark Name</div>
            <p class="assigned-name">{{ $landmark['name'] ?? 'Assigned landmark' }}</p>
        </div>
        <div>
            <div class="assigned-label">Status</div>
            <p class="assigned-value"><span class="status-badge">{{ $landmark['status'] ?? 'Unavailable' }}</span></p>
        </div>
        <div>
            <div class="assigned-label">Total Visitors</div>
            <p class="assigned-value">{{ number_format($landmark['total_visitors'] ?? 0) }}</p>
        </div>
    </section>

    <h2 class="curator-section-title">Visitor Analytics</h2>
    <section class="curator-card analytics-card">
        <div class="analytics-header">
            <div>
                <h2>Visitor Activity Trend</h2>
                <p id="visitorAnalyticsDescription">Visits during the last 7 days</p>
            </div>
            <div class="analytics-filter">
                <label for="visitorAnalyticsPeriod">Period</label>
                <div class="curator-period-select">
                    <select id="visitorAnalyticsPeriod">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                    <button type="button"
                            class="curator-period-select__field"
                            aria-haspopup="listbox"
                            aria-controls="visitorAnalyticsPeriodOptions"
                            aria-expanded="false">Daily</button>
                    <button type="button"
                            class="curator-period-select__arrow"
                            aria-label="Toggle period options"
                            aria-controls="visitorAnalyticsPeriodOptions"
                            aria-expanded="false"></button>
                    <ul id="visitorAnalyticsPeriodOptions" class="curator-period-select__menu" role="listbox" hidden></ul>
                </div>
            </div>
        </div>
        <div class="chart-wrap">
            <canvas id="visitorAnalyticsChart"></canvas>
        </div>
    </section>

    <h2 class="curator-section-title">Quiz Leaderboard</h2>
    <section class="curator-card leaderboard-card">
        <h2>Top Visitor Scores</h2>
        <p>Highest quiz scores achieved at {{ $landmark['name'] ?? 'your assigned landmark' }}.</p>
        <div class="table-wrap">
            <table class="leaderboard-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Visitor Name</th>
                        <th>Score</th>
                        <th>Date Achieved</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse (($statistics['leaderboard'] ?? []) as $entry)
                        <tr>
                            <td><span class="rank">{{ $loop->iteration }}</span></td>
                            <td>{{ $entry['visitor_name'] }}</td>
                            <td><span class="score">{{ ($entry['total_score'] ?? '') !== '' ? $entry['total_score'] : '0' }}</span></td>
                            <td>{{ $entry['completed_at_label'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="empty-state">No completed quizzes have been recorded for this landmark yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
<script>
    console.debug('Curator dashboard quiz-result query', {
        landmark_id: @json($landmark['id'] ?? null),
        result_count: @json(count($statistics['leaderboard'] ?? [])),
    });

    document.addEventListener('DOMContentLoaded', function () {
        const periodSelect = document.getElementById('visitorAnalyticsPeriod');
        const periodRoot = periodSelect.closest('.curator-period-select');
        const periodField = periodRoot.querySelector('.curator-period-select__field');
        const periodArrow = periodRoot.querySelector('.curator-period-select__arrow');
        const periodMenu = periodRoot.querySelector('.curator-period-select__menu');
        let periodOpen = false;

        function closePeriodDropdown() {
            periodOpen = false;
            periodMenu.hidden = true;
            periodRoot.classList.remove('is-open');
            periodField.setAttribute('aria-expanded', 'false');
            periodArrow.setAttribute('aria-expanded', 'false');
        }

        function openPeriodDropdown() {
            document.dispatchEvent(new CustomEvent('curator-dropdown:open', { detail: periodRoot }));
            periodOpen = true;
            const rect = periodField.getBoundingClientRect();
            const gap = 4;
            const padding = 8;
            periodMenu.style.width = rect.width + 'px';
            periodMenu.style.left = Math.max(padding, Math.min(rect.left, window.innerWidth - padding - rect.width)) + 'px';
            periodMenu.style.top = rect.bottom + gap + 'px';
            periodMenu.hidden = false;
            periodRoot.classList.add('is-open');
            periodField.setAttribute('aria-expanded', 'true');
            periodArrow.setAttribute('aria-expanded', 'true');
        }

        Array.from(periodSelect.options).forEach(function (nativeOption) {
            const item = document.createElement('li');
            const option = document.createElement('button');
            option.type = 'button';
            option.className = 'curator-period-select__option';
            option.setAttribute('role', 'option');
            option.setAttribute('aria-selected', nativeOption.selected ? 'true' : 'false');
            option.textContent = nativeOption.textContent;
            option.addEventListener('click', function () {
                periodSelect.value = nativeOption.value;
                periodField.textContent = nativeOption.textContent;
                periodMenu.querySelectorAll('[role="option"]').forEach(function (entry) {
                    entry.setAttribute('aria-selected', entry === option ? 'true' : 'false');
                });
                closePeriodDropdown();
                periodSelect.dispatchEvent(new Event('change', { bubbles: true }));
                periodField.focus();
            });
            item.appendChild(option);
            periodMenu.appendChild(item);
        });
        document.body.appendChild(periodMenu);

        periodField.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            if (!periodOpen) openPeriodDropdown();
        });
        periodArrow.addEventListener('mousedown', function (event) {
            event.preventDefault();
            event.stopPropagation();
        });
        periodArrow.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            periodOpen ? closePeriodDropdown() : openPeriodDropdown();
        });
        periodMenu.addEventListener('click', function (event) { event.stopPropagation(); });
        document.addEventListener('curator-dropdown:open', function (event) {
            if (event.detail !== periodRoot) closePeriodDropdown();
        });
        document.addEventListener('click', function (event) {
            if (!periodRoot.contains(event.target) && !periodMenu.contains(event.target)) closePeriodDropdown();
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') closePeriodDropdown();
        });
        window.addEventListener('resize', closePeriodDropdown);
        window.addEventListener('scroll', function (event) {
            if (!periodMenu.contains(event.target)) closePeriodDropdown();
        }, true);

        const charts = @json($statistics['charts'] ?? []);
        const periods = {
            daily: { description: 'Visits during the last 7 days', color: '#7A2E1F' },
            weekly: { description: 'Visits during the last 8 weeks', color: '#B66B30' },
            monthly: { description: 'Visits during the last 12 months', color: '#D49A35' },
            yearly: { description: 'Visits during the last 5 years', color: '#5F3B32' }
        };
        const initial = charts.daily || { labels: [], values: [] };
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

        periodSelect.addEventListener('change', function () {
            const period = periods[this.value] || periods.daily;
            const data = charts[this.value] || { labels: [], values: [] };
            document.getElementById('visitorAnalyticsDescription').textContent = period.description;
            chart.data.labels = data.labels;
            chart.data.datasets[0].data = data.values;
            chart.data.datasets[0].borderColor = period.color;
            chart.data.datasets[0].backgroundColor = `${period.color}1f`;
            chart.update();
        });
    });
</script>
@endsection
