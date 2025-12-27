@extends('layouts.admin')

@section('title', 'Bordro')
@section('page-title', 'Bordro')
@section('page-subtitle', 'Bordro dönemleri')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0">Bordro Dönemleri</h5>
        <small class="text-muted">Bordro dönemlerini görüntüleyin ve yönetin</small>
    </div>
    <a href="{{ route('admin.payroll.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>
        Yeni Dönem
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Dönem</th>
                        <th>Şirket</th>
                        <th>Şube</th>
                        <th>Durum</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($periods as $period)
                        <tr>
                            <td class="fw-medium">{{ $period->period_name }}</td>
                            <td>{{ $period->company->name }}</td>
                            <td>{{ $period->branch->name }}</td>
                            <td>
                                <span class="badge {{ $period->status ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $period->status ? 'Açık' : 'Kapalı' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.payroll.show', $period) }}" class="btn btn-sm btn-outline-primary">
                                    Detay
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <i class="bi bi-cash-coin fs-1 text-muted d-block mb-2"></i>
                                <p class="text-muted mb-3">Henüz dönem bulunmuyor</p>
                                <a href="{{ route('admin.payroll.create') }}" class="btn btn-primary btn-sm">
                                    İlk dönemi oluşturun
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($periods->hasPages())
        <div class="card-footer bg-white">
            {{ $periods->links() }}
        </div>
    @endif
</div>
@endsection
