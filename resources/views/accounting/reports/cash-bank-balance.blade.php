@extends('layouts.admin')

@section('title', 'Kasa & Banka Bakiyeleri')
@section('page-title', 'Kasa & Banka Bakiyeleri')
@section('page-subtitle', 'Güncel bakiye durumu')

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('accounting.reports.cash-bank-balance') }}" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Tarih</label>
                <input type="date" name="as_of_date" class="form-control" value="{{ $asOfDate }}">
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
    @if(isset($balances['cashboxes']) && count($balances['cashboxes']) > 0)
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0">Kasalar</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Kasa</th>
                                    <th class="text-end">Bakiye</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($balances['cashboxes'] as $cashbox)
                                    <tr>
                                        <td>{{ $cashbox['name'] }}</td>
                                        <td class="text-end">
                                            <strong class="{{ $cashbox['balance'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($cashbox['balance'], 2) }} ₺
                                            </strong>
                                        </td>
                                    </tr>
                                @endforeach
                                <tr class="table-info">
                                    <td><strong>Toplam</strong></td>
                                    <td class="text-end">
                                        <strong>{{ number_format($balances['total_cash'] ?? 0, 2) }} ₺</strong>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
    
    @if(isset($balances['bank_accounts']) && count($balances['bank_accounts']) > 0)
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0">Banka Hesapları</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Banka</th>
                                    <th class="text-end">Bakiye</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($balances['bank_accounts'] as $bank)
                                    <tr>
                                        <td>{{ $bank['name'] }}</td>
                                        <td class="text-end">
                                            <strong class="{{ $bank['balance'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($bank['balance'], 2) }} ₺
                                            </strong>
                                        </td>
                                    </tr>
                                @endforeach
                                <tr class="table-info">
                                    <td><strong>Toplam</strong></td>
                                    <td class="text-end">
                                        <strong>{{ number_format($balances['total_bank'] ?? 0, 2) }} ₺</strong>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@if(isset($balances['total']))
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center">
            <h4 class="mb-0">
                Toplam Nakit: 
                <span class="{{ $balances['total'] >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ number_format($balances['total'], 2) }} ₺
                </span>
            </h4>
            <small class="text-muted">Tarih: {{ \Carbon\Carbon::parse($asOfDate)->format('d.m.Y') }}</small>
        </div>
    </div>
@endif
@endsection
