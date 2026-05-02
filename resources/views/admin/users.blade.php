@extends('layouts.sidebar')

@section('content')
    @php
        $panelRoutePrefix = session('role') === 'landmark_manager' ? 'landmarkmanager' : 'admin';
        $curatorsOnly = $curatorsOnly ?? false;
        $usersListRouteName = $curatorsOnly ? 'landmarkmanager.curators' : ($panelRoutePrefix.'.users');
        $userActionsRoutePrefix = $curatorsOnly ? 'landmarkmanager.curators' : ($panelRoutePrefix.'.users');
    @endphp
    <style>
        .users-wrap { max-width: 2000px; margin: 0 auto; }
        .users-title { font-size: 1.9rem; font-weight: 800; margin: 0 0 .25rem 0; color: #7A2E1F; }
        .users-sub { margin: 0 0 1rem 0; color: #6b7280; font-size: .95rem; }
        .users-filter {
            display: flex;
            gap: .6rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
            background: #fff;
            border: 1px solid #eceff3;
            border-radius: 12px;
            padding: .8rem;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
        }
        .users-input, .users-select {
            padding: .6rem .72rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
            color: #111827;
        }
        .users-input { width: 280px; max-width: 100%; }
        .users-input:focus, .users-select:focus {
            outline: none;
            border-color: #E8B34B;
            box-shadow: 0 0 0 3px rgba(232, 179, 75, 0.25);
        }
        .users-btn {
            border: 1px solid transparent;
            border-radius: 8px;
            padding: .6rem .85rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all .15s ease;
            cursor: pointer;
        }
        .users-btn.apply { background: #E8B34B; color: #7A2E1F; border-color: #F3C96A; }
        .users-btn.apply:hover { background: #F3C96A; transform: translateY(-1px); }
        .users-btn.clear { background: #f3f4f6; color: #374151; border-color: #e5e7eb; }
        .users-btn.clear:hover { background: #e5e7eb; }
        .users-table-card {
            background: #fff;
            padding: 1rem;
            border-radius: 12px;
            border: 1px solid #eceff3;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.05);
            overflow-x: auto;
        }
        .users-table { width: 100%; border-collapse: separate; border-spacing: 0; min-width: 980px; }
        .users-table th {
            text-align: left;
            padding: .78rem;
            background: #fff7ed;
            color: #7A2E1F;
            border-bottom: 1px solid #f1f5f9;
            font-size: .92rem;
        }
        .users-table td {
            padding: .78rem;
            border-bottom: 1px solid #eef2f7;
            color: #1f2937;
            vertical-align: middle;
        }
        .users-table tbody tr:hover { background: #fcfcfd; }
        .role-pill {
            display: inline-flex;
            align-items: center;
            padding: .2rem .55rem;
            border-radius: 999px;
            font-size: .78rem;
            font-weight: 700;
            text-transform: capitalize;
            border: 1px solid transparent;
        }
        .role-admin { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
        .role-curator { background: #ecfdf5; color: #166534; border-color: #bbf7d0; }
        .role-visitor { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
        .role-landmark_manager { background: #fef3c7; color: #92400e; border-color: #fde68a; }
        .uid-text { color: #6b7280; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .84rem; }
        .status-pill {
            display: inline-flex;
            align-items: center;
            padding: .2rem .55rem;
            border-radius: 999px;
            font-size: .78rem;
            font-weight: 700;
            text-transform: capitalize;
            border: 1px solid transparent;
        }
        .status-approved { background: #ecfdf5; color: #166534; border-color: #bbf7d0; }
        .status-pending { background: #fffbeb; color: #b45309; border-color: #fde68a; }
        .status-rejected { background: #fef2f2; color: #991b1b; border-color: #fecaca; }
        .user-actions { display: inline-flex; flex-wrap: wrap; gap: .4rem; align-items: center; }
        .user-actions form { display: inline-block; margin: 0; }
        .btn-approve {
            padding: .35rem .65rem;
            border-radius: 8px;
            border: none;
            font-weight: 700;
            font-size: .78rem;
            cursor: pointer;
            background: #166534;
            color: #fff;
        }
        .btn-approve:hover { filter: brightness(1.06); }
        .btn-reject {
            padding: .35rem .65rem;
            border-radius: 8px;
            border: 1px solid #fecaca;
            font-weight: 700;
            font-size: .78rem;
            cursor: pointer;
            background: #fff;
            color: #991b1b;
        }
        .btn-reject:hover { background: #fef2f2; }
        .flash-ok {
            padding: .75rem 1rem;
            border-radius: 10px;
            background: #ecfdf5;
            color: #166534;
            border: 1px solid #bbf7d0;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        .flash-err {
            padding: .75rem 1rem;
            border-radius: 10px;
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        .empty-box {
            color: #6b7280;
            background: #fff;
            border: 1px dashed #d1d5db;
            border-radius: 10px;
            padding: .9rem 1rem;
        }
        @media (max-width: 640px) {
            .users-input { width: 100%; }
            .users-filter { padding: .7rem; }
            .users-btn { flex: 1 1 auto; }
        }
    </style>

    <div class="users-wrap">
    <h2 class="users-title">{{ $curatorsOnly ? 'Curators' : 'All Registered Users' }}</h2>
    <p class="users-sub">
        @if ($curatorsOnly)
            {{ count($users) }} curator{{ count($users) !== 1 ? 's' : '' }} found
        @else
            {{ count($users) }} user{{ count($users) !== 1 ? 's' : '' }} found
        @endif
    </p>

    @if (session('status'))
        <p class="flash-ok">{{ session('status') }}</p>
    @endif
    @if (session('status_err'))
        <p class="flash-err">{{ session('status_err') }}</p>
    @endif

    <form method="GET" action="{{ route($usersListRouteName) }}" class="users-filter">
        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search by email or UID..."
            class="users-input">

        @unless ($curatorsOnly)
        <select name="role" class="users-select">
            <option value="">All Roles</option>
            <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
            <option value="curator" {{ request('role') === 'curator' ? 'selected' : '' }}>Curator</option>
            <option value="landmark_manager" {{ request('role') === 'landmark_manager' ? 'selected' : '' }}>Landmark Manager</option>
            <option value="visitor" {{ request('role') === 'visitor' ? 'selected' : '' }}>Visitor</option>
        </select>
        @endunless

        <button type="submit" class="users-btn apply">
            Apply
        </button>

        <a href="{{ route($usersListRouteName) }}" class="users-btn clear">
            Clear
        </a>
    </form>

    @if (count($users) === 0)
        <p class="empty-box">{{ $curatorsOnly ? 'No curators found. Try a different search.' : 'No users found. Try changing your search or role filter.' }}</p>
    @else
        <div class="users-table-card">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Role</th>
                        @if ($curatorsOnly || request()->routeIs('admin.users'))
                            <th>Signup</th>
                        @endif
                        <th>Approval</th>
                        <th>UID</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        @php
                            $approvalLabel = ucfirst(str_replace('_', ' ', $user->approval_status));
                            $showPendingActions = ! empty($user->approval_actions);
                            $signupDetail = '';
                            if ($user->role === 'curator') {
                                $signupDetail = ($user->curator_registration_type ?? '') === 'new_landmark'
                                    ? 'New landmark proposal'
                                    : 'Landmark join code';
                            }
                        @endphp
                        <tr>
                            <td>{{ $user->email }}</td>
                            <td>
                                <span class="role-pill role-{{ strtolower($user->role) }}">
                                    {{ str_replace('_', ' ', $user->role) }}
                                </span>
                            </td>
                            @if ($curatorsOnly || request()->routeIs('admin.users'))
                                <td style="font-size:.88rem;color:#374151;">
                                    {{ $signupDetail ?: '—' }}
                                </td>
                            @endif
                            <td>
                                <span class="status-pill status-{{ $user->approval_status }}">{{ $approvalLabel }}</span>
                            </td>
                            <td class="uid-text">{{ $user->uid }}</td>
                            <td>
                                @if ($showPendingActions)
                                    <div class="user-actions">
                                        <form method="POST" action="{{ route($userActionsRoutePrefix.'.approve', ['uid' => $user->uid]) }}">
                                            @csrf
                                            <input type="hidden" name="search" value="{{ request('search') }}">
                                            <input type="hidden" name="role" value="{{ $curatorsOnly ? 'curator' : request('role') }}">
                                            <button type="submit" class="btn-approve">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route($userActionsRoutePrefix.'.reject', ['uid' => $user->uid]) }}" onsubmit="return confirm('Reject this registration?');">
                                            @csrf
                                            <input type="hidden" name="search" value="{{ request('search') }}">
                                            <input type="hidden" name="role" value="{{ $curatorsOnly ? 'curator' : request('role') }}">
                                            <button type="submit" class="btn-reject">Reject</button>
                                        </form>
                                    </div>
                                @else
                                    <span style="color: #9ca3af; font-size: .82rem;">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    </div>
@endsection

