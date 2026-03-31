<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0d6efd">
    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <link rel="shortcut icon" href="/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
    <link rel="stylesheet" href="{{ asset('css/responsive.css') }}">

    <style>
        :root { --sidebar-width: 260px; }

        /* ── Sidebar ── */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background-color: #343a40;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            transition: left 0.3s;
            display: flex;
            flex-direction: column;
            overflow: hidden; /* outer wrapper never scrolls */
        }

        /* Logo / user header — fixed height, never scrolls */
        .sidebar-header {
            flex-shrink: 0;
            padding: 16px;
            color: #fff;
            border-bottom: 1px solid rgba(255,255,255,.1);
        }

        /* Nav area — scrollable when content overflows */
        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding-bottom: 8px;
        }
        .sidebar-nav::-webkit-scrollbar { width: 4px; }
        .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.2); border-radius: 2px; }

        .sidebar a {
            color: #fff;
            text-decoration: none;
            padding: 10px 15px;
            display: block;
            white-space: nowrap;
        }
        .sidebar a:hover, .sidebar a.active { background-color: #495057; }

        /* User dropdown in sidebar */
        .sidebar-user-btn {
            background: none;
            border: none;
            color: #fff;
            padding: 0;
            width: 100%;
            text-align: left;
            cursor: pointer;
        }
        .sidebar-user-btn:hover { opacity: .85; }
        .sidebar-dropdown {
            display: none;
            background: #495057;
            border-radius: 4px;
            margin-top: 6px;
        }
        .sidebar-dropdown.show { display: block; }
        .sidebar-dropdown a, .sidebar-dropdown button {
            padding: 8px 14px;
            font-size: .875rem;
            display: block;
            width: 100%;
            text-align: left;
            background: none;
            border: none;
            color: #fff;
            text-decoration: none;
            cursor: pointer;
        }
        .sidebar-dropdown a:hover, .sidebar-dropdown button:hover { background: #6c757d; }

        /* ── Main content ── */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: margin-left 0.3s;
            min-height: 100vh;
        }

        /* ── Mobile header ── */
        .mobile-header {
            display: none;
            background-color: #343a40;
            padding: 10px 15px;
            color: white;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        /* ── Responsive ── */
        @media (max-width: 991.98px) {
            .sidebar { left: calc(-1 * var(--sidebar-width)); }
            .sidebar.show { left: 0; }
            .main-content { margin-left: 0; }
            .mobile-header { display: flex; justify-content: space-between; align-items: center; }
            .sidebar-overlay {
                display: none;
                position: fixed; inset: 0;
                background: rgba(0,0,0,.5);
                z-index: 998;
            }
            .sidebar-overlay.show { display: block; }
        }

        /* ── Table responsive helpers ── */
        @media (max-width: 767.98px) {
            .table-responsive .btn-group { flex-direction: column; gap: 2px; }
            .card-body { padding: 12px; }
        }
    </style>
</head>
<body>

{{-- Mobile top bar --}}
<div class="mobile-header d-lg-none">
    @include('components.logo', ['iconClass' => 'fs-4 text-warning', 'textClass' => 'fs-5 text-white fw-bold'])
    <button class="btn btn-outline-light btn-sm" id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

{{-- Sidebar --}}
<div class="sidebar" id="adminSidebar">

    {{-- Header: logo + user dropdown --}}
    <div class="sidebar-header">
        <div class="d-none d-lg-block mb-3">
            @include('components.logo', ['iconClass' => 'fs-3 text-warning', 'textClass' => 'fs-5 text-white fw-bold', 'wrapperClass' => 'justify-content-start'])
        </div>

        {{-- User button --}}
        <button class="sidebar-user-btn d-flex align-items-center gap-2" id="userDropdownBtn">
            <i class="bi bi-person-circle fs-4"></i>
            <div class="flex-grow-1 overflow-hidden">
                <div class="fw-bold small text-truncate">{{ Auth::user()->name }}</div>
                <div class="text-white-50" style="font-size:.72rem;">
                    {{ Auth::user()->getRoleNames()->first() ?? 'Admin' }}
                </div>
            </div>
            <i class="bi bi-chevron-down small" id="userChevron"></i>
        </button>

        {{-- Dropdown --}}
        <div class="sidebar-dropdown" id="userDropdown">
            <a href="{{ route('admin.profile.index') }}">
                <i class="bi bi-person me-2"></i> My Account
            </a>
            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit">
                    <i class="bi bi-box-arrow-right me-2 text-danger"></i> Logout
                </button>
            </form>
        </div>
    </div>

    {{-- Scrollable nav --}}
    <nav class="sidebar-nav">
        @can('view_admin_dashboard')
        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2 me-2"></i> Dashboard
        </a>
        @endcan

        @can('view_monthly_duties')
        <a href="{{ route('admin.monthly-duties.index') }}" class="{{ request()->routeIs('admin.monthly-duties.*') ? 'active' : '' }}">
            <i class="bi bi-calendar-event me-2"></i> Monthly Duties
        </a>
        @endcan

        @can('view_daily_logs')
        <a href="{{ route('admin.daily-logs.index') }}" class="{{ request()->routeIs('admin.daily-logs.*') ? 'active' : '' }}">
            <i class="bi bi-list-check me-2"></i> Daily Logs
        </a>
        @endcan

        @can('view_replacements')
        <a href="{{ route('admin.replacements.index') }}" class="{{ request()->routeIs('admin.replacements.*') ? 'active' : '' }}">
            <i class="bi bi-person-gear me-2"></i> Replacements
        </a>
        @endcan

        @can('view_vehicles')
        <a href="{{ route('admin.vehicles.index') }}" class="{{ request()->routeIs('admin.vehicles.*') ? 'active' : '' }}">
            <i class="bi bi-car-front me-2"></i> Vehicles
        </a>
        @endcan

        @if(auth()->user()->can('view_customers') || auth()->user()->can('view_direct_bookings'))
        <hr class="text-white opacity-25 mx-3">
        <div class="px-3 mb-1 text-uppercase small text-white-50">Direct Bookings</div>
        @endif

        @can('view_customers')
        <a href="{{ route('admin.customers.index') }}" class="{{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">
            <i class="bi bi-people me-2"></i> Customers
        </a>
        @endcan

        @can('view_direct_bookings')
        <a href="{{ route('admin.direct-bookings.index') }}" class="{{ request()->routeIs('admin.direct-bookings.*') ? 'active' : '' }}">
            <i class="bi bi-calendar-check me-2"></i> Direct Bookings
        </a>
        @endcan

        @can('view_reports')
        <a href="{{ route('admin.reports.index') }}" class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
            <i class="bi bi-file-earmark-pdf me-2"></i> Reports
        </a>
        @endcan

        @can('view_audit_logs')
        <a href="{{ route('admin.audit-logs.index') }}" class="{{ request()->routeIs('admin.audit-logs.index') ? 'active' : '' }}">
            <i class="bi bi-journal-text me-2"></i> Audit Logs
        </a>
        @endcan

        @if(auth()->user()->can('manage_roles') || auth()->user()->can('manage_permissions'))
        <hr class="text-white opacity-25 mx-3">
        <div class="px-3 mb-1 text-uppercase small text-white-50">Access Control</div>
        @endif

        @can('manage_roles')
        <a href="{{ route('admin.roles.index') }}" class="{{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
            <i class="bi bi-shield-lock me-2"></i> Roles
        </a>
        @endcan

        @can('manage_permissions')
        <a href="{{ route('admin.permissions.index') }}" class="{{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}">
            <i class="bi bi-key me-2"></i> Permissions
        </a>
        @endcan

        @can('manage_settings')
        <a href="{{ route('admin.settings.index') }}" class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
            <i class="bi bi-gear me-2"></i> Settings
        </a>
        @endcan
    </nav>
</div>

{{-- Main Content --}}
<div class="main-content">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @yield('content')
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function () {
    // Mobile sidebar toggle
    $('#sidebarToggle, #sidebarOverlay').on('click', function () {
        $('#adminSidebar').toggleClass('show');
        $('#sidebarOverlay').toggleClass('show');
    });
    if ($(window).width() < 992) {
        $('.sidebar-nav a').on('click', function () {
            $('#adminSidebar').removeClass('show');
            $('#sidebarOverlay').removeClass('show');
        });
    }

    // User dropdown toggle
    $('#userDropdownBtn').on('click', function () {
        $('#userDropdown').toggleClass('show');
        $('#userChevron').toggleClass('bi-chevron-down bi-chevron-up');
    });
});

if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/firebase-messaging-sw.js?v={{ time() }}')
        .then(reg => console.log('SW registered'))
        .catch(err => console.error('SW failed', err));
}
</script>
@include('partials.fcm-scripts', ['guard' => 'web'])
@stack('scripts')
</body>
</html>
