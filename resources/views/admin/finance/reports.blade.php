@extends('layouts.admin')

@section('title', 'Finans Raporları')
@section('page-title', 'Finans Raporları')
@section('page-subtitle', 'Gelir ve gider analizi')

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.finance.reports') }}" class="row g-3">
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
                            {{ ['', 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'][$m] }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i>
                    Filtrele
                </button>
            </div>
        </form>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="card-subtitle text-muted mb-0">Toplam Gelir</h6>
                    <i class="bi bi-arrow-up-circle text-success fs-4"></i>
                </div>
                <h3 class="text-success mb-0">{{ number_format($income, 2) }} ₺</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="card-subtitle text-muted mb-0">Toplam Gider</h6>
                    <i class="bi bi-arrow-down-circle text-danger fs-4"></i>
                </div>
                <h3 class="text-danger mb-0">{{ number_format($expense, 2) }} ₺</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="card-subtitle text-muted mb-0">Net</h6>
                    <i class="bi bi-cash-stack {{ $net >= 0 ? 'text-success' : 'text-danger' }} fs-4"></i>
                </div>
                <h3 class="{{ $net >= 0 ? 'text-success' : 'text-danger' }} mb-0">
                    {{ number_format($net, 2) }} ₺
                </h3>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-list-ul me-2"></i>
            İşlemler ({{ ['', 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'][$month] }} {{ $year }})
        </h5>
        <span class="badge bg-primary">{{ $transactions->count() }} işlem</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tarih</th>
                        <th>Tip</th>
                        <th>Kategori</th>
                        <th>Açıklama</th>
                        <th class="text-end">Tutar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->transaction_date->format('d.m.Y') }}</td>
                            <td>
                                <span class="badge {{ $transaction->type === 'income' ? 'bg-success' : 'bg-danger' }}">
                                    {{ $transaction->type === 'income' ? 'Gelir' : 'Gider' }}
                                </span>
                            </td>
                            <td>{{ $transaction->category->name }}</td>
                            <td>
                                <div>{{ $transaction->description ?? '-' }}</div>
                                @if($transaction->attachments && $transaction->attachments->count() > 0)
                                    <small class="text-muted">
                                        <i class="bi bi-paperclip"></i> {{ $transaction->attachments->count() }} ek
                                    </small>
                                @endif
                            </td>
                            <td class="text-end fw-bold {{ $transaction->type === 'income' ? 'text-success' : 'text-danger' }}">
                                {{ number_format($transaction->amount, 2) }} ₺
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <i class="bi bi-inbox display-4 text-muted d-block mb-3"></i>
                                <p class="text-muted mb-0">Bu dönem için işlem bulunmuyor</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

