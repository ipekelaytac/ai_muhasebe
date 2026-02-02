@extends('layouts.admin')

@section('title', 'Mesai Tahakkukları')
@section('page-title', 'Mesai Tahakkukları')
@section('page-subtitle', 'Mesai belgelerini görüntüleyin ve yönetin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0">Mesai Tahakkukları</h5>
        <small class="text-muted">Mesai belgelerini görüntüleyin ve yönetin</small>
    </div>
    <a href="{{ route('accounting.overtime.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>
        Yeni Mesai Girişi
    </a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('accounting.overtime.index') }}" class="row g-3">
            <div class="col-md-3">
                <select name="party_id" class="form-select">
                    <option value="">Tüm Personeller</option>
                    @foreach($parties as $party)
                        <option value="{{ $party->id }}" {{ request('party_id') == $party->id ? 'selected' : '' }}>
                            {{ $party->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">Tüm Durumlar</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Bekliyor</option>
                    <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Kısmi</option>
                    <option value="settled" {{ request('status') == 'settled' ? 'selected' : '' }}>Kapandı</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}" placeholder="Başlangıç">
            </div>
            <div class="col-md-3">
                <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}" placeholder="Bitiş">
            </div>
            <div class="col-md-1">
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="open_only" value="1" id="open_only" {{ request('open_only') ? 'checked' : '' }}>
                    <label class="form-check-label" for="open_only">Açık</label>
                </div>
            </div>
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search me-1"></i>Filtrele
                </button>
                @if(request()->hasAny(['party_id', 'status', 'start_date', 'end_date', 'open_only']))
                    <a href="{{ route('accounting.overtime.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-1"></i>Temizle
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        @if($documents->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Belge No</th>
                            <th>Personel</th>
                            <th>Belge Tarihi</th>
                            <th>Vade Tarihi</th>
                            <th>Tutar</th>
                            <th>Ödenen</th>
                            <th>Kalan</th>
                            <th>Durum</th>
                            <th class="text-end">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($documents as $document)
                            <tr>
                                <td><strong>{{ $document->document_number }}</strong></td>
                                <td>{{ $document->party->name }}</td>
                                <td>{{ $document->document_date->format('d.m.Y') }}</td>
                                <td>{{ $document->due_date ? $document->due_date->format('d.m.Y') : '-' }}</td>
                                <td class="fw-bold">{{ number_format($document->total_amount, 2) }} ₺</td>
                                <td class="text-success">{{ number_format($document->paid_amount, 2) }} ₺</td>
                                <td class="fw-bold {{ $document->unpaid_amount > 0 ? 'text-warning' : 'text-success' }}">
                                    {{ number_format($document->unpaid_amount, 2) }} ₺
                                </td>
                                <td>
                                    <span class="badge bg-{{ $document->status == 'settled' ? 'success' : ($document->status == 'partial' ? 'warning' : 'secondary') }}">
                                        {{ \App\Domain\Accounting\Enums\DocumentStatus::getLabel($document->status) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('accounting.overtime.show', $document) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                {{ $documents->appends(request()->query())->links('vendor.pagination.bootstrap-5') }}
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-clock-history display-4 text-muted mb-3"></i>
                <p class="lead text-muted">Mesai tahakkuku bulunamadı.</p>
                <a href="{{ route('accounting.overtime.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>Yeni Mesai Girişi
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
