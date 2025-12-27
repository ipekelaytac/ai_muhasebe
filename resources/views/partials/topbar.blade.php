<header class="bg-white border-bottom shadow-sm">
    <div class="d-flex align-items-center justify-content-between p-3">
        <div class="d-flex align-items-center">
            <button class="btn btn-link d-md-none me-2" onclick="toggleSidebar()">
                <i class="bi bi-list fs-4"></i>
            </button>
            <div>
                <h4 class="mb-0">@yield('page-title', 'Dashboard')</h4>
                @hasSection('page-subtitle')
                    <small class="text-muted">@yield('page-subtitle')</small>
                @endif
            </div>
        </div>
        
        <div class="d-flex align-items-center gap-3">
            @if(Auth::user()->company)
                <div class="text-end d-none d-md-block">
                    <div class="small fw-bold">{{ Auth::user()->company->name }}</div>
                    @if(Auth::user()->branch)
                        <div class="small text-muted">{{ Auth::user()->branch->name }}</div>
                    @endif
                </div>
            @endif
            
            <div class="dropdown">
                <button class="btn btn-link text-decoration-none dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle fs-4"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profil</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="bi bi-box-arrow-right me-2"></i>Çıkış Yap
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>

