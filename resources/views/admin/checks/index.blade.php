@extends('layouts.admin')

@section('title', 'Çekler')
@section('page-title', 'Çekler')
@section('page-subtitle', 'Çek yönetimi')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0">Tüm Çekler</h5>
        <small class="text-muted">Çekleri görüntüleyin ve yönetin</small>
    </div>
    <a href="{{ route('admin.checks.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>
        Yeni Çek
    </a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.checks.index') }}" class="row g-3">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Çek No, Banka, Cari Ara..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">Tüm Durumlar</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Bekliyor</option>
                    <option value="cashed" {{ request('status') == 'cashed' ? 'selected' : '' }}>Bozduruldu</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>İptal</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="customer_id" class="form-select">
                    <option value="">Tüm Cariler</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                            {{ $customer->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control" placeholder="Başlangıç" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control" placeholder="Bitiş" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </form>
        @if(request()->hasAny(['search', 'status', 'customer_id', 'date_from', 'date_to']))
            <div class="mt-2">
                <a href="{{ route('admin.checks.index') }}" class="btn btn-sm btn-secondary">
                    <i class="bi bi-x-circle me-1"></i>Temizle
                </a>
            </div>
        @endif
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Çek No</th>
                        <th>Banka</th>
                        <th>Cari</th>
                        <th>Tutar</th>
                        <th>Geldiği Tarih</th>
                        <th>Vade Tarihi</th>
                        <th>Bozdurulma Tarihi</th>
                        <th>Durum</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($checks as $check)
                        <tr>
                            <td>
                                <span class="fw-medium">{{ $check->check_number }}</span>
                            </td>
                            <td>{{ $check->bank_name }}</td>
                            <td>
                                <div class="fw-medium">{{ $check->customer->name }}</div>
                                <small class="text-muted">{{ $check->company->name }} / {{ $check->branch->name }}</small>
                            </td>
                            <td class="fw-bold text-primary">{{ number_format($check->amount, 2) }} ₺</td>
                            <td>{{ $check->received_date->format('d.m.Y') }}</td>
                            <td>
                                <span class="{{ $check->due_date->isPast() && $check->status == 'pending' ? 'text-danger' : '' }}">
                                    {{ $check->due_date->format('d.m.Y') }}
                                </span>
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
                                    <form action="{{ route('admin.checks.destroy', $check) }}" method="POST" class="d-inline" onsubmit="return confirm('Bu çeki silmek istediğinize emin misiniz?');">
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
                            <td colspan="9" class="text-center py-5">
                                <i class="bi bi-receipt fs-1 text-muted d-block mb-2"></i>
                                <p class="text-muted mb-3">Henüz çek bulunmuyor</p>
                                <a href="{{ route('admin.checks.create') }}" class="btn btn-primary btn-sm">
                                    İlk çeki ekleyin
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($checks->hasPages())
        <div class="card-footer bg-white">
            {{ $checks->links() }}
        </div>
    @endif
</div>
@endsection

