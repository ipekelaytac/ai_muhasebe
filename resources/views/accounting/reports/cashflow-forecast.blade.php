@extends('layouts.admin')

@section('title', 'Nakit Akış Tahmini')
@section('page-title', 'Nakit Akış Tahmini')
@section('page-subtitle', 'Gelecekteki nakit akış tahmini')

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('accounting.reports.cashflow-forecast') }}" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Gün Sayısı</label>
                <select name="days" class="form-select">
                    <option value="30" {{ $days == 30 ? 'selected' : '' }}>30 Gün</option>
                    <option value="60" {{ $days == 60 ? 'selected' : '' }}>60 Gün</option>
                    <option value="90" {{ $days == 90 ? 'selected' : '' }}>90 Gün</option>
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

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tarih</th>
                        <th class="text-end">Giriş</th>
                        <th class="text-end">Çıkış</th>
                        <th class="text-end">Net</th>
                        <th class="text-end">Kümülatif</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($forecast['days'] ?? [] as $day)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($day['date'])->format('d.m.Y') }}</td>
                            <td class="text-end text-success">{{ number_format($day['in'] ?? 0, 2) }} ₺</td>
                            <td class="text-end text-danger">{{ number_format($day['out'] ?? 0, 2) }} ₺</td>
                            <td class="text-end">
                                <strong class="{{ ($day['net'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($day['net'] ?? 0, 2) }} ₺
                                </strong>
                            </td>
                            <td class="text-end">
                                <strong class="{{ ($day['cumulative'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($day['cumulative'] ?? 0, 2) }} ₺
                                </strong>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                                <p class="text-muted mb-0">Tahmin verisi bulunmuyor</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
