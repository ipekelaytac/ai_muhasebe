@extends('layouts.admin')

@section('title', 'Aylık Kâr/Zarar')
@section('page-title', 'Aylık Kâr/Zarar')
@section('page-subtitle', 'Gelir ve gider raporu')

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('accounting.reports.monthly-pnl') }}" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Yıl</label>
                <input type="number" name="year" class="form-control" value="{{ $year }}" min="2020" max="2100">
            </div>
            <div class="col-md-3">
                <label class="form-label">Ay</label>
                <select name="month" class="form-select">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create(null, $m, 1)->locale('tr')->monthName }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i>Filtrele
                </button>
            </div>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0">Gelirler</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <tbody>
                            @forelse($pnl['income']['categories'] ?? [] as $item)
                                <tr>
                                    <td>{{ $item['category_name'] ?? 'Kategorisiz' }}</td>
                                    <td class="text-end">
                                        <strong class="text-success">{{ number_format($item['amount'], 2) }} ₺</strong>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center py-3 text-muted">Gelir bulunmuyor</td>
                                </tr>
                            @endforelse
                            @if(isset($pnl['income']['total']))
                                <tr class="table-success">
                                    <td><strong>Toplam Gelir</strong></td>
                                    <td class="text-end">
                                        <strong>{{ number_format($pnl['income']['total'], 2) }} ₺</strong>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0">Giderler</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <tbody>
                            @forelse($pnl['expenses']['categories'] ?? [] as $item)
                                <tr>
                                    <td>{{ $item['category_name'] ?? 'Kategorisiz' }}</td>
                                    <td class="text-end">
                                        <strong class="text-danger">{{ number_format($item['amount'], 2) }} ₺</strong>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center py-3 text-muted">Gider bulunmuyor</td>
                                </tr>
                            @endforelse
                            @if(isset($pnl['expenses']['total']))
                                <tr class="table-danger">
                                    <td><strong>Toplam Gider</strong></td>
                                    <td class="text-end">
                                        <strong>{{ number_format($pnl['expenses']['total'], 2) }} ₺</strong>
                                    </td>
                                </tr>
                            @endif
                            @if(isset($pnl['payroll']) && $pnl['payroll']['total'] > 0)
                                <tr class="table-warning">
                                    <td><strong>Maaş Ödemeleri</strong></td>
                                    <td class="text-end">
                                        <strong>{{ number_format($pnl['payroll']['total'], 2) }} ₺</strong>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@if(isset($pnl['payroll']) && $pnl['payroll']['total'] > 0)
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0">Maaş Ödemeleri Detayı</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <tbody>
                            @if($pnl['payroll']['salary'] > 0)
                            <tr>
                                <td>Maaş</td>
                                <td class="text-end">
                                    <strong class="text-warning">{{ number_format($pnl['payroll']['salary'], 2) }} ₺</strong>
                                </td>
                            </tr>
                            @endif
                            @if($pnl['payroll']['overtime'] > 0)
                            <tr>
                                <td>Mesai</td>
                                <td class="text-end">
                                    <strong class="text-warning">{{ number_format($pnl['payroll']['overtime'], 2) }} ₺</strong>
                                </td>
                            </tr>
                            @endif
                            @if($pnl['payroll']['meal'] > 0)
                            <tr>
                                <td>Yemek Yardımı</td>
                                <td class="text-end">
                                    <strong class="text-warning">{{ number_format($pnl['payroll']['meal'], 2) }} ₺</strong>
                                </td>
                            </tr>
                            @endif
                            <tr class="table-warning">
                                <td><strong>Toplam Maaş Ödemeleri</strong></td>
                                <td class="text-end">
                                    <strong>{{ number_format($pnl['payroll']['total'], 2) }} ₺</strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@if(isset($pnl['summary']['net_income']))
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center">
            <h4 class="mb-0">
                Net Kâr/Zarar: 
                <span class="{{ $pnl['summary']['net_income'] >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ number_format($pnl['summary']['net_income'], 2) }} ₺
                </span>
            </h4>
            <small class="text-muted">{{ \Carbon\Carbon::create($year, $month, 1)->locale('tr')->monthName }} {{ $year }}</small>
        </div>
    </div>
@endif
@endsection
