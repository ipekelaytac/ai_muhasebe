<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Muhasebe Sistemi')</title>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar-link {
            transition: all 0.2s ease;
        }
        .sidebar-link:hover {
            transform: translateX(4px);
        }
        .sidebar-link.active {
            background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .card {
            transition: all 0.3s ease;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .table-row {
            transition: all 0.2s ease;
        }
        .table-row:hover {
            background-color: #f9fafb;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .alert {
            animation: slideIn 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-gradient-to-b from-gray-900 to-gray-800 text-white flex-shrink-0 shadow-2xl">
            <div class="p-6 border-b border-gray-700">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center shadow-lg">
                        <i class="fas fa-calculator text-white text-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-white">Muhasebe</h1>
                        <p class="text-xs text-gray-400">Yönetim Paneli</p>
                    </div>
                </div>
            </div>
            <nav class="mt-6 px-3">
                <a href="{{ route('dashboard') }}" class="sidebar-link flex items-center space-x-3 px-4 py-3 mb-2 rounded-lg {{ request()->routeIs('dashboard') ? 'active' : 'hover:bg-gray-700' }}">
                    <i class="fas fa-home w-5"></i>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('admin.companies.index') }}" class="sidebar-link flex items-center space-x-3 px-4 py-3 mb-2 rounded-lg {{ request()->routeIs('admin.companies.*') ? 'active' : 'hover:bg-gray-700' }}">
                    <i class="fas fa-building w-5"></i>
                    <span>Şirketler</span>
                </a>
                <a href="{{ route('admin.branches.index') }}" class="sidebar-link flex items-center space-x-3 px-4 py-3 mb-2 rounded-lg {{ request()->routeIs('admin.branches.*') ? 'active' : 'hover:bg-gray-700' }}">
                    <i class="fas fa-sitemap w-5"></i>
                    <span>Şubeler</span>
                </a>
                <a href="{{ route('admin.employees.index') }}" class="sidebar-link flex items-center space-x-3 px-4 py-3 mb-2 rounded-lg {{ request()->routeIs('admin.employees.*') ? 'active' : 'hover:bg-gray-700' }}">
                    <i class="fas fa-users w-5"></i>
                    <span>Çalışanlar</span>
                </a>
                <a href="{{ route('admin.contracts.index') }}" class="sidebar-link flex items-center space-x-3 px-4 py-3 mb-2 rounded-lg {{ request()->routeIs('admin.contracts.*') ? 'active' : 'hover:bg-gray-700' }}">
                    <i class="fas fa-file-contract w-5"></i>
                    <span>Sözleşmeler</span>
                </a>
                <a href="{{ route('admin.payroll.index') }}" class="sidebar-link flex items-center space-x-3 px-4 py-3 mb-2 rounded-lg {{ request()->routeIs('admin.payroll.*') ? 'active' : 'hover:bg-gray-700' }}">
                    <i class="fas fa-money-check-alt w-5"></i>
                    <span>Bordro</span>
                </a>
                <a href="{{ route('admin.finance.transactions.index') }}" class="sidebar-link flex items-center space-x-3 px-4 py-3 mb-2 rounded-lg {{ request()->routeIs('admin.finance.*') ? 'active' : 'hover:bg-gray-700' }}">
                    <i class="fas fa-chart-line w-5"></i>
                    <span>Finans</span>
                </a>
            </nav>
            <div class="absolute bottom-0 w-full p-4 border-t border-gray-700">
                @auth
                    <div class="flex items-center space-x-3 mb-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-400 to-blue-500 rounded-full flex items-center justify-center">
                            <span class="text-white font-semibold">{{ substr(Auth::user()->name, 0, 1) }}</span>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-white">{{ Auth::user()->name }}</p>
                            @if(Auth::user()->company)
                                <p class="text-xs text-gray-400">{{ Auth::user()->company->name }}</p>
                            @endif
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center justify-center space-x-2 px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Çıkış Yap</span>
                        </button>
                    </form>
                @endauth
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Topbar -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="px-6 py-4 flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">@yield('page-title', 'Dashboard')</h2>
                        <p class="text-sm text-gray-500 mt-1">@yield('page-subtitle', 'Genel Bakış')</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <button class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">
                                <i class="fas fa-bell"></i>
                            </button>
                        </div>
                        <div class="h-8 w-px bg-gray-300"></div>
                        <div class="flex items-center space-x-3">
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-900">{{ now()->format('d F Y') }}</p>
                                <p class="text-xs text-gray-500">{{ now()->format('H:i') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <main class="flex-1 overflow-y-auto bg-gray-50 p-6">
                @if(session('success'))
                    <div class="alert mb-6 bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded-lg shadow-sm flex items-center">
                        <i class="fas fa-check-circle mr-3 text-green-500"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert mb-6 bg-gradient-to-r from-red-50 to-rose-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-lg shadow-sm flex items-center">
                        <i class="fas fa-exclamation-circle mr-3 text-red-500"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert mb-6 bg-gradient-to-r from-red-50 to-rose-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-lg shadow-sm">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-exclamation-triangle mr-2 text-red-500"></i>
                            <span class="font-semibold">Hata!</span>
                        </div>
                        <ul class="list-disc list-inside ml-6">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>

