@extends('layouts.admin')

@section('title', 'Borç Yaşlandırma')
@section('page-title', 'Borç Yaşlandırma')
@section('page-subtitle', 'Vade durumuna göre borçlar')

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('accounting.reports.payables-aging') }}" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Tarih</label>
                <input type="date" name="as_of_date" class="form-control" value="{{ $asOfDate }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Cari Tipi</label>
                <select name="party_type" class="form-select">
                    <option value="">Tümü</option>
                    <option value="supplier" {{ $partyType == 'supplier' ? 'selected' : '' }}>Tedarikçi</option>
                    <option value="customer" {{ $partyType == 'customer' ? 'selected' : '' }}>Müşteri</option>
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
                        <th>Cari</th>
                        <th class="text-end">0-30 Gün</th>
                        <th class="text-end">31-60 Gün</th>
                        <th class="text-end">61-90 Gün</th>
                        <th class="text-end">90+ Gün</th>
                        <th class="text-end">Toplam</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['parties'] ?? [] as $party)
                        <tr>
                            <td>
                                <a href="{{ route('accounting.parties.show', $party['id']) }}" class="text-decoration-none">
                                    {{ $party['name'] }}
                                </a>
                            </td>
                            <td class="text-end">{{ number_format($party['age_0_30'] ?? 0, 2) }} ₺</td>
                            <td class="text-end">{{ number_format($party['age_31_60'] ?? 0, 2) }} ₺</td>
                            <td class="text-end">{{ number_format($party['age_61_90'] ?? 0, 2) }} ₺</td>
                            <td class="text-end">{{ number_format($party['age_90_plus'] ?? 0, 2) }} ₺</td>
                            <td class="text-end">
                                <strong>{{ number_format($party['total'] ?? 0, 2) }} ₺</strong>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                                <p class="text-muted mb-0">Borç bulunmuyor</p>
                            </td>
                        </tr>
                    @endforelse
                    @if(isset($report['totals']))
                        <tr class="table-info">
                            <td><strong>Toplam</strong></td>
                            <td class="text-end"><strong>{{ number_format($report['totals']['age_0_30'] ?? 0, 2) }} ₺</strong></td>
                            <td class="text-end"><strong>{{ number_format($report['totals']['age_31_60'] ?? 0, 2) }} ₺</strong></td>
                            <td class="text-end"><strong>{{ number_format($report['totals']['age_61_90'] ?? 0, 2) }} ₺</strong></td>
                            <td class="text-end"><strong>{{ number_format($report['totals']['age_90_plus'] ?? 0, 2) }} ₺</strong></td>
                            <td class="text-end"><strong>{{ number_format($report['totals']['total'] ?? 0, 2) }} ₺</strong></td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
