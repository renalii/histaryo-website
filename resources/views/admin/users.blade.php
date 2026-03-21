@extends('layouts.sidebar')

@section('content')
    <h2 style="font-size: 1.75rem; font-weight: bold; margin-bottom: 1.5rem;">👥 All Registered Users</h2>

    
    <form method="GET" action="{{ route('admin.users') }}" style="margin-bottom: 1.5rem; display: flex; gap: 10px; flex-wrap: wrap;">
        <input 
            type="text" 
            name="search" 
            value="{{ request('search') }}" 
            placeholder="Search by email, role, or UID..." 
            style="padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; width: 250px;">

        <select name="role" style="padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
            <option value="">All Roles</option>
            <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
            <option value="curator" {{ request('role') === 'curator' ? 'selected' : '' }}>Curator</option>
            <option value="visitor" {{ request('role') === 'visitor' ? 'selected' : '' }}>Visitor</option>
        </select>

        
        <button type="submit" 
            style="background-color: #2563eb; color: white; padding: 10px 15px; border: none; border-radius: 6px; cursor: pointer;">
            Apply
        </button>

        
        <a href="{{ route('admin.users') }}" 
            style="background-color: #6b7280; color: white; padding: 10px 15px; border-radius: 6px; text-decoration: none; display: flex; align-items: center;">
            Clear
        </a>
    </form>

    @if (count($users) === 0)
        <p style="color: #6b7280;">No users found.</p>
    @else
        <div style="background: white; padding: 1.5rem; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); overflow-x:auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background-color: #f3f4f6;">
                    <tr>
                        <th style="text-align: left; padding: 12px;">Email</th>
                        <th style="text-align: left; padding: 12px;">Role</th>
                        <th style="text-align: left; padding: 12px;">UID</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr style="border-top: 1px solid #e5e7eb;">
                            <td style="padding: 12px;">{{ $user->email }}</td>
                            <td style="padding: 12px; text-transform: capitalize;">{{ $user->role }}</td>
                            <td style="padding: 12px;">{{ $user->uid }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection

