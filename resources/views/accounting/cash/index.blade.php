@extends('layouts.admin')

@section('title', 'Kasa & Bankalar')
@section('page-title', 'Kasa & Bankalar')
@section('page-subtitle', 'Kasa ve banka hesap bakiyeleri')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0">Kasa ve Banka Bakiyeleri</h5>
        <small class="text-muted">Güncel bakiye durumunu görüntüleyin</small>
    </div>
    <a href="{{ route('accounting.cash.transfer') }}" class="btn btn-info">
        <i class="bi bi-arrow-left-right me-1"></i>
        Virman Yap
    </a>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Kasalar</h6>
                <a href="{{ route('accounting.cash.cashbox.create') }}" class="btn btn-sm btn-success">
                    <i class="bi bi-plus-circle me-1"></i>Ekle
                </a>
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
                            @forelse($cashboxes as $cashbox)
                                <tr>
                                    <td>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>{{ $cashbox->name }}</strong>
                                                @if($cashbox->code)
                                                    <small class="text-muted">({{ $cashbox->code }})</small>
                                                @endif
                                                @if($cashbox->is_active)
                                                    <span class="badge bg-success ms-2">Aktif</span>
                                                @else
                                                    <span class="badge bg-secondary ms-2">Pasif</span>
                                                @endif
                                                @if($cashbox->is_default)
                                                    <span class="badge bg-primary ms-1">Varsayılan</span>
                                                @endif
                                            </div>
                                            <a href="{{ route('accounting.cash.cashbox.edit', $cashbox) }}" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <strong class="{{ $cashbox->balance >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ number_format($cashbox->balance, 2) }} {{ $cashbox->currency }}
                                        </strong>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox display-6 d-block mb-2"></i>
                                        Henüz kasa tanımlanmamış. 
                                        <a href="{{ route('accounting.cash.cashbox.create') }}" class="text-primary">İlk kasayı ekleyin</a>
                                    </td>
                                </tr>
                            @endforelse
                            @if(count($cashboxes) > 0 && isset($balances['total_cash']))
                                <tr class="table-info">
                                    <td><strong>Toplam</strong></td>
                                    <td class="text-end">
                                        <strong>{{ number_format($balances['total_cash'], 2) }} ₺</strong>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Banka Hesapları</h6>
                <a href="{{ route('accounting.cash.bank.create') }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>Ekle
                </a>
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
                            @forelse($bankAccounts as $bank)
                                <tr>
                                    <td>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>{{ $bank->name }}</strong>
                                                @if($bank->code)
                                                    <small class="text-muted">({{ $bank->code }})</small>
                                                @endif
                                                <br>
                                                <small class="text-muted">{{ $bank->bank_name }}</small>
                                                @if($bank->is_active)
                                                    <span class="badge bg-success ms-2">Aktif</span>
                                                @else
                                                    <span class="badge bg-secondary ms-2">Pasif</span>
                                                @endif
                                                @if($bank->is_default)
                                                    <span class="badge bg-primary ms-1">Varsayılan</span>
                                                @endif
                                            </div>
                                            <a href="{{ route('accounting.cash.bank.edit', $bank) }}" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <strong class="{{ $bank->balance >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ number_format($bank->balance, 2) }} {{ $bank->currency }}
                                        </strong>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center py-4 text-muted">
                                        <i class="bi bi-bank display-6 d-block mb-2"></i>
                                        Henüz banka hesabı tanımlanmamış. 
                                        <a href="{{ route('accounting.cash.bank.create') }}" class="text-primary">İlk banka hesabını ekleyin</a>
                                    </td>
                                </tr>
                            @endforelse
                            @if(count($bankAccounts) > 0 && isset($balances['total_bank']))
                                <tr class="table-info">
                                    <td><strong>Toplam</strong></td>
                                    <td class="text-end">
                                        <strong>{{ number_format($balances['total_bank'], 2) }} ₺</strong>
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

@if(isset($balances['total']))
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body text-center">
            <h4 class="mb-0">
                Toplam Nakit: 
                <span class="{{ $balances['total'] >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ number_format($balances['total'], 2) }} ₺
                </span>
            </h4>
        </div>
    </div>
@endif

@if($recentPayments->count() > 0)
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <h6 class="mb-0">Son Ödemeler</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tarih</th>
                            <th>Cari</th>
                            <th>Tip</th>
                            <th>Kasa/Banka</th>
                            <th class="text-end">Tutar</th>
                            <th class="text-end">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentPayments as $payment)
                            <tr>
                                <td>{{ $payment->payment_date->format('d.m.Y') }}</td>
                                <td>
                                    @if($payment->party)
                                        {{ $payment->party->name }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $payment->direction === 'in' ? 'bg-success' : 'bg-danger' }}">
                                        {{ $payment->type_label }}
                                    </span>
                                </td>
                                <td>
                                    @if($payment->cashbox)
                                        <span class="badge bg-info">{{ $payment->cashbox->name }}</span>
                                    @elseif($payment->bankAccount)
                                        <span class="badge bg-primary">{{ $payment->bankAccount->name }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format($payment->amount, 2) }} ₺</td>
                                <td class="text-end">
                                    <a href="{{ route('accounting.payments.show', $payment) }}" class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
@endsection
