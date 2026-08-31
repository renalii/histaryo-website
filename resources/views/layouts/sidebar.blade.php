<!DOCTYPE html>
<html lang="en">
@php
  $pageTitle = 'Histaryo';

  if (request()->routeIs('admin.dashboard')) {
    $pageTitle = 'Admin Dashboard';
  } elseif (request()->routeIs('sitemanager.dashboard')) {
    $pageTitle = 'Site Manager';
  } elseif (request()->routeIs('admin.users')) {
    $pageTitle = 'Users';
  } elseif (request()->routeIs('admin.site-managers')) {
    $pageTitle = 'Site Managers';
  } elseif (request()->routeIs('sitemanager.curators')) {
    $pageTitle = 'Curators';
  } elseif (request()->routeIs('sitemanager.exhibit-categories.*')) {
    $pageTitle = 'Exhibit Categories';
  } elseif (request()->routeIs('sitemanager.exhibits.*')) {
    $pageTitle = 'Exhibits';
  } elseif (request()->routeIs('admin.map')) {
    $pageTitle = 'Map';
  } elseif (request()->routeIs('sitemanager.map')) {
    $pageTitle = 'Map';
  } elseif (request()->routeIs('sitemanager.landmarks.create')) {
    $pageTitle = 'Create landmark';
  } elseif (request()->routeIs('admin.landmarks') || request()->routeIs('admin.landmarks.show')) {
    $pageTitle = request('status', 'pending') === 'pending' ? 'Landmark Approvals' : 'Landmarks';
  } elseif (request()->routeIs('sitemanager.landmarks') || request()->routeIs('sitemanager.landmarks.show')) {
    $pageTitle = 'Landmarks';
  } elseif (request()->routeIs('curators.dashboard')) {
    $pageTitle = 'Curator Dashboard';
  } elseif (request()->routeIs('curators.pending-assignment')) {
    $pageTitle = 'Assignment pending';
  } elseif (request()->routeIs('curators.tips.*')) {
    $pageTitle = 'Curator Tips Review';
  } elseif (request()->routeIs('curators.exhibits.*')) {
    $pageTitle = 'Exhibits';
  } elseif (request()->routeIs('curators.exhibit-categories.*')) {
    $pageTitle = 'Exhibit Categories';
  } elseif (request()->routeIs('curators.quiz.*')) {
    $pageTitle = 'Quiz Bank';
  } elseif (request()->routeIs('landmarks.*')) {
    $pageTitle = 'Landmarks';
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

    html,
    body {
      height: 100%;
      margin: 0;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(to bottom right, #f4f1ff, #e0e7ff);
      color: #111827;
      display: flex;
      min-height: 100vh;
    }

    .sidebar {
      position: fixed;
      top: 0;
      left: 0;
      z-index: 10;
      width: 240px;
      height: 100vh;
      min-height: 100vh;
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
      overflow-y: auto;
    }

    .sidebar-menu {
      flex: 1;
      min-height: 0;
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

    .logout-container {
      margin-top: auto;
      padding: 20px;
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
      margin-left: 240px;
      min-width: 0;
      min-height: 100vh;
      padding: 2.5rem;
    }

    @media (max-width: 768px) {
      .sidebar {
        display: none;
      }

      .main-content {
        margin-left: 0;
        padding: 1.25rem;
      }
    }
  </style>
</head>
<body>

  {{-- Sidebar --}}
  <aside class="sidebar">
    <div class="sidebar-menu">
      <h2>Histaryo</h2>
      <nav class="nav-links">
        @if(session('role') === 'curator')
          @php
            $curatorHasLandmark = is_string(session('assigned_landmark_id')) && trim(session('assigned_landmark_id')) !== '';
          @endphp
          @if($curatorHasLandmark)
            <a href="{{ route('curators.dashboard') }}"><span class="nav-label">Dashboard</span></a>
            <a href="{{ route('landmarks.show', session('assigned_landmark_id')) }}"><span class="nav-label">Landmark</span></a>
            <a href="{{ route('curators.exhibits.index') }}"><span class="nav-label">Exhibits</span></a>
            <a href="{{ route('curators.exhibit-categories.index') }}"><span class="nav-label">Exhibit Categories</span></a>
            <a href="{{ route('curators.quiz.all') }}"><span class="nav-label">Quiz Bank</span></a>
            <a href="{{ route('curators.tips.index') }}"><span class="nav-label">Review Tip</span></a>
          @else
            <a href="{{ route('curators.pending-assignment') }}"><span class="nav-label">Assignment pending</span></a>
          @endif
        @elseif(session('role') === 'admin')
          <a href="{{ route('admin.dashboard') }}"><span class="nav-label">Dashboard</span></a>
          <a href="{{ route('admin.users') }}"><span class="nav-label">Users</span></a>
          <a href="{{ route('admin.landmarks', ['status' => 'all']) }}"><span class="nav-label">Landmark</span></a>
          <a href="{{ route('admin.map') }}"><span class="nav-label">Map</span></a>
            {{-- Landmark Approvals removed as requested --}}
        @elseif(session('role') === 'site_manager')
          <a href="{{ route('sitemanager.dashboard') }}"><span class="nav-label">Dashboard</span></a>
          <a href="{{ route('sitemanager.landmarks') }}"><span class="nav-label">Landmarks</span></a>
          <a href="{{ route('sitemanager.exhibits.index') }}"><span class="nav-label">Exhibits</span></a>
          <a href="{{ route('sitemanager.exhibit-categories.index') }}"><span class="nav-label">Exhibit Categories</span></a>
          <a href="{{ route('sitemanager.curators') }}"><span class="nav-label">Curators</span></a>
        @endif
      </nav>
    </div>

    <div class="logout logout-container">
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

  <script>
    window.addEventListener('pageshow', function (event) {
      if (event.persisted) {
        window.location.reload();
      }
    });
  </script>

</body>
</html>
