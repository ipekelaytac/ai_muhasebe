@extends('layouts.admin')

@section('title', 'Cariler')
@section('page-title', 'Cariler')
@section('page-subtitle', 'Cari yönetimi')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0">Tüm Cariler</h5>
        <small class="text-muted">Carileri görüntüleyin ve yönetin</small>
    </div>
    <a href="{{ route('admin.customers.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>
        Yeni Cari
    </a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.customers.index') }}" class="row g-3">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Ara..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="type" class="form-select">
                    <option value="">Tüm Tipler</option>
                    <option value="customer" {{ request('type') == 'customer' ? 'selected' : '' }}>Müşteri</option>
                    <option value="supplier" {{ request('type') == 'supplier' ? 'selected' : '' }}>Tedarikçi</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">Tüm Durumlar</option>
                    <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Aktif</option>
                    <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Pasif</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i>Filtrele
                </button>
            </div>
            <div class="col-md-3">
                @if(request()->hasAny(['search', 'type', 'status']))
                    <a href="{{ route('admin.customers.index') }}" class="btn btn-secondary w-100">
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
                        <th>Kod</th>
                        <th>Cari Adı</th>
                        <th>Tip</th>
                        <th>Şirket / Şube</th>
                        <th>İletişim</th>
                        <th>Bakiye</th>
                        <th>Durum</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                        <tr>
                            <td>
                                <span class="badge bg-secondary">{{ $customer->code ?? '-' }}</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                        <span class="text-primary fw-bold">{{ substr($customer->name, 0, 1) }}</span>
                                    </div>
                                    <span class="fw-medium">{{ $customer->name }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="badge {{ $customer->type == 'customer' ? 'bg-info' : 'bg-warning' }}">
                                    {{ $customer->type == 'customer' ? 'Müşteri' : 'Tedarikçi' }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-medium">{{ $customer->company->name }}</div>
                                <small class="text-muted">{{ $customer->branch->name }}</small>
                            </td>
                            <td>
                                @if($customer->phone)
                                    <div><i class="bi bi-telephone me-1 text-muted"></i>{{ $customer->phone }}</div>
                                @endif
                                @if($customer->email)
                                    <div><i class="bi bi-envelope me-1 text-muted"></i>{{ $customer->email }}</div>
                                @endif
                            </td>
                            <td>
                                @php
                                    $balance = $customer->balance;
                                @endphp
                                <span class="fw-bold {{ $balance >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($balance, 2) }} ₺
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $customer->status ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $customer->status ? 'Aktif' : 'Pasif' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-outline-info" title="Detay">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-outline-primary" title="Düzenle">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.customers.destroy', $customer) }}" method="POST" class="d-inline" onsubmit="return confirm('Bu cariyi silmek istediğinize emin misiniz?');">
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
                            <td colspan="8" class="text-center py-5">
                                <i class="bi bi-people fs-1 text-muted d-block mb-2"></i>
                                <p class="text-muted mb-3">Henüz cari bulunmuyor</p>
                                <a href="{{ route('admin.customers.create') }}" class="btn btn-primary btn-sm">
                                    İlk cariyi ekleyin
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($customers->hasPages())
        <div class="card-footer bg-white">
            {{ $customers->links() }}
        </div>
    @endif
</div>
@endsection

