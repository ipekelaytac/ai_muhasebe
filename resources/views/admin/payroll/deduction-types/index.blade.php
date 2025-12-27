@extends('layouts.admin')

@section('title', 'Kesinti Tipleri')
@section('page-title', 'Kesinti Tipleri')
@section('page-subtitle', 'Bordro kesinti tipleri')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0">Tüm Kesinti Tipleri</h5>
        <small class="text-muted">Kesinti tiplerini görüntüleyin ve yönetin</small>
    </div>
    <a href="{{ route('admin.payroll.deduction-types.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>
        Yeni Kesinti Tipi
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Şirket</th>
                        <th>Ad</th>
                        <th>Durum</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deductionTypes as $type)
                        <tr>
                            <td>{{ $type->company->name }}</td>
                            <td class="fw-medium">{{ $type->name }}</td>
                            <td>
                                <span class="badge {{ $type->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $type->is_active ? 'Aktif' : 'Pasif' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.payroll.deduction-types.edit', $type) }}" class="btn btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.payroll.deduction-types.destroy', $type) }}" method="POST" class="d-inline" onsubmit="return confirm('Emin misiniz?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <i class="bi bi-tag fs-1 text-muted d-block mb-2"></i>
                                <p class="text-muted mb-3">Henüz kesinti tipi bulunmuyor</p>
                                <a href="{{ route('admin.payroll.deduction-types.create') }}" class="btn btn-primary btn-sm">
                                    İlk kesinti tipini oluşturun
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($deductionTypes->hasPages())
        <div class="card-footer bg-white">
            {{ $deductionTypes->links() }}
        </div>
    @endif
</div>
@endsection

