@extends('layouts.admin')

@section('title', 'Tahakkuklar')
@section('page-title', 'Tahakkuklar')
@section('page-subtitle', 'Belge yönetimi')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0">Tüm Belgeler</h5>
        <small class="text-muted">Tahakkukları görüntüleyin ve yönetin</small>
    </div>
    <a href="{{ route('accounting.documents.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>
        Yeni Tahakkuk
    </a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('accounting.documents.index') }}">
            <div class="row g-3">
                <div class="col-md-12 col-lg-3">
                    <label class="form-label small">Arama</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Belge no, açıklama..." value="{{ request('search') }}">
                </div>
                <div class="col-md-6 col-lg-2">
                    <label class="form-label small">Tip</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">Tüm Tipler</option>
                        @foreach(\App\Domain\Accounting\Enums\DocumentType::ALL as $type)
                            <option value="{{ $type }}" {{ request('type') == $type ? 'selected' : '' }}>
                                {{ \App\Domain\Accounting\Enums\DocumentType::getLabel($type) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-lg-2">
                    <label class="form-label small">Yön</label>
                    <select name="direction" class="form-select form-select-sm">
                        <option value="">Tüm Yönler</option>
                        <option value="receivable" {{ request('direction') == 'receivable' ? 'selected' : '' }}>Alacak</option>
                        <option value="payable" {{ request('direction') == 'payable' ? 'selected' : '' }}>Borç</option>
                    </select>
                </div>
                <div class="col-md-6 col-lg-2">
                    <label class="form-label small">Durum</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Tüm Durumlar</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Bekliyor</option>
                        <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Kısmi</option>
                        <option value="settled" {{ request('status') == 'settled' ? 'selected' : '' }}>Kapandı</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>İptal</option>
                    </select>
                </div>
                <div class="col-md-6 col-lg-3">
                    <label class="form-label small">Cari</label>
                    <select name="party_id" class="form-select form-select-sm">
                        <option value="">Tüm Cariler</option>
                        @foreach($parties as $party)
                            <option value="{{ $party->id }}" {{ request('party_id') == $party->id ? 'selected' : '' }}>
                                {{ $party->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-lg-2">
                    <label class="form-label small">Başlangıç</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-6 col-lg-2">
                    <label class="form-label small">Bitiş</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                </div>
                <div class="col-md-6 col-lg-2">
                    <label class="form-label small">&nbsp;</label>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="open_only" value="1" id="open_only" {{ request('open_only') ? 'checked' : '' }}>
                        <label class="form-check-label" for="open_only">Sadece Açık</label>
                    </div>
                </div>
                <div class="col-md-6 col-lg-2">
                    <label class="form-label small">&nbsp;</label>
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-search me-1"></i>Filtrele
                    </button>
                </div>
                @if(request()->hasAny(['search', 'type', 'direction', 'status', 'party_id', 'start_date', 'end_date', 'open_only']))
                <div class="col-md-6 col-lg-2">
                    <label class="form-label small">&nbsp;</label>
                    <a href="{{ route('accounting.documents.index') }}" class="btn btn-secondary btn-sm w-100">
                        <i class="bi bi-x-circle me-1"></i>Temizle
                    </a>
                </div>
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
                        <th>Tarih</th>
                        <th>Vade</th>
                        <th>Cari</th>
                        <th>Tip</th>
                        <th>Belge No</th>
                        <th class="text-end">Tutar</th>
                        <th class="text-end">Kalan</th>
                        <th>Durum</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($documents as $document)
                        <tr>
                            <td>{{ $document->document_date->format('d.m.Y') }}</td>
                            <td>
                                @if($document->due_date)
                                    {{ $document->due_date->format('d.m.Y') }}
                                    @if($document->due_date < now() && in_array($document->status, ['pending', 'partial']))
                                        <span class="badge bg-danger ms-1">Vadesi Geçti</span>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('accounting.parties.show', $document->party_id) }}" class="text-decoration-none">
                                    {{ $document->party->name }}
                                </a>
                            </td>
                            <td>
                                <span class="badge {{ $document->direction === 'receivable' ? 'bg-success' : 'bg-danger' }}">
                                    {{ $document->type_label }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('accounting.documents.show', $document) }}" class="text-decoration-none">
                                    {{ $document->document_number }}
                                </a>
                            </td>
                            <td class="text-end">{{ number_format($document->total_amount, 2) }} ₺</td>
                            <td class="text-end">
                                @if($document->unpaid_amount > 0)
                                    <strong class="{{ $document->unpaid_amount == $document->total_amount ? 'text-danger' : 'text-warning' }}">
                                        {{ number_format($document->unpaid_amount, 2) }} ₺
                                    </strong>
                                @else
                                    <span class="text-muted">0,00 ₺</span>
                                @endif
                            </td>
                            <td>
                                @if($document->status === 'pending')
                                    <span class="badge bg-warning">Bekliyor</span>
                                @elseif($document->status === 'partial')
                                    <span class="badge bg-info">Kısmi</span>
                                @elseif($document->status === 'settled')
                                    <span class="badge bg-success">Kapandı</span>
                                @elseif($document->status === 'cancelled')
                                    <span class="badge bg-secondary">İptal</span>
                                @elseif($document->status === 'reversed')
                                    <span class="badge bg-dark">Ters Kayıt</span>
                                @else
                                    <span class="badge bg-secondary">{{ $document->status_label }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('accounting.documents.show', $document) }}" class="btn btn-outline-info" title="Detay">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if($document->canModify())
                                        <a href="{{ route('accounting.documents.edit', $document) }}" class="btn btn-outline-primary" title="Düzenle">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                                <p class="text-muted mb-0">Henüz belge bulunmuyor</p>
                                <a href="{{ route('accounting.documents.create') }}" class="btn btn-primary btn-sm mt-3">
                                    <i class="bi bi-plus-circle me-1"></i>Yeni Tahakkuk Oluştur
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if(method_exists($documents, 'hasPages') && $documents->hasPages())
        <div class="card-footer bg-white">
            {{ $documents->appends(request()->query())->links('vendor.pagination.bootstrap-5') }}
        </div>
    @endif
</div>
@endsection
