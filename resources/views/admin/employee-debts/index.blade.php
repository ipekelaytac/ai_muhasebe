@extends('layouts.admin')

@section('title', 'Çalışan Borçları')
@section('page-title', 'Çalışan Borçları')
@section('page-subtitle', 'Çalışan borç yönetimi')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0">Tüm Borçlar</h5>
        <small class="text-muted">Çalışan borçlarını görüntüleyin ve yönetin</small>
    </div>
    <a href="{{ route('admin.employee-debts.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>
        Yeni Borç
    </a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.employee-debts.index') }}" class="row g-3">
            <div class="col-md-3">
                <select name="employee_id" class="form-select">
                    <option value="">Tüm Personeller</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                            {{ $employee->full_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">Tüm Durumlar</option>
                    <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Açık</option>
                    <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Kapalı</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control" placeholder="Başlangıç" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control" placeholder="Bitiş" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i>Filtrele
                </button>
            </div>
            <div class="col-md-1">
                @if(request()->hasAny(['employee_id', 'status', 'date_from', 'date_to']))
                    <a href="{{ route('admin.employee-debts.index') }}" class="btn btn-secondary w-100">
                        <i class="bi bi-x-circle"></i>
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
                        <th>Personel</th>
                        <th>Borç Tarihi</th>
                        <th>Borç Tutarı</th>
                        <th>Ödenen</th>
                        <th>Kalan</th>
                        <th>Durum</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($debts as $debt)
                        <tr>
                            <td>
                                <div class="fw-medium">{{ $debt->employee->full_name }}</div>
                                <small class="text-muted">{{ $debt->company->name }} / {{ $debt->branch->name }}</small>
                            </td>
                            <td>{{ $debt->debt_date->format('d.m.Y') }}</td>
                            <td class="fw-bold text-danger">{{ number_format($debt->amount, 2) }} ₺</td>
                            <td class="text-success">{{ number_format($debt->paid_amount, 2) }} ₺</td>
                            <td class="fw-bold {{ $debt->remaining_amount > 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($debt->remaining_amount, 2) }} ₺
                            </td>
                            <td>
                                <span class="badge {{ $debt->status ? 'bg-warning' : 'bg-success' }}">
                                    {{ $debt->status ? 'Açık' : 'Kapalı' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.employee-debts.show', $debt) }}" class="btn btn-outline-info" title="Detay">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.employee-debts.edit', $debt) }}" class="btn btn-outline-primary" title="Düzenle">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.employee-debts.destroy', $debt) }}" method="POST" class="d-inline" onsubmit="return confirm('Bu borcu silmek istediğinize emin misiniz?');">
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
                            <td colspan="7" class="text-center py-5">
                                <i class="bi bi-credit-card fs-1 text-muted d-block mb-2"></i>
                                <p class="text-muted mb-3">Henüz borç bulunmuyor</p>
                                <a href="{{ route('admin.employee-debts.create') }}" class="btn btn-primary btn-sm">
                                    İlk borcu ekleyin
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($debts->hasPages())
        <div class="card-footer bg-white">
            {{ $debts->links() }}
        </div>
    @endif
</div>
@endsection

