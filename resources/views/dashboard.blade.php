@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Genel bakış ve istatistikler')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="bg-primary bg-opacity-10 rounded p-2">
                        <i class="bi bi-people fs-4 text-primary"></i>
                    </div>
                    <span class="badge bg-success">Aktif</span>
                </div>
                <h6 class="text-muted mb-1 small">Toplam Çalışan</h6>
                <h3 class="mb-0">{{ \App\Models\Employee::where('status', 1)->count() }}</h3>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="bg-success bg-opacity-10 rounded p-2">
                        <i class="bi bi-cash-coin fs-4 text-success"></i>
                    </div>
                    <span class="badge bg-success">Açık</span>
                </div>
                <h6 class="text-muted mb-1 small">Aktif Bordro</h6>
                <h3 class="mb-0">{{ \App\Models\PayrollPeriod::where('status', 1)->count() }}</h3>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="bg-info bg-opacity-10 rounded p-2">
                        <i class="bi bi-arrow-up-circle fs-4 text-info"></i>
                    </div>
                    <span class="badge bg-secondary">Bu Ay</span>
                </div>
                <h6 class="text-muted mb-1 small">Toplam Gelir</h6>
                <h3 class="mb-0">
                    {{ number_format(\App\Domain\Accounting\Models\Document::where('direction', 'receivable')
                        ->whereYear('document_date', now()->year)
                        ->whereMonth('document_date', now()->month)
                        ->whereNotIn('status', ['cancelled', 'reversed'])
                        ->sum('total_amount'), 0) }} ₺
                </h3>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="bg-danger bg-opacity-10 rounded p-2">
                        <i class="bi bi-arrow-down-circle fs-4 text-danger"></i>
                    </div>
                    <span class="badge bg-secondary">Bu Ay</span>
                </div>
                <h6 class="text-muted mb-1 small">Toplam Gider</h6>
                <h3 class="mb-0">
                    {{ number_format(\App\Domain\Accounting\Models\Document::where('direction', 'payable')
                        ->whereYear('document_date', now()->year)
                        ->whereMonth('document_date', now()->month)
                        ->whereNotIn('status', ['cancelled', 'reversed'])
                        ->sum('total_amount'), 0) }} ₺
                </h3>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between">
        <h5 class="mb-0">
            <i class="bi bi-clock-history me-2 text-primary"></i>
            Son İşlemler
        </h5>
        {{-- Deprecated: Use Accounting API instead --}}
        {{-- <a href="{{ route('admin.finance.transactions.index') }}" class="btn btn-sm btn-link text-decoration-none">
            Tümünü Gör <i class="bi bi-arrow-right ms-1"></i>
        </a> --}}
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tarih</th>
                        <th>Tip</th>
                        <th>Kategori</th>
                        <th class="text-end">Tutar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(\App\Domain\Accounting\Models\Document::latest()->take(10)->get() as $document)
                        <tr>
                            <td>
                                <div class="fw-medium">{{ $document->document_date->format('d.m.Y') }}</div>
                                <small class="text-muted">{{ $document->created_at->format('H:i') }}</small>
                            </td>
                            <td>
                                <span class="badge {{ $document->direction === 'receivable' ? 'bg-success' : 'bg-danger' }}">
                                    {{ $document->direction === 'receivable' ? 'Gelir' : 'Gider' }}
                                </span>
                            </td>
                            <td>{{ $document->category->name ?? 'Kategorisiz' }}</td>
                            <td class="text-end">
                                <span class="fw-bold {{ $document->direction === 'receivable' ? 'text-success' : 'text-danger' }}">
                                    {{ $document->direction === 'receivable' ? '+' : '-' }}{{ number_format($document->total_amount, 2) }} ₺
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                                <p class="text-muted mb-0">Henüz işlem bulunmuyor</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
