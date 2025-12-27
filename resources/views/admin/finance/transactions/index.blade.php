@extends('layouts.admin')

@section('title', 'Finans İşlemleri')
@section('page-title', 'Finans')
@section('page-subtitle', 'Gelir ve gider işlemleri')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0">İşlemler</h5>
        <small class="text-muted">Tüm finansal işlemleri görüntüleyin</small>
    </div>
    <div class="btn-group">
        <a href="{{ route('admin.finance.reports') }}" class="btn btn-outline-secondary">
            <i class="bi bi-graph-up me-1"></i>
            Raporlar
        </a>
        <a href="{{ route('admin.finance.transactions.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>
            Yeni İşlem
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tarih</th>
                        <th>Tip</th>
                        <th>Kategori</th>
                        <th>Açıklama</th>
                        <th class="text-end">Tutar</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->transaction_date->format('d.m.Y') }}</td>
                            <td>
                                <span class="badge {{ $transaction->type === 'income' ? 'bg-success' : 'bg-danger' }}">
                                    {{ $transaction->type === 'income' ? 'Gelir' : 'Gider' }}
                                </span>
                            </td>
                            <td>{{ $transaction->category->name }}</td>
                            <td>
                                <div>{{ $transaction->description }}</div>
                                @if($transaction->attachments->count() > 0)
                                    <div class="mt-2">
                                        @foreach($transaction->attachments as $attachment)
                                            <a href="{{ route('admin.finance.transactions.attachment.show', $attachment) }}" target="_blank" class="badge bg-info text-dark text-decoration-none me-1">
                                                <i class="bi bi-file-earmark me-1"></i>
                                                {{ basename($attachment->file_path) }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="text-end fw-bold {{ $transaction->type === 'income' ? 'text-success' : 'text-danger' }}">
                                {{ number_format($transaction->amount, 2) }} ₺
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.finance.transactions.edit', $transaction) }}" class="btn btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.finance.transactions.destroy', $transaction) }}" method="POST" class="d-inline" onsubmit="return confirm('Emin misiniz?');">
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
                            <td colspan="6" class="text-center py-5">
                                <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                                <p class="text-muted mb-3">Henüz işlem bulunmuyor</p>
                                <a href="{{ route('admin.finance.transactions.create') }}" class="btn btn-primary btn-sm">
                                    İlk işlemi ekleyin
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($transactions->hasPages())
        <div class="card-footer bg-white">
            {{ $transactions->links() }}
        </div>
    @endif
</div>
@endsection
