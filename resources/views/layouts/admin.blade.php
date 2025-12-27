<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Muhasebe Sistemi')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            overflow-x: hidden;
        }
        .sidebar {
            min-height: 100vh;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }
        @media (max-width: 991.98px) {
            .sidebar {
                position: fixed;
                z-index: 1050;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                height: 100vh;
                width: 280px !important;
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .sidebar-backdrop {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 1040;
            }
            .sidebar-backdrop.show {
                display: block;
            }
            main {
                padding: 1rem !important;
            }
            .card {
                margin-bottom: 1rem;
            }
            .table-responsive {
                font-size: 0.875rem;
            }
            .btn-group {
                flex-wrap: wrap;
            }
            .btn-group .btn {
                margin-bottom: 0.25rem;
            }
        }
        @media (max-width: 575.98px) {
            main {
                padding: 0.75rem !important;
            }
            .card-body {
                padding: 1rem !important;
            }
            .table {
                font-size: 0.8rem;
            }
            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body class="bg-light">
    <div class="d-flex">
        @include('partials.sidebar')
        
        <div class="flex-grow-1 d-flex flex-column">
            @include('partials.topbar')
            
            <main class="flex-grow-1 p-3 p-md-4">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Hata!</strong>
                        <ul class="mb-0 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleSidebar()"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            sidebar.classList.toggle('show');
            backdrop.classList.toggle('show');
        }
        
        function closeSidebarOnMobile() {
            if (window.innerWidth < 992) {
                const sidebar = document.getElementById('sidebar');
                const backdrop = document.getElementById('sidebarBackdrop');
                sidebar.classList.remove('show');
                backdrop.classList.remove('show');
            }
        }
        
        // Close sidebar when clicking backdrop
        document.getElementById('sidebarBackdrop').addEventListener('click', function() {
            closeSidebarOnMobile();
        });
        
        // Close sidebar on window resize if it becomes desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 992) {
                const sidebar = document.getElementById('sidebar');
                const backdrop = document.getElementById('sidebarBackdrop');
                sidebar.classList.remove('show');
                backdrop.classList.remove('show');
            }
        });
    </script>
    @yield('scripts')
</body>
</html>

