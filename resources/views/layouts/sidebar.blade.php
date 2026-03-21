<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Histaryo Dashboard</title>
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
      background: rgba(126, 34, 206, 0.85);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border-top-right-radius: 1.5rem;
      border-bottom-right-radius: 1.5rem;
      color: white;
      padding: 2rem 1.5rem;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      box-shadow: 6px 0 30px rgba(126, 34, 206, 0.2);
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
      color: #f3e8ff;
      text-decoration: none;
      font-weight: 500;
      font-size: 1rem;
      padding: 0.6rem 1rem;
      border-radius: 0.75rem;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .nav-links a:hover {
      background: rgba(255, 255, 255, 0.2);
      color: #fff;
      transform: translateX(4px);
    }

    .logout {
      margin-top: 2rem;
    }

    .logout form button {
        background-color: rgba(253, 230, 138, 0.2); 
        color: #fde68a;
        font-weight: 600;
        font-size: 0.95rem;
        border: 1px solid rgba(253, 230, 138, 0.5);
        border-radius: 8px;
        padding: 0.6rem 1rem;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        width: 100%;
        text-align: center;
        backdrop-filter: blur(4px);
        }

        .logout form button:hover {
        background-color: rgba(253, 230, 138, 0.4);
        color: #fffbea;
        border-color: #fde68a;
        transform: translateY(-2px);
        }

    .main-content {
      flex: 1;
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
          <a href="{{ route('curators.dashboard') }}">🏠 <span>Dashboard</span></a>
          <a href="{{ route('landmarks.index') }}">📍 <span>Landmarks</span></a>
          <a href="{{ route('curators.trivia.all') }}">❓ <span>Trivia</span></a>
          <a href="{{ route('curators.map') }}">🗺️ <span>Map</span></a>
          <a href="{{ route('curators.qr') }}">📱 QR Codes</a>
        @elseif(session('role') === 'admin')
          <a href="{{ route('admin.dashboard') }}">🏠 <span>Dashboard</span></a>
          <a href="{{ route('admin.users') }}">👥 <span>Users</span></a>
          <!-- <a href="{{ route('admin.curators') }}">🧑‍🏫 <span>Curators</span></a> -->
          <a href="{{ route('admin.landmarks') }}">🧭 <span>Landmarks</span></a>
          <a href="{{ route('admin.logs') }}">📋 <span>Logs</span></a>
          <a href="{{ route('admin.reports') }}">📊 <span>Reports</span></a>
        @endif
      </nav>
    </div>

    <div class="logout">
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">🚪 Logout</button>
      </form>
    </div>
  </aside>

  {{-- Main Content --}}
  <main class="main-content">
    @yield('content')
  </main>

</body>
</html>
