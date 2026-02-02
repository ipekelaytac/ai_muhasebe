@extends('layouts.admin')

@section('title', 'Cariler')
@section('page-title', 'Cariler')
@section('page-subtitle', 'Cari hesap yönetimi')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0">Tüm Cariler</h5>
        <small class="text-muted">Cari hesapları görüntüleyin ve yönetin</small>
    </div>
    <a href="{{ route('accounting.parties.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>
        Yeni Cari
    </a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('accounting.parties.index') }}" class="row g-3">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Ara (isim, kod, vergi no)..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="type" class="form-select">
                    <option value="">Tüm Tipler</option>
                    <option value="customer" {{ request('type') == 'customer' ? 'selected' : '' }}>Müşteri</option>
                    <option value="supplier" {{ request('type') == 'supplier' ? 'selected' : '' }}>Tedarikçi</option>
                    <option value="employee" {{ request('type') == 'employee' ? 'selected' : '' }}>Çalışan</option>
                    <option value="other" {{ request('type') == 'other' ? 'selected' : '' }}>Diğer</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">Tüm Durumlar</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Pasif</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i>Filtrele
                </button>
            </div>
            <div class="col-md-3">
                @if(request()->hasAny(['search', 'type', 'status']))
                    <a href="{{ route('accounting.parties.index') }}" class="btn btn-secondary w-100">
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
                        <th>İsim</th>
                        <th>Tip</th>
                        <th class="text-end">Alacak</th>
                        <th class="text-end">Borç</th>
                        <th class="text-end">Net Bakiye</th>
                        <th>Durum</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($parties as $party)
                        <tr>
                            <td><code>{{ $party->code }}</code></td>
                            <td>
                                <a href="{{ route('accounting.parties.show', $party) }}" class="text-decoration-none">
                                    {{ $party->name }}
                                </a>
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ $party->type_label }}</span>
                            </td>
                            <td class="text-end">
                                @if($party->receivable_balance > 0)
                                    <span class="text-success fw-bold">+{{ number_format($party->receivable_balance, 2) }} ₺</span>
                                @else
                                    <span class="text-muted">0,00 ₺</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($party->payable_balance > 0)
                                    <span class="text-danger fw-bold">-{{ number_format($party->payable_balance, 2) }} ₺</span>
                                @else
                                    <span class="text-muted">0,00 ₺</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @php
                                    $balance = $party->balance;
                                @endphp
                                @if($balance > 0)
                                    <span class="text-success fw-bold">+{{ number_format($balance, 2) }} ₺</span>
                                @elseif($balance < 0)
                                    <span class="text-danger fw-bold">{{ number_format($balance, 2) }} ₺</span>
                                @else
                                    <span class="text-muted">0,00 ₺</span>
                                @endif
                            </td>
                            <td>
                                @if($party->is_active)
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary">Pasif</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('accounting.parties.show', $party) }}" class="btn btn-outline-info" title="Detay">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('accounting.parties.edit', $party) }}" class="btn btn-outline-primary" title="Düzenle">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                                <p class="text-muted mb-0">Henüz cari hesap bulunmuyor</p>
                                <a href="{{ route('accounting.parties.create') }}" class="btn btn-primary btn-sm mt-3">
                                    <i class="bi bi-plus-circle me-1"></i>Yeni Cari Oluştur
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($parties->hasPages())
        <div class="card-footer bg-white">
            {{ $parties->links() }}
        </div>
    @endif
</div>
@endsection
