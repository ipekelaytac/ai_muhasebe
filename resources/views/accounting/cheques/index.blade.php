@extends('layouts.admin')

@section('title', 'Çekler / Senetler')
@section('page-title', 'Çekler / Senetler')
@section('page-subtitle', 'Çek ve senet yönetimi')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0">Tüm Çekler</h5>
        <small class="text-muted">Çek ve senetleri görüntüleyin ve yönetin</small>
    </div>
    <a href="{{ route('accounting.cheques.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>
        Yeni Çek/Senet
    </a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('accounting.cheques.index') }}" class="row g-3">
            <div class="col-md-3">
                <select name="type" class="form-select">
                    <option value="">Tüm Tipler</option>
                    <option value="received" {{ request('type') == 'received' ? 'selected' : '' }}>Alınan</option>
                    <option value="issued" {{ request('type') == 'issued' ? 'selected' : '' }}>Verilen</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">Tüm Durumlar</option>
                    <option value="in_portfolio" {{ request('status') == 'in_portfolio' ? 'selected' : '' }}>Portföyde</option>
                    <option value="deposited" {{ request('status') == 'deposited' ? 'selected' : '' }}>Bankada</option>
                    <option value="collected" {{ request('status') == 'collected' ? 'selected' : '' }}>Tahsil Edildi</option>
                    <option value="bounced" {{ request('status') == 'bounced' ? 'selected' : '' }}>Karşılıksız</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="party_id" class="form-select">
                    <option value="">Tüm Cariler</option>
                    @foreach($parties as $party)
                        <option value="{{ $party->id }}" {{ request('party_id') == $party->id ? 'selected' : '' }}>
                            {{ $party->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i>Filtrele
                </button>
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
                        <th>Çek No</th>
                        <th>Tarih</th>
                        <th>Vade</th>
                        <th>Cari</th>
                        <th>Banka</th>
                        <th class="text-end">Tutar</th>
                        <th>Durum</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cheques as $cheque)
                        <tr>
                            <td>
                                <a href="{{ route('accounting.cheques.show', $cheque) }}" class="text-decoration-none">
                                    {{ $cheque->cheque_number }}
                                </a>
                            </td>
                            <td>{{ $cheque->issue_date->format('d.m.Y') }}</td>
                            <td>
                                {{ $cheque->due_date->format('d.m.Y') }}
                                @if($cheque->due_date < now() && !in_array($cheque->status, ['collected', 'paid', 'bounced', 'cancelled']))
                                    <span class="badge bg-danger ms-1">Vadesi Geçti</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('accounting.parties.show', $cheque->party_id) }}" class="text-decoration-none">
                                    {{ $cheque->party->name }}
                                </a>
                            </td>
                            <td>{{ $cheque->bank_name }}</td>
                            <td class="text-end">{{ number_format($cheque->amount, 2) }} ₺</td>
                            <td>
                                @if($cheque->status === 'in_portfolio')
                                    <span class="badge bg-info">Portföyde</span>
                                @elseif($cheque->status === 'deposited')
                                    <span class="badge bg-primary">Bankada</span>
                                @elseif($cheque->status === 'collected')
                                    <span class="badge bg-success">Tahsil Edildi</span>
                                @elseif($cheque->status === 'bounced')
                                    <span class="badge bg-danger">Karşılıksız</span>
                                @elseif($cheque->status === 'paid')
                                    <span class="badge bg-success">Ödendi</span>
                                @else
                                    <span class="badge bg-secondary">{{ $cheque->status }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('accounting.cheques.show', $cheque) }}" class="btn btn-outline-info" title="Detay">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                                <p class="text-muted mb-0">Henüz çek bulunmuyor</p>
                                <a href="{{ route('accounting.cheques.create') }}" class="btn btn-primary btn-sm mt-3">
                                    <i class="bi bi-plus-circle me-1"></i>Yeni Çek Oluştur
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($cheques->hasPages())
        <div class="card-footer bg-white">
            {{ $cheques->links() }}
        </div>
    @endif
</div>
@endsection
