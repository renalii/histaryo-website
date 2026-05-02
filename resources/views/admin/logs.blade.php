@extends('layouts.sidebar')

@section('content')
    <style>
        .logs-wrap { max-width: 2000px; margin: 0 auto; }
        .logs-top { display: flex; justify-content: space-between; align-items: center; gap: .8rem; flex-wrap: wrap; margin-bottom: .7rem; }
        .logs-title { font-size: 1.9rem; font-weight: 800; margin: 0; color: #7A2E1F; }
        .logs-sub { color: #6b7280; margin: 0 0 1rem 0; font-size: .95rem; }
        .logs-clear {
            background: #ef4444;
            color: #fff;
            padding: .55rem .9rem;
            border-radius: 8px;
            border: 1px solid #fecaca;
            font-weight: 700;
            cursor: pointer;
            transition: all .15s ease;
        }
        .logs-clear:hover { background: #dc2626; transform: translateY(-1px); }
        .logs-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 1rem;
        }
        .logs-modal-backdrop.show { display: flex; }
        .logs-modal {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border: 1px solid #eceff3;
            border-radius: 14px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.2);
            padding: 1rem 1rem .9rem;
        }
        .logs-modal-title {
            margin: 0 0 .45rem 0;
            color: #7A2E1F;
            font-size: 1.2rem;
            font-weight: 800;
        }
        .logs-modal-message {
            margin: 0;
            color: #374151;
            line-height: 1.45;
            font-size: .95rem;
        }
        .logs-modal-actions {
            margin-top: 1rem;
            display: flex;
            justify-content: flex-end;
            gap: .55rem;
        }
        .logs-modal-btn {
            border-radius: 8px;
            padding: .5rem .9rem;
            font-weight: 700;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all .15s ease;
        }
        .logs-modal-btn.cancel {
            background: #f3f4f6;
            border-color: #e5e7eb;
            color: #374151;
        }
        .logs-modal-btn.cancel:hover { background: #e5e7eb; }
        .logs-modal-btn.confirm {
            background: #ef4444;
            border-color: #fecaca;
            color: #fff;
        }
        .logs-modal-btn.confirm:hover { background: #dc2626; }
        .logs-status {
            color: #166534;
            margin-bottom: .8rem;
            background: #ecfdf5;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            padding: .7rem .9rem;
        }
        .logs-empty {
            color: #6b7280;
            background: #fff;
            border: 1px dashed #d1d5db;
            border-radius: 10px;
            padding: .9rem 1rem;
        }
        .logs-card {
            background: #fff;
            border: 1px solid #eceff3;
            border-radius: 12px;
            box-shadow: 0 6px 16px rgba(15,23,42,.05);
            overflow: auto;
        }
        .logs-table { width: 100%; border-collapse: separate; border-spacing: 0; min-width: 760px; }
        .logs-table th {
            padding: 12px;
            text-align: left;
            background: #fff7ed;
            color: #7A2E1F;
            font-size: .9rem;
            border-bottom: 1px solid #eef2f7;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .logs-table td {
            padding: 12px;
            border-bottom: 1px solid #eef2f7;
            color: #374151;
            font-size: .92rem;
            vertical-align: middle;
        }
        .logs-table tbody tr:hover { background: #fcfcfd; }
        .role-pill {
            display: inline-flex;
            align-items: center;
            padding: .18rem .52rem;
            border-radius: 999px;
            font-size: .78rem;
            font-weight: 700;
            text-transform: capitalize;
            border: 1px solid transparent;
        }
        .role-admin { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
        .role-curator { background: #ecfdf5; color: #166534; border-color: #bbf7d0; }
        .role-na { background: #f3f4f6; color: #6b7280; border-color: #e5e7eb; }
        .mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; color: #6b7280; font-size: .84rem; }
    </style>
    <div class="logs-wrap">
    <div class="logs-top">
        <h2 class="logs-title">System Logs</h2>

        
        <form id="clearLogsForm" action="{{ route('admin.logs.clear') }}" method="POST">
            @csrf
            @method('DELETE')
            <button type="button" id="openClearLogsModal" class="logs-clear">
                Clear Logs
            </button>
        </form>
    </div>
    <p class="logs-sub">{{ count($logs) }} log entr{{ count($logs) === 1 ? 'y' : 'ies' }}</p>

    @if(session('status'))
        <p class="logs-status">{{ session('status') }}</p>
    @endif

    @if(count($logs) === 0)
        <p class="logs-empty">No logs found.</p>
    @else
        <div class="logs-card">
        <table class="logs-table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Action</th>
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

                    <tr>
                        <td class="mono">{{ $timestamp }}</td>
                        <td>{{ $email }}</td>
                        <td>
                            <span class="role-pill {{ $role === 'admin' ? 'role-admin' : ($role === 'curator' ? 'role-curator' : 'role-na') }}">
                                {{ $role }}
                            </span>
                        </td>
                        <td>{{ $action }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    @endif
    </div>

    <div id="clearLogsModal" class="logs-modal-backdrop" aria-hidden="true">
        <div class="logs-modal" role="dialog" aria-modal="true" aria-labelledby="clearLogsModalTitle">
            <h3 id="clearLogsModalTitle" class="logs-modal-title">Clear Logs</h3>
            <p class="logs-modal-message">Are you sure you want to clear all logs? This action cannot be undone.</p>
            <div class="logs-modal-actions">
                <button type="button" id="cancelClearLogs" class="logs-modal-btn cancel">Cancel</button>
                <button type="button" id="confirmClearLogs" class="logs-modal-btn confirm">Confirm</button>
            </div>
        </div>
    </div>

    <script>
        const clearLogsModal = document.getElementById('clearLogsModal');
        const openClearLogsModal = document.getElementById('openClearLogsModal');
        const cancelClearLogs = document.getElementById('cancelClearLogs');
        const confirmClearLogs = document.getElementById('confirmClearLogs');
        const clearLogsForm = document.getElementById('clearLogsForm');

        function closeClearLogsModal() {
            clearLogsModal.classList.remove('show');
            clearLogsModal.setAttribute('aria-hidden', 'true');
        }

        openClearLogsModal.addEventListener('click', function () {
            clearLogsModal.classList.add('show');
            clearLogsModal.setAttribute('aria-hidden', 'false');
        });

        cancelClearLogs.addEventListener('click', closeClearLogsModal);

        clearLogsModal.addEventListener('click', function (event) {
            if (event.target === clearLogsModal) {
                closeClearLogsModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && clearLogsModal.classList.contains('show')) {
                closeClearLogsModal();
            }
        });

        confirmClearLogs.addEventListener('click', function () {
            clearLogsForm.submit();
        });
    </script>
@endsection
