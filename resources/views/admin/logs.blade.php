@extends('layouts.sidebar')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2 style="font-size: 1.75rem; font-weight: bold;">📋 System Logs</h2>

        
        <form action="{{ route('admin.logs.clear') }}" method="POST"
              onsubmit="return confirm('⚠️ Are you sure you want to clear all logs? This cannot be undone.');">
            @csrf
            @method('DELETE')
            <button type="submit"
                    style="background: #ef4444; color: white; padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer;">
                🗑️ Clear Logs
            </button>
        </form>
    </div>

    @if(session('status'))
        <p style="color: green; margin-bottom: 1rem;">{{ session('status') }}</p>
    @endif

    @if(count($logs) === 0)
        <p style="color: #6b7280;">No logs found.</p>
    @else
        <table style="width: 100%; border-collapse: collapse; background: white; box-shadow: 0 4px 8px rgba(0,0,0,0.05); border-radius: 8px; overflow: hidden;">
            <thead style="background: #f3f4f6; text-align: left;">
                <tr>
                    <th style="padding: 12px;">Timestamp</th>
                    <th style="padding: 12px;">Email</th>
                    <th style="padding: 12px;">Role</th>
                    <th style="padding: 12px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($logs as $log)
                    @php
                        $data = $log->data();
                        $email = $data['email'] ?? '—';
                        $timestamp = $data['timestamp'] ?? '—';
                        $action = preg_replace('/\s*\(auto-QR:\s*LM-[a-f0-9]{6}\)/i', '', (string)($data['action'] ?? '—'));
                        $role = $userRoles[$email] ?? 'N/A';
                    @endphp

                    <tr style="border-top: 1px solid #e5e7eb;">
                        <td style="padding: 12px;">{{ $timestamp }}</td>
                        <td style="padding: 12px;">{{ $email }}</td>
                        <td style="padding: 12px;">{{ $role }}</td>
                        <td style="padding: 12px;">{{ $action }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
