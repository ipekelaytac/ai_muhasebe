@extends('layouts.admin')

@section('title', 'Mesailer')
@section('page-title', 'Mesailer')
@section('page-subtitle', 'Mesai yönetimi')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0">Tüm Mesailer</h5>
        <small class="text-muted">Mesaileri görüntüleyin ve yönetin</small>
    </div>
    <a href="{{ route('admin.overtimes.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>
        Yeni Mesai
    </a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.overtimes.index') }}" class="row g-3">
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
            <div class="col-md-3">
                @if(request()->hasAny(['employee_id', 'date_from', 'date_to']))
                    <a href="{{ route('admin.overtimes.index') }}" class="btn btn-secondary w-100">
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
                        <th>Personel</th>
                        <th>Tarih</th>
                        <th>Başlangıç</th>
                        <th>Bitiş</th>
                        <th>Saat</th>
                        <th>Saatlik Ücret</th>
                        <th>Tutar</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($overtimes as $overtime)
                        <tr>
                            <td>
                                <div class="fw-medium">{{ $overtime->employee->full_name }}</div>
                                <small class="text-muted">{{ $overtime->company->name }} / {{ $overtime->branch->name }}</small>
                            </td>
                            <td>{{ $overtime->overtime_date->format('d.m.Y') }}</td>
                            <td>{{ date('H:i', strtotime($overtime->start_time)) }}</td>
                            <td>{{ date('H:i', strtotime($overtime->end_time)) }}</td>
                            <td class="fw-bold">{{ number_format($overtime->hours, 2) }} saat</td>
                            <td>{{ number_format($overtime->rate, 2) }} ₺</td>
                            <td class="fw-bold text-primary">{{ number_format($overtime->amount, 2) }} ₺</td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.overtimes.edit', $overtime) }}" class="btn btn-outline-primary" title="Düzenle">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.overtimes.destroy', $overtime) }}" method="POST" class="d-inline" onsubmit="return confirm('Bu mesaiyi silmek istediğinize emin misiniz?');">
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
                                <i class="bi bi-clock-history fs-1 text-muted d-block mb-2"></i>
                                <p class="text-muted mb-3">Henüz mesai bulunmuyor</p>
                                <a href="{{ route('admin.overtimes.create') }}" class="btn btn-primary btn-sm">
                                    İlk mesaiyi ekleyin
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($overtimes->hasPages())
        <div class="card-footer bg-white">
            {{ $overtimes->links() }}
        </div>
    @endif
</div>
@endsection

