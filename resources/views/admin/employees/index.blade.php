@extends('layouts.admin')

@section('title', 'Çalışanlar')
@section('page-title', 'Çalışanlar')
@section('page-subtitle', 'Çalışan yönetimi')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0">Tüm Çalışanlar</h5>
        <small class="text-muted">Çalışanları görüntüleyin ve yönetin</small>
    </div>
    <a href="{{ route('admin.employees.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>
        Yeni Çalışan
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Çalışan</th>
                        <th>Şirket / Şube</th>
                        <th>İletişim</th>
                        <th>Durum</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                        <span class="text-primary fw-bold">{{ substr($employee->full_name, 0, 1) }}</span>
                                    </div>
                                    <span class="fw-medium">{{ $employee->full_name }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="fw-medium">{{ $employee->company->name }}</div>
                                <small class="text-muted">{{ $employee->branch->name }}</small>
                            </td>
                            <td>
                                <i class="bi bi-telephone me-1 text-muted"></i>
                                {{ $employee->phone ?? 'Belirtilmemiş' }}
                            </td>
                            <td>
                                <span class="badge {{ $employee->status ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $employee->status ? 'Aktif' : 'Pasif' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.employees.edit', $employee) }}" class="btn btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.employees.toggle-status', $employee) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-warning">
                                            <i class="bi bi-toggle-{{ $employee->status ? 'on' : 'off' }}"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <i class="bi bi-people fs-1 text-muted d-block mb-2"></i>
                                <p class="text-muted mb-3">Henüz çalışan bulunmuyor</p>
                                <a href="{{ route('admin.employees.create') }}" class="btn btn-primary btn-sm">
                                    İlk çalışanı ekleyin
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($employees->hasPages())
        <div class="card-footer bg-white">
            {{ $employees->links() }}
        </div>
    @endif
</div>
@endsection
