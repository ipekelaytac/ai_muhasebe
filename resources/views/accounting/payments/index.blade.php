@extends('layouts.admin')

@section('title', 'Ödemeler / Tahsilatlar')
@section('page-title', 'Ödemeler / Tahsilatlar')
@section('page-subtitle', 'Ödeme ve tahsilat yönetimi')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0">Tüm Ödemeler</h5>
        <small class="text-muted">Ödeme ve tahsilatları görüntüleyin ve yönetin</small>
    </div>
    <a href="{{ route('accounting.payments.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>
        Yeni Ödeme/Tahsilat
    </a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('accounting.payments.index') }}" class="row g-3">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Ödeme no, açıklama..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="type" class="form-select">
                    <option value="">Tüm Tipler</option>
                    @foreach(\App\Domain\Accounting\Enums\PaymentType::ALL as $type)
                        <option value="{{ $type }}" {{ request('type') == $type ? 'selected' : '' }}>
                            {{ \App\Domain\Accounting\Enums\PaymentType::getLabel($type) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="direction" class="form-select">
                    <option value="">Tüm Yönler</option>
                    <option value="in" {{ request('direction') == 'in' ? 'selected' : '' }}>Giriş</option>
                    <option value="out" {{ request('direction') == 'out' ? 'selected' : '' }}>Çıkış</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">Tüm Durumlar</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Taslak</option>
                    <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Onaylı</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>İptal</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="party_id" class="form-select">
                    <option value="">Tüm Cariler</option>
                    @foreach($parties as $party)
                        <option value="{{ $party->id }}" {{ request('party_id') == $party->id ? 'selected' : '' }}>
                            {{ $party->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="cashbox_id" class="form-select">
                    <option value="">Tüm Kasalar</option>
                    @foreach($cashboxes as $cashbox)
                        <option value="{{ $cashbox->id }}" {{ request('cashbox_id') == $cashbox->id ? 'selected' : '' }}>
                            {{ $cashbox->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="bank_account_id" class="form-select">
                    <option value="">Tüm Bankalar</option>
                    @foreach($bankAccounts as $bank)
                        <option value="{{ $bank->id }}" {{ request('bank_account_id') == $bank->id ? 'selected' : '' }}>
                            {{ $bank->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}" placeholder="Başlangıç">
            </div>
            <div class="col-md-3">
                <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}" placeholder="Bitiş">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i>Filtrele
                </button>
            </div>
            <div class="col-md-2">
                @if(request()->hasAny(['search', 'type', 'direction', 'status', 'party_id', 'cashbox_id', 'bank_account_id', 'start_date', 'end_date']))
                    <a href="{{ route('accounting.payments.index') }}" class="btn btn-secondary w-100">
                        <i class="bi bi-x-circle me-1"></i>Temizle
                    </a>
                @endif
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
                        <th>Cari</th>
                        <th>Tip</th>
                        <th>Ödeme No</th>
                        <th>Kasa/Banka</th>
                        <th class="text-end">Tutar</th>
                        <th class="text-end">Dağıtılan</th>
                        <th class="text-end">Kalan</th>
                        <th>Durum</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>{{ $payment->payment_date->format('d.m.Y') }}</td>
                            <td>
                                @if($payment->party)
                                    <a href="{{ route('accounting.parties.show', $payment->party_id) }}" class="text-decoration-none">
                                        {{ $payment->party->name }}
                                    </a>
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
                                <a href="{{ route('accounting.payments.show', $payment) }}" class="text-decoration-none">
                                    {{ $payment->payment_number }}
                                </a>
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
                                @if($payment->allocated_amount > 0)
                                    <span class="text-success">{{ number_format($payment->allocated_amount, 2) }} ₺</span>
                                @else
                                    <span class="text-muted">0,00 ₺</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($payment->unallocated_amount > 0)
                                    <strong class="text-warning">{{ number_format($payment->unallocated_amount, 2) }} ₺</strong>
                                @else
                                    <span class="text-muted">0,00 ₺</span>
                                @endif
                            </td>
                            <td>
                                @if($payment->status === 'draft')
                                    <span class="badge bg-secondary">Taslak</span>
                                @elseif($payment->status === 'confirmed')
                                    <span class="badge bg-success">Onaylı</span>
                                @elseif($payment->status === 'cancelled')
                                    <span class="badge bg-danger">İptal</span>
                                @elseif($payment->status === 'reversed')
                                    <span class="badge bg-dark">Ters Kayıt</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('accounting.payments.show', $payment) }}" class="btn btn-outline-info" title="Detay">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if($payment->canModify())
                                        <a href="{{ route('accounting.payments.edit', $payment) }}" class="btn btn-outline-primary" title="Düzenle">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                                <p class="text-muted mb-0">Henüz ödeme bulunmuyor</p>
                                <a href="{{ route('accounting.payments.create') }}" class="btn btn-primary btn-sm mt-3">
                                    <i class="bi bi-plus-circle me-1"></i>Yeni Ödeme Oluştur
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($payments->hasPages())
        <div class="card-footer bg-white">
            {{ $payments->links() }}
        </div>
    @endif
</div>
@endsection
