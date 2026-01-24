<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duty Management - Admin</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0d6efd">

    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <link rel="shortcut icon" href="/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
    <link rel="manifest" href="/manifest.json" />
    
    <style>
        :root {
            --sidebar-width: 260px;
        }
        .sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background-color: #343a40;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            transition: all 0.3s;
        }
        .sidebar a {
            color: #fff;
            text-decoration: none;
            padding: 10px 15px;
            display: block;
        }
        .sidebar a:hover, .sidebar a.active {
            background-color: #495057;
        }
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s;
        }
        
        .mobile-header {
            display: none;
            background-color: #343a40;
            padding: 10px 15px;
            color: white;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        /* Responsive Styles */
        @media (max-width: 991.98px) {
            .sidebar {
                left: calc(-1 * var(--sidebar-width));
            }
            .sidebar.show {
                left: 0;
            }
            .main-content {
                margin-left: 0;
            }
            .mobile-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 998;
            }
            .sidebar-overlay.show {
                display: block;
            }
        }
    </style>
</head>
<body>
    <div class="mobile-header d-lg-none">
        <h5 class="mb-0">Admin Panel</h5>
        <button class="btn btn-outline-light btn-sm" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="sidebar" id="adminSidebar">
        <div class="p-3 text-white">
            <h4 class="d-none d-lg-block">Admin Panel</h4>
            <div class="d-flex align-items-center">
                <i class="bi bi-person-circle fs-4 me-2"></i>
                <div>
                    <div class="fw-bold small">{{ Auth::user()->name }}</div>
                    <div class="text-white-50" style="font-size: 0.75rem;">Administrator</div>
                </div>
            </div>
        </div>
        <hr class="text-white mx-3">
        <nav>
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
            <div class="px-3 mb-2 text-uppercase small text-white-50">Access Control</div>
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
            
            <hr class="text-white opacity-25 mx-3">
            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-link text-white w-100 text-start text-decoration-none px-3">
                    <i class="bi bi-box-arrow-right me-2 text-danger"></i> Logout
                </button>
            </form>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        
        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#sidebarToggle, #sidebarOverlay').on('click', function() {
                $('#adminSidebar').toggleClass('show');
                $('#sidebarOverlay').toggleClass('show');
            });

            // Close sidebar on mobile when a link is clicked
            if ($(window).width() < 992) {
                $('.sidebar a').on('click', function() {
                    $('#adminSidebar').removeClass('show');
                    $('#sidebarOverlay').removeClass('show');
                });
            }
        });

        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/firebase-messaging-sw.js?v={{ time() }}')
                .then(reg => console.log('Service Worker registered'))
                .catch(err => console.error('SW registration failed', err));
        }

    </script>
    @include('partials.fcm-scripts', ['guard' => 'web'])
    @stack('scripts')
</body>
</html>
