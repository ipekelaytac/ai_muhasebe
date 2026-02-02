@extends('layouts.admin')

@section('title', 'Hata')
@section('page-title', 'Bir Hata Oluştu')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
        <div class="mb-4">
            <i class="bi bi-exclamation-triangle text-warning" style="font-size: 4rem;"></i>
        </div>
        <h4 class="mb-3">Bir Hata Oluştu</h4>
        <p class="text-muted mb-4">{{ $message ?? 'Lütfen tekrar deneyin veya sistem yöneticisine başvurun.' }}</p>
        <div>
            <a href="{{ url()->previous() }}" class="btn btn-secondary me-2">
                <i class="bi bi-arrow-left me-1"></i>
                Geri Dön
            </a>
            <a href="{{ route('dashboard') }}" class="btn btn-primary">
                <i class="bi bi-house-door me-1"></i>
                Ana Sayfa
            </a>
        </div>
    </div>
</div>
@endsection
