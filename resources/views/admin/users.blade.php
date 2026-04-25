@extends('layouts.sidebar')

@section('content')
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
        .users-table { width: 100%; border-collapse: separate; border-spacing: 0; min-width: 680px; }
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
        .uid-text { color: #6b7280; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .84rem; }
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
    <h2 class="users-title">All Registered Users</h2>
    <p class="users-sub">{{ count($users) }} user{{ count($users) !== 1 ? 's' : '' }} found</p>

    <form method="GET" action="{{ route('admin.users') }}" class="users-filter">
        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search by email or UID..."
            class="users-input">

        <select name="role" class="users-select">
            <option value="">All Roles</option>
            <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
            <option value="curator" {{ request('role') === 'curator' ? 'selected' : '' }}>Curator</option>
            <option value="visitor" {{ request('role') === 'visitor' ? 'selected' : '' }}>Visitor</option>
        </select>

        <button type="submit" class="users-btn apply">
            Apply
        </button>

        <a href="{{ route('admin.users') }}" class="users-btn clear">
            Clear
        </a>
    </form>

    @if (count($users) === 0)
        <p class="empty-box">No users found. Try changing your search or role filter.</p>
    @else
        <div class="users-table-card">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Role</th>
                        <th>UID</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->email }}</td>
                            <td>
                                <span class="role-pill role-{{ strtolower($user->role) }}">
                                    {{ $user->role }}
                                </span>
                            </td>
                            <td class="uid-text">{{ $user->uid }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    </div>
@endsection

