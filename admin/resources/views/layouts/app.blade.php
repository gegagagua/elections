<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @isset($autoRefresh)
        <meta http-equiv="refresh" content="{{ (int) $autoRefresh }}">
    @endisset
    <title>@yield('title', 'ადმინი')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --sidebar-w: 240px;
        }
        body {
            background: #f5f6fa;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", sans-serif;
        }
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: var(--sidebar-w);
            background: #1e293b;
            color: #cbd5e1;
            padding: 20px 0;
            overflow-y: auto;
        }
        .sidebar .brand {
            color: #fff;
            font-weight: 700;
            font-size: 18px;
            padding: 0 20px 20px;
            border-bottom: 1px solid #334155;
            margin-bottom: 12px;
        }
        .sidebar .nav-link {
            color: #cbd5e1;
            padding: 10px 20px;
            border-radius: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sidebar .nav-link:hover { background: #334155; color: #fff; }
        .sidebar .nav-link.active { background: #0d6efd; color: #fff; }
        .sidebar .nav-link i { font-size: 18px; }

        .topbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-w);
            right: 0;
            height: 60px;
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 0 24px;
            gap: 12px;
            z-index: 10;
        }
        .content {
            margin-left: var(--sidebar-w);
            padding: 84px 24px 24px;
        }
        .page-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 12px;
            flex-wrap: wrap;
        }
        .page-head h1 { margin: 0; font-size: 24px; font-weight: 600; }

        .stat-card { border: none; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .stat-card .card-body { padding: 20px; }

        .table { --bs-table-bg: #fff; }
        .table > :not(caption) > * > * { padding: 12px 14px; }
        .table thead th {
            background: #f8fafc;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            font-weight: 600;
        }
        .table tbody tr.row-came { background: #d1fae5 !important; }
        .table tbody tr.row-came td { background: #d1fae5 !important; }

        .card { border: none; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border-radius: 12px; }
        .card-header { background: #fff; border-bottom: 1px solid #e2e8f0; padding: 16px 20px; font-weight: 600; }

        .tile-link { text-decoration: none; color: inherit; display: block; }
        .tile-link:hover .card { box-shadow: 0 4px 12px rgba(0,0,0,0.1); transform: translateY(-2px); transition: all .15s ease; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform .2s; z-index: 20; }
            .sidebar.show { transform: translateX(0); }
            .topbar { left: 0; }
            .content { margin-left: 0; padding: 76px 16px 16px; }
        }
    </style>
</head>
<body>
    @auth
        <aside class="sidebar" id="sidebar">
            <div class="brand"><i class="bi bi-check2-square"></i> არჩევნები</div>
            <nav class="nav flex-column">
                <a href="{{ route('districts.index') }}"
                   class="nav-link {{ request()->routeIs('districts.*') && !request()->routeIs('districts.customers.*') ? 'active' : '' }}">
                    <i class="bi bi-geo-alt"></i> უბნები
                </a>
                <a href="{{ route('managers.index') }}"
                   class="nav-link {{ request()->routeIs('managers.*') ? 'active' : '' }}">
                    <i class="bi bi-people"></i> მენეჯერები
                </a>
            </nav>
        </aside>

        <header class="topbar">
            <button class="btn btn-outline-secondary d-md-none me-auto" onclick="document.getElementById('sidebar').classList.toggle('show')">
                <i class="bi bi-list"></i>
            </button>
            <span class="text-muted small"><i class="bi bi-person-circle"></i> {{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-box-arrow-right"></i> გასვლა
                </button>
            </form>
        </header>

        <main class="content">
            @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle"></i> {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @yield('content')
        </main>
    @else
        <main class="d-flex align-items-center justify-content-center vh-100 p-3">
            @yield('content')
        </main>
    @endauth

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
