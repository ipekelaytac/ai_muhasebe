@extends('layouts.admin')

@section('title', 'Avanslar')
@section('page-title', 'Avanslar')
@section('page-subtitle', 'Çalışan avansları')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0">Tüm Avanslar</h5>
        <small class="text-muted">Avansları görüntüleyin ve yönetin</small>
    </div>
    <a href="{{ route('admin.advances.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>
        Yeni Avans
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tarih</th>
                        <th>Çalışan</th>
                        <th class="text-end">Tutar</th>
                        <th class="text-end">Mahsup Edilen</th>
                        <th class="text-end">Kalan</th>
                        <th>Durum</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($advances as $advance)
                        <tr>
                            <td>{{ $advance->advance_date->format('d.m.Y') }}</td>
                            <td>
                                <div class="fw-medium">{{ $advance->employee->full_name }}</div>
                                <small class="text-muted">{{ $advance->company->name }} - {{ $advance->branch->name }}</small>
                            </td>
                            <td class="text-end fw-medium">{{ number_format($advance->amount, 2) }} ₺</td>
                            <td class="text-end text-success">{{ number_format($advance->amount - $advance->remaining_amount, 2) }} ₺</td>
                            <td class="text-end fw-bold {{ $advance->remaining_amount > 0 ? 'text-warning' : 'text-success' }}">
                                {{ number_format($advance->remaining_amount, 2) }} ₺
                            </td>
                            <td>
                                <span class="badge {{ $advance->status ? 'bg-warning' : 'bg-success' }}">
                                    {{ $advance->status ? 'Açık' : 'Kapatıldı' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.advances.edit', $advance) }}" class="btn btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.advances.destroy', $advance) }}" method="POST" class="d-inline" onsubmit="return confirm('Emin misiniz?');">
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
                            <td colspan="7" class="text-center py-5">
                                <i class="bi bi-cash-stack fs-1 text-muted d-block mb-2"></i>
                                <p class="text-muted mb-3">Henüz avans bulunmuyor</p>
                                <a href="{{ route('admin.advances.create') }}" class="btn btn-primary btn-sm">
                                    İlk avansı oluşturun
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($advances->hasPages())
        <div class="card-footer bg-white">
            {{ $advances->links() }}
        </div>
    @endif
</div>
@endsection

