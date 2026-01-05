<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Driver App</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .mobile-container {
            max-width: 600px;
            margin: 0 auto;
            min-height: 100vh;
            background: #fff;
            padding-bottom: 60px; /* Space for bottom nav */
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
        }
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: #fff;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: space-around;
            align-items: center;
            z-index: 1000;
            max-width: 600px;
            margin: 0 auto; /* Center nav on large screens if container is centered */
        }
        /* Fix bottom nav width on large screens */
        @media (min-width: 600px) {
            .bottom-nav {
                left: 50%;
                transform: translateX(-50%);
                width: 600px;
            }
        }
        .bottom-nav a {
            text-decoration: none;
            color: #6c757d;
            text-align: center;
            font-size: 0.8rem;
        }
        .bottom-nav a.active {
            color: #0d6efd;
        }
        .bottom-nav i {
            display: block;
            font-size: 1.5rem;
        }
        .content {
            padding: 15px;
        }
    </style>
</head>
<body>
    <div class="mobile-container">
        <!-- Header -->
        <nav class="navbar navbar-light bg-white border-bottom px-3">
            <span class="navbar-brand mb-0 h1">Duty App</span>
            @auth('driver')
                <form action="{{ route('driver.logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger">Logout</button>
                </form>
            @endauth
        </nav>

        <div class="content">
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

        @auth('driver')
        <div class="bottom-nav">
            <a href="{{ route('driver.dashboard') }}" class="{{ request()->routeIs('driver.dashboard') ? 'active' : '' }}">
                <i class="bi bi-house"></i> Home
            </a>
            <a href="{{ route('driver.history') }}" class="{{ request()->routeIs('driver.history') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i> History
            </a>
        </div>
        @endauth
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
