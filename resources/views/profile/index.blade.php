@extends('layouts.admin')

@section('title', 'Profil')
@section('page-title', 'Profil')
@section('page-subtitle', 'Profil bilgilerinizi ve şifrenizi yönetin')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-person-circle me-2"></i>Profil Bilgileri</h5>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Ad Soyad <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                                class="form-control @error('name') is-invalid @enderror">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">E-posta <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                                class="form-control @error('email') is-invalid @enderror">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Şirket</label>
                            <input type="text" value="{{ $user->company->name ?? '-' }}" readonly
                                class="form-control bg-light">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Şube</label>
                            <input type="text" value="{{ $user->branch->name ?? '-' }}" readonly
                                class="form-control bg-light">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>
                            Güncelle
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Şifre Değiştir</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('profile.update-password') }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Mevcut Şifre <span class="text-danger">*</span></label>
                        <input type="password" name="current_password" id="current_password" required
                            class="form-control @error('current_password') is-invalid @enderror">
                        @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Yeni Şifre <span class="text-danger">*</span></label>
                        <input type="password" name="password" id="password" required
                            class="form-control @error('password') is-invalid @enderror">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">En az 8 karakter olmalıdır.</small>
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Yeni Şifre (Tekrar) <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" id="password_confirmation" required
                            class="form-control">
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-key me-1"></i>
                            Şifreyi Değiştir
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 100px; height: 100px;">
                    <span class="text-white fw-bold" style="font-size: 2.5rem;">{{ substr($user->name ?? 'A', 0, 1) }}</span>
                </div>
                <h5 class="mb-1">{{ $user->name }}</h5>
                <p class="text-muted mb-2">{{ $user->email }}</p>
                @if($user->company)
                    <p class="text-muted mb-1">
                        <i class="bi bi-building me-1"></i>{{ $user->company->name }}
                    </p>
                @endif
                @if($user->branch)
                    <p class="text-muted mb-0">
                        <i class="bi bi-geo-alt me-1"></i>{{ $user->branch->name }}
                    </p>
                @endif
                @if($user->is_admin)
                    <span class="badge bg-danger mt-2">Yönetici</span>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

