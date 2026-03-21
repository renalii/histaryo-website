@extends('layouts.sidebar')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2 style="font-size: 1.75rem; font-weight: bold;">📊 Reports</h2>
    </div>

    <p style="color: #6b7280; margin-bottom: 1rem;">
        Export system data (users, landmarks, visits, trivia engagement) as PDF or Excel for offline analysis.
    </p>

    
    <div style="background: white; padding: 1rem; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.05);">
        <form action="{{ route('admin.reports.export', 'custom') }}" method="GET" style="margin-bottom: 1rem;">
            <label for="reportType" style="font-weight: bold; display: block; margin-bottom: 0.5rem;">Choose Report Type:</label>
            <select name="type" id="reportType"
                    style="padding: 8px; border-radius: 6px; border: 1px solid #d1d5db; width: 100%; margin-bottom: 1rem;">
                <option value="users">👤 Users</option>
                <option value="landmarks">🏞️ Landmarks</option>
                <option value="visits">📈 Visits / Logs</option>
                <option value="trivia">🎯 Trivia Engagement</option>
            </select>

            <label for="format" style="font-weight: bold; display: block; margin-bottom: 0.5rem;">Export Format:</label>
            <select name="format" id="format"
                    style="padding: 8px; border-radius: 6px; border: 1px solid #d1d5db; width: 100%; margin-bottom: 1rem;">
                <option value="pdf">📄 PDF</option>
                <option value="excel">📊 Excel</option>
            </select>

            <label for="dateRange" style="font-weight: bold; display: block; margin-bottom: 0.5rem;">Date Range (optional):</label>
            <input type="date" name="from" style="padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; margin-right: 0.5rem;">
            <input type="date" name="to" style="padding: 8px; border: 1px solid #d1d5db; border-radius: 6px;">

            <div style="margin-top: 1rem;">
                <button type="submit"
                        style="background: #2563eb; color: white; padding: 10px 18px; border-radius: 6px; border: none; cursor: pointer;">
                    📥 Export Report
                </button>
            </div>
        </form>
    </div>

    @if(session('status'))
        <p style="color: green; margin-top: 1rem;">{{ session('status') }}</p>
    @endif
@endsection
