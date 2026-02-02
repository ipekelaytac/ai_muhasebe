<aside class="sidebar bg-dark text-white d-flex flex-column" id="sidebar">
    <div class="sidebar-header p-3 border-bottom border-secondary flex-shrink-0">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <div class="bg-primary rounded p-2 me-2">
                    <i class="bi bi-calculator text-white"></i>
                </div>
                <div>
                    <h5 class="mb-0 text-white">Muhasebe</h5>
                    <small class="text-secondary d-none d-md-inline">Yönetim Paneli</small>
                </div>
            </div>
            <button class="btn btn-link text-white d-lg-none p-1 ms-auto" onclick="toggleSidebar()" type="button">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>
    
    <nav class="sidebar-nav p-2 p-md-3">
        <a href="{{ route('dashboard') }}" class="nav-link text-white d-flex align-items-center mb-2 rounded {{ request()->routeIs('dashboard') ? 'bg-primary' : 'hover-bg-secondary' }}" style="padding: 0.5rem;" onclick="closeSidebarOnMobile()">
            <i class="bi bi-house-door me-2"></i>
            <span>Dashboard</span>
        </a>
        
        <a href="{{ route('admin.payroll.index') }}" class="nav-link text-white d-flex align-items-center mb-2 rounded {{ request()->routeIs('admin.payroll.*') ? 'bg-primary' : 'hover-bg-secondary' }}" style="padding: 0.5rem;" onclick="closeSidebarOnMobile()">
            <i class="bi bi-cash-coin me-2"></i>
            <span>Bordro</span>
        </a>
        
        <a href="{{ route('admin.meal-allowance.index') }}" class="nav-link text-white d-flex align-items-center mb-2 rounded {{ request()->routeIs('admin.meal-allowance.*') ? 'bg-primary' : 'hover-bg-secondary' }}" style="padding: 0.5rem;" onclick="closeSidebarOnMobile()">
            <i class="bi bi-egg-fried me-2"></i>
            <span>Yemek Yardımı</span>
        </a>
        
        <a href="{{ route('admin.salary-calculator.index') }}" class="nav-link text-white d-flex align-items-center mb-2 rounded {{ request()->routeIs('admin.salary-calculator.*') ? 'bg-primary' : 'hover-bg-secondary' }}" style="padding: 0.5rem;" onclick="closeSidebarOnMobile()">
            <i class="bi bi-calculator me-2"></i>
            <span>Maaş Hesaplama</span>
        </a>
        
        <a href="{{ route('admin.cost-calculator.index') }}" class="nav-link text-white d-flex align-items-center mb-2 rounded {{ request()->routeIs('admin.cost-calculator.*') ? 'bg-primary' : 'hover-bg-secondary' }}" style="padding: 0.5rem;" onclick="closeSidebarOnMobile()">
            <i class="bi bi-cash-stack me-2"></i>
            <span>Maliyet Hesaplayıcı</span>
        </a>
        
        {{-- Accounting System Menu --}}
        <div class="text-secondary small fw-bold text-uppercase mt-3 mb-2 px-2" style="font-size: 0.7rem;">Muhasebe</div>
        
        <a href="{{ route('accounting.parties.index') }}" class="nav-link text-white d-flex align-items-center mb-2 rounded {{ request()->routeIs('accounting.parties.*') ? 'bg-primary' : 'hover-bg-secondary' }}" style="padding: 0.5rem;" onclick="closeSidebarOnMobile()">
            <i class="bi bi-person-badge me-2"></i>
            <span>Cariler</span>
        </a>
        
        <a href="{{ route('accounting.documents.index') }}" class="nav-link text-white d-flex align-items-center mb-2 rounded {{ request()->routeIs('accounting.documents.*') ? 'bg-primary' : 'hover-bg-secondary' }}" style="padding: 0.5rem;" onclick="closeSidebarOnMobile()">
            <i class="bi bi-file-earmark-text me-2"></i>
            <span>Tahakkuklar</span>
        </a>
        
        <a href="{{ route('accounting.payments.index') }}" class="nav-link text-white d-flex align-items-center mb-2 rounded {{ request()->routeIs('accounting.payments.*') ? 'bg-primary' : 'hover-bg-secondary' }}" style="padding: 0.5rem;" onclick="closeSidebarOnMobile()">
            <i class="bi bi-cash-coin me-2"></i>
            <span>Ödeme / Tahsilat</span>
        </a>
        
        <a href="{{ route('accounting.overtime.index') }}" class="nav-link text-white d-flex align-items-center mb-2 rounded {{ request()->routeIs('accounting.overtime.*') ? 'bg-primary' : 'hover-bg-secondary' }}" style="padding: 0.5rem;" onclick="closeSidebarOnMobile()">
            <i class="bi bi-clock-history me-2"></i>
            <span>Mesai Girişi</span>
        </a>
        
        <a href="{{ route('accounting.cash.index') }}" class="nav-link text-white d-flex align-items-center mb-2 rounded {{ request()->routeIs('accounting.cash.*') ? 'bg-primary' : 'hover-bg-secondary' }}" style="padding: 0.5rem;" onclick="closeSidebarOnMobile()">
            <i class="bi bi-bank me-2"></i>
            <span>Kasa & Bankalar</span>
        </a>
        
        <a href="{{ route('accounting.cheques.index') }}" class="nav-link text-white d-flex align-items-center mb-2 rounded {{ request()->routeIs('accounting.cheques.*') ? 'bg-primary' : 'hover-bg-secondary' }}" style="padding: 0.5rem;" onclick="closeSidebarOnMobile()">
            <i class="bi bi-receipt me-2"></i>
            <span>Çek / Senet</span>
        </a>
        
        <a href="{{ route('accounting.reports.index') }}" class="nav-link text-white d-flex align-items-center mb-2 rounded {{ request()->routeIs('accounting.reports.*') ? 'bg-primary' : 'hover-bg-secondary' }}" style="padding: 0.5rem;" onclick="closeSidebarOnMobile()">
            <i class="bi bi-graph-up me-2"></i>
            <span>Raporlar</span>
        </a>
        
        <a href="{{ route('accounting.periods.index') }}" class="nav-link text-white d-flex align-items-center mb-2 rounded {{ request()->routeIs('accounting.periods.*') ? 'bg-primary' : 'hover-bg-secondary' }}" style="padding: 0.5rem;" onclick="closeSidebarOnMobile()">
            <i class="bi bi-lock me-2"></i>
            <span>Dönem Kilit</span>
        </a>
        
        <a href="{{ route('admin.employees.index') }}" class="nav-link text-white d-flex align-items-center mb-2 rounded {{ request()->routeIs('admin.employees.*') ? 'bg-primary' : 'hover-bg-secondary' }}" style="padding: 0.5rem;" onclick="closeSidebarOnMobile()">
            <i class="bi bi-people me-2"></i>
            <span>Çalışanlar</span>
        </a>
        
        <a href="{{ route('admin.contracts.index') }}" class="nav-link text-white d-flex align-items-center mb-2 rounded {{ request()->routeIs('admin.contracts.*') ? 'bg-primary' : 'hover-bg-secondary' }}" style="padding: 0.5rem;" onclick="closeSidebarOnMobile()">
            <i class="bi bi-file-earmark-text me-2"></i>
            <span>Sözleşmeler</span>
        </a>
        
        <a href="{{ route('admin.reports.index') }}" class="nav-link text-white d-flex align-items-center mb-2 rounded {{ request()->routeIs('admin.reports.*') ? 'bg-primary' : 'hover-bg-secondary' }}" style="padding: 0.5rem;" onclick="closeSidebarOnMobile()">
            <i class="bi bi-file-earmark-bar-graph me-2"></i>
            <span>Raporlar</span>
        </a>
        
        <a href="{{ route('admin.branches.index') }}" class="nav-link text-white d-flex align-items-center mb-2 rounded {{ request()->routeIs('admin.branches.*') || request()->routeIs('admin.payroll.deduction-types.*') ? 'bg-primary' : 'hover-bg-secondary' }}" style="padding: 0.5rem;" onclick="closeSidebarOnMobile()">
            <i class="bi bi-gear me-2"></i>
            <span>Ayarlar</span>
        </a>
    </nav>
    
    <div class="sidebar-footer p-2 p-md-3 border-top border-secondary bg-dark">
        @auth
            <div class="d-flex align-items-center mb-3">
                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                    <span class="text-white fw-bold">{{ substr(Auth::user()->name ?? 'A', 0, 1) }}</span>
                </div>
                <div class="flex-grow-1">
                    <div class="text-white small fw-bold">{{ Auth::user()->name ?? 'Kullanıcı' }}</div>
                    @if(Auth::user()->company)
                        <div class="text-secondary small">{{ Auth::user()->company->name }}</div>
                    @endif
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-danger btn-sm w-100">
                    <i class="bi bi-box-arrow-right me-1"></i>
                    Çıkış
                </button>
            </form>
        @endauth
    </div>
</aside>

