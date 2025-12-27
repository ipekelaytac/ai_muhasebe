@extends('layouts.admin')

@section('title', 'Sözleşmeler')
@section('page-title', 'Sözleşmeler')
@section('page-subtitle', 'Çalışan sözleşmeleri')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0">Tüm Sözleşmeler</h5>
        <small class="text-muted">Çalışan sözleşmelerini görüntüleyin ve yönetin</small>
    </div>
    <a href="{{ route('admin.contracts.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>
        Yeni Sözleşme
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Çalışan</th>
                        <th>Geçerlilik</th>
                        <th class="text-end">Net Maaş</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contracts as $contract)
                        <tr>
                            <td class="fw-medium">{{ $contract->employee->full_name }}</td>
                            <td>
                                {{ $contract->effective_from->format('d.m.Y') }} - 
                                {{ $contract->effective_to ? $contract->effective_to->format('d.m.Y') : 'Devam ediyor' }}
                            </td>
                            <td class="text-end">{{ number_format($contract->monthly_net_salary, 2) }} ₺</td>
                            <td class="text-end">
                                <a href="{{ route('admin.contracts.edit', $contract) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil me-1"></i>
                                    Düzenle
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <i class="bi bi-file-earmark-text fs-1 text-muted d-block mb-2"></i>
                                <p class="text-muted mb-3">Henüz sözleşme bulunmuyor</p>
                                <a href="{{ route('admin.contracts.create') }}" class="btn btn-primary btn-sm">
                                    İlk sözleşmeyi oluşturun
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($contracts->hasPages())
        <div class="card-footer bg-white">
            {{ $contracts->links() }}
        </div>
    @endif
</div>
@endsection
