<!DOCTYPE html>
<html lang="en">
@php
  $pageTitle = 'Histaryo';

  if (request()->routeIs('admin.dashboard')) {
    $pageTitle = 'Admin Dashboard';
  } elseif (request()->routeIs('landmarkmanager.dashboard')) {
    $pageTitle = 'Landmark Manager';
  } elseif (request()->routeIs('admin.users')) {
    $pageTitle = 'Admin Users';
  } elseif (request()->routeIs('landmarkmanager.curators')) {
    $pageTitle = 'Curators';
  } elseif (request()->routeIs('landmarkmanager.landmarks.create')) {
    $pageTitle = 'Create landmark';
  } elseif (request()->routeIs('admin.landmarks') || request()->routeIs('landmarkmanager.landmarks')) {
    $pageTitle = 'Landmarks';
  } elseif (request()->routeIs('admin.logs')) {
    $pageTitle = 'Admin Logs';
  } elseif (request()->routeIs('admin.reports')) {
    $pageTitle = 'Admin Reports';
  } elseif (request()->routeIs('curators.dashboard')) {
    $pageTitle = 'Curator Dashboard';
  } elseif (request()->routeIs('curators.pending-assignment')) {
    $pageTitle = 'Assignment pending';
  } elseif (request()->routeIs('curators.tips.*')) {
    $pageTitle = 'Curator Tips Review';
  }
@endphp

<head>
  <meta charset="UTF-8">
  <title>{{ $pageTitle }}</title>
  <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: 'Inter', sans-serif;
      background: linear-gradient(to bottom right, #f4f1ff, #e0e7ff);
      color: #111827;
      display: flex;
      height: 100vh;      
      overflow: hidden;   
    }

    .sidebar {
      width: 260px;
      background: linear-gradient(180deg, #7A2E1F, #8b3926);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border-top-right-radius: 1.5rem;
      border-bottom-right-radius: 1.5rem;
      color: #fff9eb;
      padding: 2rem 1.5rem;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      box-shadow: 6px 0 30px rgba(122, 46, 31, 0.28);
      height: 100vh;        
      position: sticky;     
      top: 0;
    }

    .sidebar h2 {
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 2.5rem;
      letter-spacing: 0.5px;
    }

    .nav-links {
      display: flex;
      flex-direction: column;
      gap: 1.2rem;
    }

    .nav-links a {
      color: #fff3da;
      text-decoration: none;
      font-weight: 500;
      font-size: 1rem;
      padding: 0.6rem 1rem;
      border-radius: 0.75rem;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .nav-links .nav-icon {
      width: 1.4rem;
      display: inline-flex;
      justify-content: center;
      align-items: center;
      flex-shrink: 0;
      font-size: 1.05rem;
      line-height: 1;
    }

    .nav-links .nav-label {
      line-height: 1.25;
    }

    .nav-links a:hover {
      background: rgba(243, 201, 106, 0.28);
      color: #fffdf7;
      transform: translateX(4px);
    }

    .logout {
      margin-top: 2rem;
    }

    .logout form button {
        background-color: #E8B34B;
        color: #7A2E1F;
        font-weight: 600;
        font-size: 0.95rem;
        border: 1px solid #F3C96A;
        border-radius: 8px;
        padding: 0.6rem 1rem;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        width: 100%;
        text-align: center;
        backdrop-filter: blur(4px);
        }

        .logout form button:hover {
        background-color: #F3C96A;
        color: #7A2E1F;
        border-color: #E8B34B;
        transform: translateY(-2px);
        }

    .main-content {
      flex: 1;
      min-width: 0;
      padding: 2.5rem;
      height: 100vh;        
      overflow-y: auto;     
    }

    @media (max-width: 768px) {
      .sidebar {
        display: none;
      }

      .main-content {
        padding: 1.25rem;
      }
    }
  </style>
</head>
<body>

  {{-- Sidebar --}}
  <aside class="sidebar">
    <div>
      <h2>Histaryo</h2>
      <nav class="nav-links">
        @if(session('role') === 'curator')
          @php
            $curatorHasLandmark = is_string(session('assigned_landmark_id')) && trim(session('assigned_landmark_id')) !== '';
          @endphp
          @if($curatorHasLandmark)
            <a href="{{ route('curators.dashboard') }}"><span class="nav-label">Dashboard</span></a>
            <a href="{{ route('landmarks.index') }}"><span class="nav-label">Landmarks</span></a>
            <a href="{{ route('curators.trivia.all') }}"><span class="nav-label">Trivia</span></a>
            <a href="{{ route('curators.tips.index') }}"><span class="nav-label">Tips Review</span></a>
            <a href="{{ route('curators.map') }}"><span class="nav-label">Map</span></a>
            <a href="{{ route('curators.qr') }}"><span class="nav-label">QR Codes</span></a>
          @else
            <a href="{{ route('curators.pending-assignment') }}"><span class="nav-label">Assignment pending</span></a>
          @endif
        @elseif(session('role') === 'admin')
          <a href="{{ route('admin.dashboard') }}"><span class="nav-label">Dashboard</span></a>
          <a href="{{ route('admin.users') }}"><span class="nav-label">Users</span></a>
          <a href="{{ route('admin.landmarks') }}"><span class="nav-label">Landmarks</span></a>
          <a href="{{ route('admin.logs') }}"><span class="nav-label">Logs</span></a>
          <a href="{{ route('admin.reports') }}"><span class="nav-label">Reports</span></a>
        @elseif(session('role') === 'landmark_manager')
          <a href="{{ route('landmarkmanager.dashboard') }}"><span class="nav-label">Dashboard</span></a>
          <a href="{{ route('landmarkmanager.curators') }}"><span class="nav-label">Curators</span></a>
          <a href="{{ route('landmarkmanager.landmarks') }}"><span class="nav-label">Landmarks</span></a>
          <a href="{{ route('landmarkmanager.landmarks.create') }}"><span class="nav-label">Create landmark</span></a>
        @endif
      </nav>
    </div>

    <div class="logout">
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Logout</button>
      </form>
    </div>
  </aside>

  {{-- Main Content --}}
  <main class="main-content">
    @yield('content')
  </main>

</body>
</html>
