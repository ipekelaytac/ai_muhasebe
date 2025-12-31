@extends('layouts.admin')

@section('title', 'Cari Detay')
@section('page-title', 'Cari Detay - ' . $customer->name)
@section('page-subtitle', $customer->company->name . ' - ' . $customer->branch->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Geri
    </a>
    <div>
        <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i>Düzenle
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-2">Bakiye</h6>
                <p class="card-text fs-4 fw-bold {{ $balance >= 0 ? 'text-success' : 'text-danger' }} mb-0">
                    {{ number_format($balance, 2) }} ₺
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-2">Toplam Gelir</h6>
                <p class="card-text fs-4 fw-bold text-success mb-0">
                    {{ number_format($customer->transactions()->where('type', 'income')->sum('amount'), 2) }} ₺
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-2">Toplam Gider</h6>
                <p class="card-text fs-4 fw-bold text-danger mb-0">
                    {{ number_format($customer->transactions()->where('type', 'expense')->sum('amount'), 2) }} ₺
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-2">Toplam Hareket</h6>
                <p class="card-text fs-4 fw-bold text-primary mb-0">
                    {{ $customer->transactions()->count() }}
                </p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-2">Toplam Çek</h6>
                <p class="card-text fs-4 fw-bold text-info mb-0">
                    {{ $customer->checks()->count() }}
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-2">Toplam Çek Tutarı</h6>
                <p class="card-text fs-4 fw-bold text-primary mb-0">
                    {{ number_format($customer->checks()->sum('amount'), 2) }} ₺
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-2">Bekleyen Çekler</h6>
                <p class="card-text fs-4 fw-bold text-warning mb-0">
                    {{ $customer->checks()->where('status', 'pending')->count() }}
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-2">Bozdurulmuş Çekler</h6>
                <p class="card-text fs-4 fw-bold text-success mb-0">
                    {{ $customer->checks()->where('status', 'cashed')->count() }}
                </p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light">
                <h6 class="mb-0">Cari Bilgileri</h6>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Kod:</dt>
                    <dd class="col-sm-8">{{ $customer->code ?? '-' }}</dd>

                    <dt class="col-sm-4">Tip:</dt>
                    <dd class="col-sm-8">
                        <span class="badge {{ $customer->type == 'customer' ? 'bg-info' : 'bg-warning' }}">
                            {{ $customer->type == 'customer' ? 'Müşteri' : 'Tedarikçi' }}
                        </span>
                    </dd>

                    <dt class="col-sm-4">Telefon:</dt>
                    <dd class="col-sm-8">{{ $customer->phone ?? '-' }}</dd>

                    <dt class="col-sm-4">E-posta:</dt>
                    <dd class="col-sm-8">{{ $customer->email ?? '-' }}</dd>

                    <dt class="col-sm-4">Adres:</dt>
                    <dd class="col-sm-8">{{ $customer->address ?? '-' }}</dd>

                    <dt class="col-sm-4">Vergi No:</dt>
                    <dd class="col-sm-8">{{ $customer->tax_number ?? '-' }}</dd>

                    <dt class="col-sm-4">Vergi Dairesi:</dt>
                    <dd class="col-sm-8">{{ $customer->tax_office ?? '-' }}</dd>

                    <dt class="col-sm-4">Durum:</dt>
                    <dd class="col-sm-8">
                        <span class="badge {{ $customer->status ? 'bg-success' : 'bg-secondary' }}">
                            {{ $customer->status ? 'Aktif' : 'Pasif' }}
                        </span>
                    </dd>

                    @if($customer->notes)
                    <dt class="col-sm-4">Notlar:</dt>
                    <dd class="col-sm-8">{{ $customer->notes }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Hareketler</h6>
        <a href="{{ route('admin.customers.transactions.create', $customer) }}" class="btn btn-sm btn-success">
            <i class="bi bi-plus-circle me-1"></i>Hareket Ekle
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tarih</th>
                        <th>Tip</th>
                        <th>Açıklama</th>
                        <th class="text-end">Tutar</th>
                        <th>Oluşturan</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customer->transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->transaction_date->format('d.m.Y') }}</td>
                            <td>
                                <span class="badge {{ $transaction->type == 'income' ? 'bg-success' : 'bg-danger' }}">
                                    {{ $transaction->type == 'income' ? 'Gelir' : 'Gider' }}
                                </span>
                            </td>
                            <td>{{ $transaction->description ?? '-' }}</td>
                            <td class="text-end fw-bold {{ $transaction->type == 'income' ? 'text-success' : 'text-danger' }}">
                                {{ $transaction->type == 'income' ? '+' : '-' }}{{ number_format($transaction->amount, 2) }} ₺
                            </td>
                            <td>{{ $transaction->creator->name ?? '-' }}</td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.customers.transactions.edit', [$customer, $transaction]) }}" class="btn btn-outline-primary" title="Düzenle">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.customers.transactions.destroy', [$customer, $transaction]) }}" method="POST" class="d-inline" onsubmit="return confirm('Bu hareketi silmek istediğinize emin misiniz?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Sil">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                                <p class="text-muted mb-3">Henüz hareket bulunmuyor</p>
                                <a href="{{ route('admin.customers.transactions.create', $customer) }}" class="btn btn-primary btn-sm">
                                    İlk hareketi ekleyin
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($customer->checks->count() > 0)
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Çekler</h6>
        <a href="{{ route('admin.checks.create') }}?customer_id={{ $customer->id }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-circle me-1"></i>Yeni Çek Ekle
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Çek No</th>
                        <th>Banka</th>
                        <th>Tutar</th>
                        <th>Geldiği Tarih</th>
                        <th>Vade Tarihi</th>
                        <th>Bozdurulma Tarihi</th>
                        <th>Durum</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($customer->checks as $check)
                        <tr>
                            <td>
                                <span class="fw-medium">{{ $check->check_number }}</span>
                            </td>
                            <td>{{ $check->bank_name }}</td>
                            <td class="fw-bold text-primary">{{ number_format($check->amount, 2) }} ₺</td>
                            <td>{{ $check->received_date->format('d.m.Y') }}</td>
                            <td>
                                <span class="{{ $check->due_date->isPast() && $check->status == 'pending' ? 'text-danger fw-bold' : '' }}">
                                    {{ $check->due_date->format('d.m.Y') }}
                                </span>
                                @if($check->due_date->isPast() && $check->status == 'pending')
                                    <span class="badge bg-danger ms-1">Vadesi Geçti</span>
                                @endif
                            </td>
                            <td>
                                {{ $check->cashed_date ? $check->cashed_date->format('d.m.Y') : '-' }}
                            </td>
                            <td>
                                @if($check->status == 'pending')
                                    <span class="badge bg-warning">Bekliyor</span>
                                @elseif($check->status == 'cashed')
                                    <span class="badge bg-success">Bozduruldu</span>
                                @else
                                    <span class="badge bg-secondary">İptal</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.checks.show', $check) }}" class="btn btn-outline-info" title="Detay">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.checks.edit', $check) }}" class="btn btn-outline-primary" title="Düzenle">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </div>
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

