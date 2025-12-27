@extends('layouts.admin')

@section('title', 'Yemek Yardımı Takibi')
@section('page-title', 'Yemek Yardımı Takibi')
@section('page-subtitle', 'Yemek yardımı ödemeleri ve raporları')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0">Yemek Yardımı Takibi</h5>
        <small class="text-muted">Aylık yemek yardımı ödemelerini görüntüleyin</small>
    </div>
    <div class="btn-group">
        <a href="{{ route('admin.meal-allowance.report') }}" class="btn btn-outline-primary">
            <i class="bi bi-graph-up me-1"></i>
            Detaylı Rapor
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-2">Toplam Yemek Yardımı</h6>
                <p class="card-text fs-3 fw-bold text-info mb-0">{{ number_format($summary['total_meal_allowance'], 2) }} ₺</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-2">Personel Sayısı</h6>
                <p class="card-text fs-3 fw-bold mb-0">{{ $summary['total_employees'] }} kişi</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-2">Ortalama</h6>
                <p class="card-text fs-3 fw-bold mb-0">
                    {{ $summary['total_employees'] > 0 ? number_format($summary['total_meal_allowance'] / $summary['total_employees'], 2) : 0 }} ₺
                </p>
            </div>
        </div>
    </div>
</div>

<form method="GET" action="{{ route('admin.meal-allowance.index') }}" class="mb-4">
    <div class="row g-3">
        <div class="col-md-4">
            <label for="year" class="form-label">Yıl</label>
            <select name="year" id="year" class="form-select">
                @for($y = now()->year; $y >= now()->year - 5; $y--)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </div>
        <div class="col-md-4">
            <label for="month" class="form-label">Ay</label>
            <select name="month" id="month" class="form-select">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::create(null, $m, 1)->locale('tr')->monthName }}
                    </option>
                @endfor
            </select>
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-search me-1"></i>Filtrele
            </button>
        </div>
    </div>
</form>

@if($items->isEmpty())
    <div class="alert alert-info text-center">
        <i class="bi bi-info-circle fs-1 d-block mb-3"></i>
        <p class="mb-0">Seçilen dönem için yemek yardımı kaydı bulunmuyor.</p>
    </div>
@else
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0">{{ \Carbon\Carbon::create($year, $month, 1)->locale('tr')->monthName }} {{ $year }} - Yemek Yardımı Detayları</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Çalışan</th>
                            <th>Şirket</th>
                            <th>Şube</th>
                            <th class="text-end">Yemek Yardımı</th>
                            <th class="text-end">Net Maaş</th>
                            <th class="text-end">Toplam</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            <tr>
                                <td class="fw-medium">{{ $item->employee->full_name }}</td>
                                <td>{{ $item->payrollPeriod->company->name }}</td>
                                <td>{{ $item->payrollPeriod->branch->name }}</td>
                                <td class="text-end text-info fw-bold">{{ number_format($item->meal_allowance, 2) }} ₺</td>
                                <td class="text-end">{{ number_format($item->base_net_salary, 2) }} ₺</td>
                                <td class="text-end fw-bold">{{ number_format($item->net_payable, 2) }} ₺</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="3" class="fw-bold">TOPLAM</th>
                            <th class="text-end fw-bold text-info">{{ number_format($items->sum('meal_allowance'), 2) }} ₺</th>
                            <th class="text-end fw-bold">{{ number_format($items->sum('base_net_salary'), 2) }} ₺</th>
                            <th class="text-end fw-bold">{{ number_format($items->sum('net_payable'), 2) }} ₺</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endif
@endsection

