@extends('layouts.sidebar')

@section('content')
    @php
        $email = session('email');
        $name = session('name') ?? ($email ? ucfirst(explode('@', $email)[0]) : 'Curator');
    @endphp

    <div style="
        background: linear-gradient(135deg, #4c1d95, #7c3aed);
        color: #fff;
        padding: 2rem 2.25rem;
        border-radius: 1.25rem;
        margin-bottom: 1.75rem;
        box-shadow: 0 12px 24px rgba(76, 29, 149, 0.25);">
        <p style="margin: 0 0 0.35rem 0; font-size: 0.85rem; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.08em;">
            Curator account
        </p>
        <h1 style="margin: 0; font-size: 1.75rem; font-weight: 700;">Hi, {{ $name }}</h1>
    </div>

    <div class="card" style="background:#fff; border-radius:14px; padding:1.75rem 2rem; box-shadow: 0 4px 24px rgba(0,0,0,0.07); border:1px solid #f3f4f6; max-width: 40rem;">
        <h2 style="margin: 0 0 0.75rem 0; font-size: 1.35rem; color: #111827;">Waiting for site assignment</h2>
        <p style="margin: 0 0 1rem 0; color: #4b5563; line-height: 1.6;">
            Your account is active, but no landmark is linked to your curator profile yet. A <strong>Site Manager</strong> or administrator must assign you to a landmark in the system before you can manage trivia, QR codes, and tips.
        </p>
        <p style="margin: 0 0 1.25rem 0; color: #4b5563; line-height: 1.6;">
            If you recently registered with a landmark code, ask the manager for that landmark to approve your request. If you were added manually, contact your Site Manager or support so they can set <code style="background:#f3f4f6; padding:0.15rem 0.4rem; border-radius:4px; font-size:0.9em;">assigned_landmark_id</code> on your user profile.
        </p>
        <p style="margin: 0; color: #6b7280; font-size: 0.95rem;">
            After your manager saves your site in Firestore, use <strong>Check again</strong> below. We re-read your profile from the server each time—you do not need to log out unless you prefer to start a new session.
        </p>
        <div style="margin-top: 1.5rem; display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center;">
            <a href="{{ route('curators.pending-assignment') }}" style="display:inline-block; background:#7A2E1F; color:#fff; text-decoration:none; font-weight:600; padding:0.65rem 1.25rem; border-radius:10px;">
                Check again
            </a>
            <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                @csrf
                <button type="submit" style="background:#f3f4f6; color:#374151; font-weight:600; border:1px solid #e5e7eb; padding:0.65rem 1.25rem; border-radius:10px; cursor:pointer;">
                    Log out
                </button>
            </form>
        </div>
    </div>
@endsection
