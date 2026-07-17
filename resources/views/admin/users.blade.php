@extends('layouts.sidebar')

@section('content')
    @php
        $panelRoutePrefix = session('role') === 'site_manager' ? 'sitemanager' : 'admin';
        $curatorsOnly = $curatorsOnly ?? false;
        $siteManagersOnly = $siteManagersOnly ?? false;
        $usersListRouteName = $curatorsOnly
            ? 'sitemanager.curators'
            : ($siteManagersOnly ? 'admin.site-managers' : ($panelRoutePrefix.'.users'));
        $userActionsRoutePrefix = $curatorsOnly ? 'sitemanager.curators' : ($panelRoutePrefix.'.users');
        $usersIsPaginated = $users instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;
        $usersTotal = $usersIsPaginated ? $users->total() : count($users);
        $usersIsEmpty = $usersTotal === 0;
        $adminUsersIndex = request()->routeIs('admin.users') && ! $curatorsOnly && ! $siteManagersOnly;
    @endphp
    <style>
        @if ($adminUsersIndex)
        @media (min-width: 769px) {
            html,
            body {
                height: 100%;
                min-height: 0;
                overflow-y: hidden;
            }
            .main-content {
                height: 100vh;
                overflow-y: hidden;
            }
        }
        @endif
        .users-wrap { max-width: 2000px; margin: 0 auto; }
        .users-title { font-size: 1.9rem; font-weight: 800; margin: 0 0 .25rem 0; color: #7A2E1F; }
        .users-sub { margin: 0 0 1rem 0; color: #6b7280; font-size: .95rem; }
        .users-filter {
            display: flex;
            gap: .6rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
            border-radius: 12px;
            padding: .8rem;
        }
        .users-filter.curators-filter {
            display: inline-flex;
            width: auto;
            max-width: 100%;
            align-items: center;
            flex-grow: 0;
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
            border-color: #d1d5db;
            box-shadow: none;
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
        .users-table.admin-users-table { min-width: 620px; }
        .users-table.curators-table { min-width: 780px; }
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
        .role-site_manager { background: #fef3c7; color: #92400e; border-color: #fde68a; }
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
        .status-active { background: #ecfdf5; color: #166534; border-color: #bbf7d0; }
        .status-inactive { background: #f3f4f6; color: #4b5563; border-color: #d1d5db; }
        .user-actions { display: inline-flex; flex-wrap: wrap; gap: .4rem; align-items: center; }
        .user-actions form { display: inline-block; margin: 0; }
        .btn-edit {
            padding: .35rem .65rem;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            font-weight: 700;
            font-size: .78rem;
            cursor: pointer;
            background: #fff;
            color: #374151;
            text-decoration: none;
        }
        .btn-edit:hover { background: #f9fafb; }
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
        .curator-delete-modal {
            position: fixed;
            inset: 0;
            z-index: 1100;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(17, 24, 39, 0.55);
        }
        .curator-delete-modal.is-open { display: flex; }
        .curator-delete-modal__panel {
            position: relative;
            width: min(100%, 430px);
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.28);
            padding: 1.2rem;
            border: 1px solid #f1f5f9;
        }
        .curator-delete-modal__title {
            margin: 0 0 .65rem;
            color: #7A2E1F;
            font-size: 1.25rem;
            font-weight: 800;
        }
        .curator-delete-modal__message,
        .curator-delete-modal__detail {
            margin: 0;
            color: #374151;
            line-height: 1.55;
        }
        .curator-delete-modal__detail {
            margin-top: .75rem;
            color: #6b7280;
        }
        .curator-delete-modal__email {
            color: #111827;
            font-weight: 800;
            overflow-wrap: anywhere;
        }
        .curator-delete-modal__landmark {
            margin: .85rem 0 0;
            padding: .7rem;
            border-radius: 8px;
            background: #fff7ed;
            color: #7A2E1F;
        }
        .curator-delete-modal__actions {
            display: flex;
            justify-content: flex-end;
            gap: .6rem;
            margin-top: 1.25rem;
        }
        .curator-delete-modal__btn {
            border-radius: 8px;
            border: 1px solid transparent;
            padding: .6rem .9rem;
            font-weight: 800;
            cursor: pointer;
            transition: all .15s ease;
        }
        .curator-delete-modal__btn--secondary {
            background: #f3f4f6;
            color: #374151;
            border-color: #e5e7eb;
        }
        .curator-delete-modal__btn--secondary:hover { background: #e5e7eb; }
        .curator-delete-modal__btn--danger {
            background: #991b1b;
            color: #fff;
            border-color: #991b1b;
        }
        .curator-delete-modal__btn--danger:hover { background: #7f1d1d; }
        #curatorDeleteModal .curator-delete-modal__actions,
        #curatorDeactivateModal .curator-delete-modal__actions,
        #curatorActivateModal .curator-delete-modal__actions,
        #siteManagerApproveModal .curator-delete-modal__actions,
        #siteManagerRejectModal .curator-delete-modal__actions { flex-direction: row; }
        #curatorDeleteModal .curator-delete-modal__btn,
        #curatorDeactivateModal .curator-delete-modal__btn,
        #curatorActivateModal .curator-delete-modal__btn,
        #siteManagerApproveModal .curator-delete-modal__btn,
        #siteManagerRejectModal .curator-delete-modal__btn {
            width: auto;
            padding: .45rem .8rem;
            border-radius: 8px;
            font-size: .85rem;
            line-height: 1;
            font-weight: 700;
        }
        #curatorDeleteModal .curator-delete-modal__btn--secondary,
        #curatorDeactivateModal .curator-delete-modal__btn--secondary,
        #curatorActivateModal .curator-delete-modal__btn--secondary,
        #siteManagerApproveModal .curator-delete-modal__btn--secondary,
        #siteManagerRejectModal .curator-delete-modal__btn--secondary {
            background: #f3f4f6;
            border-color: #e5e7eb;
            color: #374151;
        }
        #curatorDeleteModal .curator-delete-modal__btn--danger,
        #curatorDeactivateModal .curator-delete-modal__btn--danger,
        #curatorActivateModal .curator-delete-modal__btn--danger,
        #siteManagerRejectModal .curator-delete-modal__btn--danger {
            background: #ef4444;
            border-color: #ef4444;
            color: #fff;
        }
        #curatorDeleteModal .curator-delete-modal__btn--danger:hover,
        #curatorDeactivateModal .curator-delete-modal__btn--danger:hover,
        #curatorActivateModal .curator-delete-modal__btn--danger:hover,
        #siteManagerRejectModal .curator-delete-modal__btn--danger:hover {
            background: #dc2626;
            border-color: #dc2626;
        }
        #siteManagerApproveModal .curator-delete-modal__btn--approve {
            background: #166534;
            border-color: #166534;
            color: #fff;
        }
        #siteManagerApproveModal .curator-delete-modal__btn--approve:hover {
            background: #14532d;
            border-color: #14532d;
        }
        .site-manager-reject-modal__close {
            position: absolute;
            top: .65rem;
            right: .7rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            padding: 0;
            border: 0;
            border-radius: 7px;
            background: transparent;
            color: #6b7280;
            font-size: 1.35rem;
            line-height: 1;
            cursor: pointer;
        }
        .site-manager-reject-modal__close:hover { background: #f3f4f6; }
        .site-manager-reject-modal__note {
            margin: .35rem 0 0;
            color: #6b7280;
            font-size: .82rem;
        }
        .flash-ok {
            padding: .75rem 1rem;
            border-radius: 10px;
            background: #ecfdf5;
            color: #166534;
            border: 1px solid #bbf7d0;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        .flash-ok.curators-flash-ok {
            width: fit-content;
            max-width: 100%;
            box-sizing: border-box;
            padding: .55rem 1rem;
        }
        .flash-ok.admin-users-flash-ok {
            width: fit-content;
            max-width: 100%;
            box-sizing: border-box;
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
        .users-pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            padding: .85rem 0 0;
        }
        .users-pagination__summary {
            color: #6b7280;
            font-size: .88rem;
            font-weight: 600;
        }
        .users-pagination__controls {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: .65rem;
            flex-wrap: nowrap;
            margin-left: auto;
        }
        .users-page-link,
        .users-page-disabled {
            padding: .52rem .85rem;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            text-align: center;
            font-size: .85rem;
            font-weight: 700;
            text-decoration: none;
        }
        .users-page-link--previous { background: #fff; color: #6b7280; }
        .users-page-link--previous:hover { background: #f9fafb; color: #374151; }
        .users-page-link--next { background: #E8B34B; border-color: #F3C96A; color: #7A2E1F; }
        .users-page-link--next:hover { background: #F3C96A; }
        .users-page-disabled { background: #f9fafb; color: #9ca3af; }
        .users-pagination__page-count {
            color: #4b5563;
            font-size: .88rem;
            font-weight: 600;
        }
        @media (max-width: 640px) {
            .users-input { width: 100%; }
            .users-filter { padding: .7rem; }
            .users-btn { flex: 1 1 auto; }
            .curator-delete-modal__actions { flex-direction: column-reverse; }
            .curator-delete-modal__btn { width: 100%; }
            .users-pagination {
                align-items: flex-start;
                flex-direction: column;
                gap: .4rem;
            }
            .users-pagination__controls {
                width: 100%;
                gap: .4rem;
                margin-left: 0;
            }
            .users-page-link,
            .users-page-disabled {
                padding: .45rem .6rem;
                font-size: .8rem;
            }
            .users-pagination__page-count { font-size: .8rem; }
        }
    </style>

    <div class="users-wrap">
    <div style="display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:.25rem;">
        <div>
            <h2 class="users-title" style="margin-bottom:.25rem;">
                @if ($curatorsOnly)
                    Curators
                @elseif ($siteManagersOnly)
                    Site Managers
                @else
                    All Registered Users
                @endif
            </h2>
            <p class="users-sub" style="margin-bottom:0;">
                @if ($curatorsOnly)
                    {{ $usersTotal }} curator{{ $usersTotal !== 1 ? 's' : '' }} found
                @elseif ($siteManagersOnly)
                    {{ count($users) }} Site Manager{{ count($users) !== 1 ? 's' : '' }} — approve or reject pending registrations
                @else
                    {{ $usersTotal }} user{{ $usersTotal !== 1 ? 's' : '' }} found
                @endif
            </p>
        </div>
        @if ($curatorsOnly && $panelRoutePrefix === 'sitemanager')
            <button type="button" id="openCuratorDrawer" class="users-btn apply" style="white-space:nowrap;">
                + Add curator
            </button>
        @endif
    </div>

    @if (session('status'))
        <p class="flash-ok{{ $curatorsOnly && $panelRoutePrefix === 'sitemanager' ? ' curators-flash-ok' : '' }}{{ $adminUsersIndex ? ' admin-users-flash-ok' : '' }}">{{ session('status') }}</p>
    @endif
    @if (session('status_err'))
        <p class="flash-err">{{ session('status_err') }}</p>
    @endif

    <form method="GET" action="{{ route($usersListRouteName) }}" class="users-filter{{ $curatorsOnly && $panelRoutePrefix === 'sitemanager' ? ' curators-filter' : '' }}">
        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="{{ $curatorsOnly ? 'Search by email, landmark, or status...' : 'Search by email...' }}"
            class="users-input">

        @if (! $curatorsOnly && ! $siteManagersOnly)
        <select name="role" class="users-select">
            <option value="">All Roles</option>
            <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
            <option value="curator" {{ request('role') === 'curator' ? 'selected' : '' }}>Curator</option>
            <option value="site_manager" {{ request('role') === 'site_manager' ? 'selected' : '' }}>Site Manager</option>
        </select>
        @endif

        <button type="submit" class="users-btn apply">
            {{ ($curatorsOnly && $panelRoutePrefix === 'sitemanager') || $adminUsersIndex ? 'Search' : 'Apply' }}
        </button>

        <a href="{{ route($usersListRouteName) }}" class="users-btn clear">
            Clear
        </a>
    </form>

    @if ($usersIsEmpty)
        <p class="empty-box">
            @if ($curatorsOnly)
                No curators found. Try a different search.
            @elseif ($siteManagersOnly)
                No Site Manager registrations match your search.
            @else
                No users found. Try changing your search or role filter.
            @endif
        </p>
    @else
        <div class="users-table-card">
            <table class="users-table{{ $curatorsOnly ? ' curators-table' : '' }}{{ $adminUsersIndex ? ' admin-users-table' : '' }}">
                <thead>
                    <tr>
                        <th>Email</th>
                        @if (! $curatorsOnly)
                            <th>Role</th>
                        @endif
                        @if ($curatorsOnly && $panelRoutePrefix === 'sitemanager')
                            <th>Landmark</th>
                        @endif
                        @if (! $adminUsersIndex && ! ($curatorsOnly && $panelRoutePrefix === 'sitemanager'))
                            <th>Approval</th>
                        @endif
                        @if ($curatorsOnly)
                            <th>Status</th>
                        @endif
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        @php
                            $approvalLabel = ucfirst(str_replace('_', ' ', $user->approval_status));
                            $showPendingActions = ! empty($user->approval_actions);
                            $roleLabel = $user->role === 'site_manager'
                                ? 'Site Manager'
                                : ucfirst(str_replace('_', ' ', $user->role));
                            $accountStatus = $user->account_status ?? 'active';
                            $accountStatusLabel = ucfirst($accountStatus);
                        @endphp
                        <tr>
                            <td>{{ $user->email }}</td>
                            @if (! $curatorsOnly)
                                <td>
                                    <span class="role-pill role-{{ strtolower($user->role) }}">
                                        {{ $roleLabel }}
                                    </span>
                                </td>
                            @endif
                            @if ($curatorsOnly && $panelRoutePrefix === 'sitemanager')
                                <td>{{ $user->assigned_landmark_name ?? 'Unassigned' }}</td>
                            @endif
                            @if (! $adminUsersIndex && ! ($curatorsOnly && $panelRoutePrefix === 'sitemanager'))
                            <td>
                                @if ($user->role === 'visitor')
                                    <span style="color: #9ca3af; font-size: .82rem;">—</span>
                                @else
                                    <span class="status-pill status-{{ $user->approval_status }}">{{ $approvalLabel }}</span>
                                @endif
                            </td>
                            @endif
                            @if ($curatorsOnly)
                                <td>
                                    <span class="status-pill status-{{ $accountStatus }}">{{ $accountStatusLabel }}</span>
                                </td>
                            @endif
                            <td>
                                @if ($curatorsOnly && $panelRoutePrefix === 'sitemanager')
                                    <div class="user-actions">
                                        <a href="{{ route('sitemanager.curators', array_filter(['search' => request('search'), 'page' => request('page'), 'edit' => $user->uid])) }}" class="btn-edit">Edit</a>

                                        <form method="POST" action="{{ route('sitemanager.curators.destroy', ['uid' => $user->uid]) }}" class="curator-delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="button"
                                                class="btn-reject js-curator-delete-action"
                                                data-curator-email="{{ $user->email }}"
                                                data-landmark-name="{{ $user->assigned_landmark_name ?? 'Unassigned' }}">
                                                Delete
                                            </button>
                                        </form>

                                        @if ($accountStatus === 'inactive')
                                            <form method="POST" action="{{ route('sitemanager.curators.activate', ['uid' => $user->uid]) }}">
                                                @csrf
                                                <input type="hidden" name="search" value="{{ request('search') }}">
                                                <input type="hidden" name="page" value="{{ request('page') }}">
                                                <button type="button" class="btn-reject js-curator-activate-action">Activate</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('sitemanager.curators.deactivate', ['uid' => $user->uid]) }}" class="curator-deactivate-form">
                                                @csrf
                                                <input type="hidden" name="search" value="{{ request('search') }}">
                                                <input type="hidden" name="page" value="{{ request('page') }}">
                                                <button type="button" class="btn-reject js-curator-deactivate-action">Deactivate</button>
                                            </form>
                                        @endif
                                    </div>
                                @elseif ($showPendingActions)
                                    <div class="user-actions">
                                        <form
                                            method="POST"
                                            action="{{ route($userActionsRoutePrefix.'.approve', ['uid' => $user->uid]) }}"
                                            @if ($user->role === 'site_manager')
                                                class="site-manager-approve-form"
                                            @endif>
                                            @csrf
                                            <input type="hidden" name="search" value="{{ request('search') }}">
                                            <input type="hidden" name="role" value="{{ $curatorsOnly ? 'curator' : request('role') }}">
                                            @if ($siteManagersOnly)
                                                <input type="hidden" name="return_to" value="site-managers">
                                            @endif
                                            <button
                                                type="{{ $user->role === 'site_manager' ? 'button' : 'submit' }}"
                                                class="btn-approve{{ $user->role === 'site_manager' ? ' js-site-manager-approve-action' : '' }}"
                                                data-user-email="{{ $user->email }}">
                                                Approve
                                            </button>
                                        </form>
                                        <form
                                            method="POST"
                                            action="{{ route($userActionsRoutePrefix.'.reject', ['uid' => $user->uid]) }}"
                                            @if ($user->role === 'site_manager')
                                                class="site-manager-reject-form"
                                            @else
                                                onsubmit="return confirm('Reject this registration?');"
                                            @endif>
                                            @csrf
                                            <input type="hidden" name="search" value="{{ request('search') }}">
                                            <input type="hidden" name="role" value="{{ $curatorsOnly ? 'curator' : request('role') }}">
                                            @if ($siteManagersOnly)
                                                <input type="hidden" name="return_to" value="site-managers">
                                            @endif
                                            <button
                                                type="{{ $user->role === 'site_manager' ? 'button' : 'submit' }}"
                                                class="btn-reject{{ $user->role === 'site_manager' ? ' js-site-manager-reject-action' : '' }}"
                                                data-user-email="{{ $user->email }}">
                                                Reject
                                            </button>
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
            @if ($usersIsPaginated && ($curatorsOnly || request()->routeIs('admin.users') || $users->hasPages()))
                <nav class="users-pagination" aria-label="Users pagination">
                    @if (! $curatorsOnly && ! request()->routeIs('admin.users'))
                        <span class="users-pagination__summary">
                            Showing {{ $users->firstItem() }}–{{ $users->lastItem() }} of {{ $users->total() }} users
                        </span>
                    @endif
                    <div class="users-pagination__controls">
                        @if ($users->onFirstPage())
                            @if ($curatorsOnly || request()->routeIs('admin.users'))
                                <span class="users-page-disabled">Prev</span>
                            @else
                                <span class="users-page-disabled">&larr; Prev</span>
                            @endif
                        @else
                            @if ($curatorsOnly || request()->routeIs('admin.users'))
                                <a class="users-page-link users-page-link--previous" href="{{ $users->previousPageUrl() }}">Prev</a>
                            @else
                                <a class="users-page-link users-page-link--previous" href="{{ $users->previousPageUrl() }}">&larr; Prev</a>
                            @endif
                        @endif

                        <span class="users-pagination__page-count">
                            Page {{ $users->currentPage() }} of {{ $users->lastPage() }}
                        </span>

                        @if ($users->hasMorePages())
                            @if ($curatorsOnly || request()->routeIs('admin.users'))
                                <a class="users-page-link users-page-link--next" href="{{ $users->nextPageUrl() }}">Next</a>
                            @else
                                <a class="users-page-link users-page-link--next" href="{{ $users->nextPageUrl() }}">Next &rarr;</a>
                            @endif
                        @else
                            @if ($curatorsOnly || request()->routeIs('admin.users'))
                                <span class="users-page-disabled">Next</span>
                            @else
                                <span class="users-page-disabled">Next &rarr;</span>
                            @endif
                        @endif
                    </div>
                </nav>
            @endif
        </div>
    @endif
    </div>

    @if ($panelRoutePrefix === 'admin' && ! $curatorsOnly)
        <div
            id="siteManagerApproveModal"
            class="curator-delete-modal"
            role="dialog"
            aria-modal="true"
            aria-hidden="true"
            aria-labelledby="siteManagerApproveModalTitle">
            <div class="curator-delete-modal__panel" tabindex="-1">
                <button
                    type="button"
                    id="siteManagerApproveClose"
                    class="site-manager-reject-modal__close"
                    aria-label="Close approve registration confirmation">&times;</button>
                <h2 id="siteManagerApproveModalTitle" class="curator-delete-modal__title">Approve Registration</h2>
                <p class="curator-delete-modal__message">Are you sure you want to approve this Site Manager registration?</p>
                <p class="curator-delete-modal__detail">
                    Selected account:<br>
                    <strong id="siteManagerApproveEmail" class="curator-delete-modal__email"></strong>
                </p>

                <div class="curator-delete-modal__actions">
                    <button type="button" id="siteManagerApproveCancel" class="curator-delete-modal__btn curator-delete-modal__btn--secondary">Cancel</button>
                    <button type="button" id="siteManagerApproveConfirm" class="curator-delete-modal__btn curator-delete-modal__btn--approve">Approve</button>
                </div>
            </div>
        </div>

        <div
            id="siteManagerRejectModal"
            class="curator-delete-modal"
            role="dialog"
            aria-modal="true"
            aria-hidden="true"
            aria-labelledby="siteManagerRejectModalTitle">
            <div class="curator-delete-modal__panel" tabindex="-1">
                <button
                    type="button"
                    id="siteManagerRejectClose"
                    class="site-manager-reject-modal__close"
                    aria-label="Close reject registration confirmation">&times;</button>
                <h2 id="siteManagerRejectModalTitle" class="curator-delete-modal__title">Reject Registration</h2>
                <p class="curator-delete-modal__message">Are you sure you want to reject this Site Manager registration?</p>
                <p class="curator-delete-modal__detail">
                    Selected account:<br>
                    <strong id="siteManagerRejectEmail" class="curator-delete-modal__email"></strong>
                </p>

                <div class="curator-delete-modal__actions">
                    <button type="button" id="siteManagerRejectCancel" class="curator-delete-modal__btn curator-delete-modal__btn--secondary">Cancel</button>
                    <button type="button" id="siteManagerRejectConfirm" class="curator-delete-modal__btn curator-delete-modal__btn--danger">Reject</button>
                </div>
            </div>
        </div>

        <script>
            (function () {
                var modal = document.getElementById('siteManagerApproveModal');
                var panel = modal ? modal.querySelector('.curator-delete-modal__panel') : null;
                var cancelButton = document.getElementById('siteManagerApproveCancel');
                var closeButton = document.getElementById('siteManagerApproveClose');
                var confirmButton = document.getElementById('siteManagerApproveConfirm');
                var selectedEmail = document.getElementById('siteManagerApproveEmail');
                var selectedForm = null;
                var activeTrigger = null;

                if (! modal || ! cancelButton || ! closeButton || ! confirmButton || ! selectedEmail) {
                    return;
                }

                function openModal(trigger) {
                    selectedForm = trigger.closest('form');
                    activeTrigger = trigger;
                    selectedEmail.textContent = trigger.dataset.userEmail || '';
                    modal.classList.add('is-open');
                    modal.setAttribute('aria-hidden', 'false');
                    document.body.style.overflow = 'hidden';
                    if (panel) {
                        panel.focus();
                    }
                }

                function closeModal() {
                    modal.classList.remove('is-open');
                    modal.setAttribute('aria-hidden', 'true');
                    selectedForm = null;
                    selectedEmail.textContent = '';
                    document.body.style.overflow = '';
                    if (activeTrigger) {
                        activeTrigger.focus();
                    }
                    activeTrigger = null;
                }

                document.querySelectorAll('.js-site-manager-approve-action').forEach(function (button) {
                    button.addEventListener('click', function () {
                        openModal(button);
                    });
                });

                cancelButton.addEventListener('click', closeModal);
                closeButton.addEventListener('click', closeModal);
                confirmButton.addEventListener('click', function () {
                    var form = selectedForm;
                    if (! form) {
                        return;
                    }

                    modal.classList.remove('is-open');
                    modal.setAttribute('aria-hidden', 'true');
                    document.body.style.overflow = '';
                    window.HTMLFormElement.prototype.submit.call(form);
                });

                modal.addEventListener('click', function (event) {
                    if (event.target === modal) {
                        closeModal();
                    }
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                        closeModal();
                    }
                });
            })();
        </script>

        <script>
            (function () {
                var modal = document.getElementById('siteManagerRejectModal');
                var panel = modal ? modal.querySelector('.curator-delete-modal__panel') : null;
                var cancelButton = document.getElementById('siteManagerRejectCancel');
                var closeButton = document.getElementById('siteManagerRejectClose');
                var confirmButton = document.getElementById('siteManagerRejectConfirm');
                var selectedEmail = document.getElementById('siteManagerRejectEmail');
                var selectedForm = null;
                var activeTrigger = null;

                if (! modal || ! cancelButton || ! closeButton || ! confirmButton || ! selectedEmail) {
                    return;
                }

                function openModal(trigger) {
                    selectedForm = trigger.closest('form');
                    activeTrigger = trigger;
                    selectedEmail.textContent = trigger.dataset.userEmail || '';
                    modal.classList.add('is-open');
                    modal.setAttribute('aria-hidden', 'false');
                    document.body.style.overflow = 'hidden';
                    if (panel) {
                        panel.focus();
                    }
                }

                function closeModal() {
                    modal.classList.remove('is-open');
                    modal.setAttribute('aria-hidden', 'true');
                    selectedForm = null;
                    selectedEmail.textContent = '';
                    document.body.style.overflow = '';
                    if (activeTrigger) {
                        activeTrigger.focus();
                    }
                    activeTrigger = null;
                }

                document.querySelectorAll('.js-site-manager-reject-action').forEach(function (button) {
                    button.addEventListener('click', function () {
                        openModal(button);
                    });
                });

                cancelButton.addEventListener('click', closeModal);
                closeButton.addEventListener('click', closeModal);
                confirmButton.addEventListener('click', function () {
                    var form = selectedForm;
                    if (! form) {
                        return;
                    }

                    modal.classList.remove('is-open');
                    modal.setAttribute('aria-hidden', 'true');
                    document.body.style.overflow = '';
                    window.HTMLFormElement.prototype.submit.call(form);
                });

                modal.addEventListener('click', function (event) {
                    if (event.target === modal) {
                        closeModal();
                    }
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                        closeModal();
                    }
                });
            })();
        </script>
    @endif

    @if ($curatorsOnly && $panelRoutePrefix === 'sitemanager')
        <div
            id="curatorDeactivateModal"
            class="curator-delete-modal"
            role="dialog"
            aria-modal="true"
            aria-hidden="true"
            aria-labelledby="curatorDeactivateModalTitle">
            <div class="curator-delete-modal__panel" tabindex="-1">
                <h2 id="curatorDeactivateModalTitle" class="curator-delete-modal__title">Deactivate this curator account?</h2>
                <p class="curator-delete-modal__detail">The curator will no longer be able to sign in.</p>
                <div class="curator-delete-modal__actions">
                    <button type="button" id="curatorDeactivateCancel" class="curator-delete-modal__btn curator-delete-modal__btn--secondary">Cancel</button>
                    <button type="button" id="curatorDeactivateConfirm" class="curator-delete-modal__btn curator-delete-modal__btn--danger">Deactivate</button>
                </div>
            </div>
        </div>

        <div
            id="curatorActivateModal"
            class="curator-delete-modal"
            role="dialog"
            aria-modal="true"
            aria-hidden="true"
            aria-labelledby="curatorActivateModalTitle">
            <div class="curator-delete-modal__panel" tabindex="-1">
                <h2 id="curatorActivateModalTitle" class="curator-delete-modal__title">Activate this curator account?</h2>
                <p class="curator-delete-modal__detail">The curator will be able to sign in again.</p>
                <div class="curator-delete-modal__actions">
                    <button type="button" id="curatorActivateCancel" class="curator-delete-modal__btn curator-delete-modal__btn--secondary">Cancel</button>
                    <button type="button" id="curatorActivateConfirm" class="curator-delete-modal__btn curator-delete-modal__btn--danger">Activate</button>
                </div>
            </div>
        </div>

        <div
            id="curatorDeleteModal"
            class="curator-delete-modal"
            role="dialog"
            aria-modal="true"
            aria-hidden="true"
            aria-labelledby="curatorDeleteModalTitle">
            <div class="curator-delete-modal__panel" tabindex="-1">
                <h2 id="curatorDeleteModalTitle" class="curator-delete-modal__title">Delete Curator</h2>
                <p class="curator-delete-modal__message">
                    Are you sure you want to delete curator account<br>
                    <span id="curatorDeleteEmail" class="curator-delete-modal__email"></span>?
                </p>
                <p class="curator-delete-modal__landmark">
                    <strong>Assigned Landmark:</strong><br>
                    <span id="curatorDeleteLandmark"></span>
                </p>
                <div class="curator-delete-modal__actions">
                    <button type="button" id="curatorDeleteCancel" class="curator-delete-modal__btn curator-delete-modal__btn--secondary">Cancel</button>
                    <button type="button" id="curatorDeleteConfirm" class="curator-delete-modal__btn curator-delete-modal__btn--danger">Delete</button>
                </div>
            </div>
        </div>

        @include('sitemanager.partials.curator-create-drawer', [
            'landmarks' => $assignableLandmarks ?? [],
            'editCurator' => $editCurator ?? null,
        ])

        <script>
            (function () {
                var deactivateModal = document.getElementById('curatorDeactivateModal');
                var deactivateCancel = document.getElementById('curatorDeactivateCancel');
                var deactivateConfirm = document.getElementById('curatorDeactivateConfirm');
                var deactivateForm = null;
                var activateModal = document.getElementById('curatorActivateModal');
                var activateCancel = document.getElementById('curatorActivateCancel');
                var activateConfirm = document.getElementById('curatorActivateConfirm');
                var activateForm = null;
                var modal = document.getElementById('curatorDeleteModal');
                var cancelButton = document.getElementById('curatorDeleteCancel');
                var confirmButton = document.getElementById('curatorDeleteConfirm');
                var deleteEmail = document.getElementById('curatorDeleteEmail');
                var deleteLandmark = document.getElementById('curatorDeleteLandmark');
                var panel = modal ? modal.querySelector('.curator-delete-modal__panel') : null;
                var selectedForm = null;
                if (! modal || ! cancelButton || ! confirmButton) {
                    return;
                }

                document.querySelectorAll('.js-curator-deactivate-action').forEach(function (button) {
                    button.addEventListener('click', function () {
                        deactivateForm = button.closest('form');
                        deactivateModal.classList.add('is-open');
                        deactivateModal.setAttribute('aria-hidden', 'false');
                        document.body.style.overflow = 'hidden';
                    });
                });

                deactivateCancel.addEventListener('click', function () {
                    deactivateModal.classList.remove('is-open');
                    deactivateModal.setAttribute('aria-hidden', 'true');
                    deactivateForm = null;
                    document.body.style.overflow = '';
                });

                deactivateConfirm.addEventListener('click', function () {
                    if (deactivateForm) {
                        deactivateForm.submit();
                    }
                });

                document.querySelectorAll('.js-curator-activate-action').forEach(function (button) {
                    button.addEventListener('click', function () {
                        activateForm = button.closest('form');
                        activateModal.classList.add('is-open');
                        activateModal.setAttribute('aria-hidden', 'false');
                        document.body.style.overflow = 'hidden';
                    });
                });

                activateCancel.addEventListener('click', function () {
                    activateModal.classList.remove('is-open');
                    activateModal.setAttribute('aria-hidden', 'true');
                    activateForm = null;
                    document.body.style.overflow = '';
                });

                activateConfirm.addEventListener('click', function () {
                    if (activateForm) {
                        activateForm.submit();
                    }
                });

                function openModal(trigger) {
                    selectedForm = trigger.closest('form');
                    deleteEmail.textContent = trigger.dataset.curatorEmail || '';
                    deleteLandmark.textContent = trigger.dataset.landmarkName || 'Unassigned';
                    modal.classList.add('is-open');
                    modal.setAttribute('aria-hidden', 'false');
                    document.body.style.overflow = 'hidden';
                    if (panel) {
                        panel.focus();
                    }
                }

                function closeModal() {
                    modal.classList.remove('is-open');
                    modal.setAttribute('aria-hidden', 'true');
                    selectedForm = null;
                    document.body.style.overflow = '';
                }

                document.querySelectorAll('.js-curator-delete-action').forEach(function (button) {
                    button.addEventListener('click', function () {
                        openModal(button);
                    });
                });

                cancelButton.addEventListener('click', closeModal);
                confirmButton.addEventListener('click', function () {
                    if (selectedForm) {
                        selectedForm.submit();
                    }
                });

                modal.addEventListener('click', function (event) {
                    if (event.target === modal) {
                        closeModal();
                    }
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                        closeModal();
                    }
                });
            })();
        </script>
    @endif
@endsection
