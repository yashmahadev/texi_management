<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duty Management - Admin</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
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
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 p-0 sidebar">
                <div class="p-3 text-white">
                    <h4>Admin Panel</h4>
                    <small>{{ Auth::user()->name }}</small>
                </div>
                <hr class="text-white">
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
                    <hr class="text-white opacity-25">
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
                    <form action="{{ route('admin.logout') }}" method="POST" class="mt-3">
                        @csrf
                        <button type="submit" class="btn btn-link text-white w-100 text-start">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </button>
                    </form>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                @yield('content')
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
