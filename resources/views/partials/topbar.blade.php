<header class="bg-white border-bottom shadow-sm sticky-top" style="z-index: 1020;">
    <div class="d-flex align-items-center justify-content-between p-2 p-md-3">
        <div class="d-flex align-items-center flex-grow-1">
            <button class="btn btn-link d-lg-none me-2 p-1" onclick="toggleSidebar()" type="button">
                <i class="bi bi-list fs-4"></i>
            </button>
            <div class="flex-grow-1">
                <h5 class="mb-0 d-none d-md-block">@yield('page-title', 'Dashboard')</h5>
                <h6 class="mb-0 d-md-none">@yield('page-title', 'Dashboard')</h6>
                @hasSection('page-subtitle')
                    <small class="text-muted d-none d-md-inline">@yield('page-subtitle')</small>
                @endif
            </div>
        </div>
        
        <div class="d-flex align-items-center gap-2 gap-md-3">
            @if(Auth::user()->company)
                <div class="text-end d-none d-lg-block">
                    <div class="small fw-bold">{{ Auth::user()->company->name }}</div>
                    @if(Auth::user()->branch)
                        <div class="small text-muted">{{ Auth::user()->branch->name }}</div>
                    @endif
                </div>
            @endif
            
            <div class="dropdown">
                <button class="btn btn-link text-decoration-none dropdown-toggle p-1 p-md-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle fs-4"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('profile.index') }}"><i class="bi bi-person me-2"></i>Profil</a></li>
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

