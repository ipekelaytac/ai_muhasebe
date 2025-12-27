@extends('layouts.admin')

@section('title', 'Yemek Yardımı Raporu')
@section('page-title', 'Yemek Yardımı Raporu')
@section('page-subtitle', 'Detaylı yemek yardımı raporları')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0">Yemek Yardımı Detaylı Raporu</h5>
        <small class="text-muted">Dönemsel yemek yardımı analizi</small>
    </div>
    <a href="{{ route('admin.meal-allowance.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Geri
    </a>
</div>

<form method="GET" action="{{ route('admin.meal-allowance.report') }}" class="mb-4">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="year" class="form-label">Yıl</label>
                    <select name="year" id="year" class="form-select">
                        @for($y = now()->year; $y >= now()->year - 5; $y--)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="start_month" class="form-label">Başlangıç Ayı</label>
                    <select name="start_month" id="start_month" class="form-select">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $startMonth == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create(null, $m, 1)->locale('tr')->monthName }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="end_month" class="form-label">Bitiş Ayı</label>
                    <select name="end_month" id="end_month" class="form-select">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $endMonth == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create(null, $m, 1)->locale('tr')->monthName }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i>Raporla
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-2">Toplam Yemek Yardımı</h6>
                <p class="card-text fs-3 fw-bold text-info mb-0">{{ number_format($total, 2) }} ₺</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-2">Personel Sayısı</h6>
                <p class="card-text fs-3 fw-bold mb-0">{{ $totalEmployees }} kişi</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-2">Ortalama</h6>
                <p class="card-text fs-3 fw-bold mb-0">
                    {{ $totalEmployees > 0 ? number_format($total / $totalEmployees, 2) : 0 }} ₺
                </p>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0">Aylık Özet</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Ay</th>
                        <th class="text-end">Personel Sayısı</th>
                        <th class="text-end">Toplam Yemek Yardımı</th>
                        <th class="text-end">Ortalama</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($monthlySummary as $monthData)
                        <tr>
                            <td class="fw-medium">{{ $monthData['month_name'] }}</td>
                            <td class="text-end">{{ $monthData['count'] }} kişi</td>
                            <td class="text-end text-info fw-bold">{{ number_format($monthData['total'], 2) }} ₺</td>
                            <td class="text-end">
                                {{ $monthData['count'] > 0 ? number_format($monthData['total'] / $monthData['count'], 2) : 0 }} ₺
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th class="fw-bold">TOPLAM</th>
                        <th class="text-end fw-bold">{{ array_sum(array_column($monthlySummary, 'count')) }} kişi</th>
                        <th class="text-end fw-bold text-info">{{ number_format($total, 2) }} ₺</th>
                        <th class="text-end fw-bold">
                            {{ $totalEmployees > 0 ? number_format($total / $totalEmployees, 2) : 0 }} ₺
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

@if($items->isNotEmpty())
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0">Detaylı Liste</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Çalışan</th>
                            <th>Dönem</th>
                            <th>Şirket</th>
                            <th>Şube</th>
                            <th class="text-end">Yemek Yardımı</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            <tr>
                                <td class="fw-medium">{{ $item->employee->full_name }}</td>
                                <td>{{ $item->payrollPeriod->period_name }}</td>
                                <td>{{ $item->payrollPeriod->company->name }}</td>
                                <td>{{ $item->payrollPeriod->branch->name }}</td>
                                <td class="text-end text-info fw-bold">{{ number_format($item->meal_allowance, 2) }} ₺</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
@endsection

