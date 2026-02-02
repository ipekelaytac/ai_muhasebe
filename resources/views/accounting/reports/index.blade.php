@extends('layouts.admin')

@section('title', 'Raporlar')
@section('page-title', 'Raporlar')
@section('page-subtitle', 'Muhasebe raporları')

@section('content')
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-cash-stack me-2"></i>Kasa & Banka Bakiyeleri
                </h5>
                <p class="card-text text-muted">Güncel kasa ve banka hesap bakiyelerini görüntüleyin.</p>
                <a href="{{ route('accounting.reports.cash-bank-balance') }}" class="btn btn-primary">
                    <i class="bi bi-arrow-right me-1"></i>Raporu Görüntüle
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-clock-history me-2"></i>Alacak Yaşlandırma
                </h5>
                <p class="card-text text-muted">Alacakların vade durumuna göre yaşlandırma raporu.</p>
                <a href="{{ route('accounting.reports.receivables-aging') }}" class="btn btn-primary">
                    <i class="bi bi-arrow-right me-1"></i>Raporu Görüntüle
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-clock-history me-2"></i>Borç Yaşlandırma
                </h5>
                <p class="card-text text-muted">Borçların vade durumuna göre yaşlandırma raporu.</p>
                <a href="{{ route('accounting.reports.payables-aging') }}" class="btn btn-primary">
                    <i class="bi bi-arrow-right me-1"></i>Raporu Görüntüle
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-people me-2"></i>Çalışan Borçları Yaşlandırma
                </h5>
                <p class="card-text text-muted">Çalışan borçlarının vade durumuna göre yaşlandırma raporu.</p>
                <a href="{{ route('accounting.reports.employee-dues-aging') }}" class="btn btn-primary">
                    <i class="bi bi-arrow-right me-1"></i>Raporu Görüntüle
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-graph-up me-2"></i>Nakit Akış Tahmini
                </h5>
                <p class="card-text text-muted">Gelecekteki nakit akış tahmini ve çek vade takibi.</p>
                <a href="{{ route('accounting.reports.cashflow-forecast') }}" class="btn btn-primary">
                    <i class="bi bi-arrow-right me-1"></i>Raporu Görüntüle
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-file-earmark-text me-2"></i>Aylık Kâr/Zarar
                </h5>
                <p class="card-text text-muted">Aylık kâr ve zarar raporu (Gelir - Gider).</p>
                <a href="{{ route('accounting.reports.monthly-pnl') }}" class="btn btn-primary">
                    <i class="bi bi-arrow-right me-1"></i>Raporu Görüntüle
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
