@extends('layouts.sidebar')

@section('content')
    <style>
        .reports-wrap { max-width: 980px; }
        .reports-title { font-size: 1.9rem; font-weight: 800; margin: 0 0 .3rem 0; color: #7A2E1F; }
        .reports-sub { color: #6b7280; margin-bottom: 1rem; }
        .reports-card {
            background: #fff;
            border: 1px solid #eceff3;
            border-radius: 12px;
            padding: 1rem;
            box-shadow: 0 6px 16px rgba(0,0,0,0.05);
        }
        .reports-grid { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap: .8rem; }
        .field { grid-column: span 12; }
        .field-half { grid-column: span 6; }
        .label { font-weight: 700; display: block; margin-bottom: .38rem; color: #374151; }
        .input {
            width: 100%;
            padding: .58rem .7rem;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            background: #fff;
        }
        select.input {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: none;
            padding-right: .7rem;
        }
        select.input::-ms-expand {
            display: none;
        }
        input[type="date"].input::-webkit-calendar-picker-indicator {
            opacity: 0;
            width: 0;
            height: 0;
            display: none;
        }
        .input:focus {
            outline: none;
            border-color: #E8B34B;
            box-shadow: 0 0 0 3px rgba(232, 179, 75, 0.25);
        }
        .hint { color: #6b7280; font-size: .85rem; margin-top: .35rem; }
        .actions { margin-top: .2rem; display: flex; gap: .5rem; flex-wrap: wrap; }
        .btn-export {
            background: #E8B34B;
            color: #7A2E1F;
            border: 1px solid #F3C96A;
            padding: .62rem 1rem;
            border-radius: 9px;
            font-weight: 800;
            cursor: pointer;
            transition: all .15s ease;
        }
        .btn-export:hover { background: #F3C96A; transform: translateY(-1px); }
        .status-ok {
            color: #166534;
            margin-top: .8rem;
            background: #ecfdf5;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            padding: .7rem .9rem;
        }
        @media (max-width: 720px) {
            .field-half { grid-column: span 12; }
        }
    </style>

    <div class="reports-wrap">
        <h2 class="reports-title">Reports</h2>
        <p class="reports-sub">Export users, landmarks, visits/logs, and trivia engagement as PDF or Excel for offline analysis.</p>

    <div class="reports-card">
        <form action="{{ route('admin.reports.export', 'custom') }}" method="GET" style="margin-bottom: 0;">
            <div class="reports-grid">
            <div class="field">
            <label for="reportType" class="label">Choose Report Type</label>
            <select name="type" id="reportType" class="input">
                <option value="users">Users</option>
                <option value="landmarks">Landmarks</option>
                <option value="visits">Visits / Logs</option>
                <option value="trivia">Trivia Engagement</option>
            </select>
            </div>

            <div class="field">
            <label for="format" class="label">Export Format</label>
            <select name="format" id="format" class="input">
                <option value="pdf">PDF</option>
                <option value="excel">Excel</option>
            </select>
            </div>

            <div class="field">
                <label class="label">Date Range (Optional)</label>
                <div class="reports-grid" style="gap:.6rem;">
                    <div class="field-half">
                        <input type="date" name="from" value="{{ request('from') }}" class="input">
                    </div>
                    <div class="field-half">
                        <input type="date" name="to" value="{{ request('to') }}" class="input">
                    </div>
                </div>
                <p class="hint">Leave both empty to export all available dates.</p>
            </div>

            <div class="field">
            <div class="actions">
                <button type="submit" class="btn-export">
                    Export Report
                </button>
            </div>
            </div>
            </div>
        </form>
    </div>

    @if(session('status'))
        <p class="status-ok">{{ session('status') }}</p>
    @endif
    </div>
@endsection
