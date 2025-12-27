<aside class="sidebar bg-dark text-white" id="sidebar" style="width: 250px;">
    <div class="p-3 border-bottom border-secondary">
        <div class="d-flex align-items-center">
            <div class="bg-primary rounded p-2 me-2">
                <i class="bi bi-calculator text-white"></i>
            </div>
            <div>
                <h5 class="mb-0 text-white">Muhasebe</h5>
                <small class="text-secondary">Yönetim Paneli</small>
            </div>
        </div>
    </div>
    
    <nav class="p-3">
        <a href="{{ route('dashboard') }}" class="nav-link text-white d-flex align-items-center mb-2 rounded {{ request()->routeIs('dashboard') ? 'bg-primary' : 'hover-bg-secondary' }}" style="padding: 0.5rem;">
            <i class="bi bi-house-door me-2"></i>
            <span>Dashboard</span>
        </a>
        
        <a href="{{ route('admin.payroll.index') }}" class="nav-link text-white d-flex align-items-center mb-2 rounded {{ request()->routeIs('admin.payroll.*') ? 'bg-primary' : 'hover-bg-secondary' }}" style="padding: 0.5rem;">
            <i class="bi bi-cash-coin me-2"></i>
            <span>Bordro</span>
        </a>
        
        <a href="{{ route('admin.meal-allowance.index') }}" class="nav-link text-white d-flex align-items-center mb-2 rounded {{ request()->routeIs('admin.meal-allowance.*') ? 'bg-primary' : 'hover-bg-secondary' }}" style="padding: 0.5rem;">
            <i class="bi bi-egg-fried me-2"></i>
            <span>Yemek Yardımı</span>
        </a>
        
        <a href="{{ route('admin.salary-calculator.index') }}" class="nav-link text-white d-flex align-items-center mb-2 rounded {{ request()->routeIs('admin.salary-calculator.*') ? 'bg-primary' : 'hover-bg-secondary' }}" style="padding: 0.5rem;">
            <i class="bi bi-calculator me-2"></i>
            <span>Maaş Hesaplama</span>
        </a>
        
        <a href="{{ route('admin.employees.index') }}" class="nav-link text-white d-flex align-items-center mb-2 rounded {{ request()->routeIs('admin.employees.*') ? 'bg-primary' : 'hover-bg-secondary' }}" style="padding: 0.5rem;">
            <i class="bi bi-people me-2"></i>
            <span>Çalışanlar</span>
        </a>
        
        <a href="{{ route('admin.contracts.index') }}" class="nav-link text-white d-flex align-items-center mb-2 rounded {{ request()->routeIs('admin.contracts.*') ? 'bg-primary' : 'hover-bg-secondary' }}" style="padding: 0.5rem;">
            <i class="bi bi-file-earmark-text me-2"></i>
            <span>Sözleşmeler</span>
        </a>
        
        <a href="{{ route('admin.advances.index') }}" class="nav-link text-white d-flex align-items-center mb-2 rounded {{ request()->routeIs('admin.advances.*') ? 'bg-primary' : 'hover-bg-secondary' }}" style="padding: 0.5rem;">
            <i class="bi bi-cash-stack me-2"></i>
            <span>Avanslar</span>
        </a>
        
        <a href="{{ route('admin.finance.transactions.index') }}" class="nav-link text-white d-flex align-items-center mb-2 rounded {{ request()->routeIs('admin.finance.*') ? 'bg-primary' : 'hover-bg-secondary' }}" style="padding: 0.5rem;">
            <i class="bi bi-graph-up me-2"></i>
            <span>Finans</span>
        </a>
        
        <a href="{{ route('admin.companies.index') }}" class="nav-link text-white d-flex align-items-center mb-2 rounded {{ request()->routeIs('admin.companies.*') || request()->routeIs('admin.branches.*') || request()->routeIs('admin.payroll.deduction-types.*') ? 'bg-primary' : 'hover-bg-secondary' }}" style="padding: 0.5rem;">
            <i class="bi bi-gear me-2"></i>
            <span>Ayarlar</span>
        </a>
    </nav>
    
    <div class="position-absolute bottom-0 w-100 p-3 border-top border-secondary">
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

