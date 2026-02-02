@extends('layouts.admin')

@section('title', 'Belge Detay')
@section('page-title', $document->document_number)
@section('page-subtitle', $document->type_label)

@section('content')
@if($document->isInLockedPeriod())
    <div class="alert alert-warning">
        <i class="bi bi-lock me-2"></i>
        <strong>Uyarı:</strong> Bu belge kilitli bir dönemde. Değişiklik yapmak için ters kayıt kullanın.
    </div>
@endif

<div class="row mb-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Belge Bilgileri</h6>
                @if($document->canModify())
                    <a href="{{ route('accounting.documents.edit', $document) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil me-1"></i>Düzenle
                    </a>
                @endif
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <strong>Belge No:</strong> {{ $document->document_number }}
                    </div>
                    <div class="col-md-6">
                        <strong>Tip:</strong> 
                        <span class="badge {{ $document->direction === 'receivable' ? 'bg-success' : 'bg-danger' }}">
                            {{ $document->type_label }}
                        </span>
                    </div>
                    <div class="col-md-6">
                        <strong>Cari:</strong> 
                        <a href="{{ route('accounting.parties.show', $document->party_id) }}" class="text-decoration-none">
                            {{ $document->party->name }}
                        </a>
                    </div>
                    <div class="col-md-6">
                        <strong>Durum:</strong> 
                        @if($document->status === 'pending')
                            <span class="badge bg-warning">Bekliyor</span>
                        @elseif($document->status === 'partial')
                            <span class="badge bg-info">Kısmi Ödendi</span>
                        @elseif($document->status === 'settled')
                            <span class="badge bg-success">Kapandı</span>
                        @elseif($document->status === 'cancelled')
                            <span class="badge bg-secondary">İptal</span>
                        @elseif($document->status === 'reversed')
                            <span class="badge bg-dark">Ters Kayıt</span>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <strong>Belge Tarihi:</strong> {{ $document->document_date->format('d.m.Y') }}
                    </div>
                    <div class="col-md-6">
                        <strong>Vade Tarihi:</strong> 
                        @if($document->due_date)
                            {{ $document->due_date->format('d.m.Y') }}
                            @if($document->due_date < now() && in_array($document->status, ['pending', 'partial']))
                                <span class="badge bg-danger ms-1">Vadesi Geçti</span>
                            @endif
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <strong>Toplam Tutar:</strong> 
                        <span class="fw-bold">{{ number_format($document->total_amount, 2) }} ₺</span>
                    </div>
                    <div class="col-md-6">
                        <strong>Kalan Tutar:</strong> 
                        <span class="fw-bold {{ $document->unpaid_amount > 0 ? 'text-danger' : 'text-success' }}">
                            {{ number_format($document->unpaid_amount, 2) }} ₺
                        </span>
                    </div>
                    @if($document->reference_number)
                        <div class="col-md-6">
                            <strong>Referans No:</strong> {{ $document->reference_number }}
                        </div>
                    @endif
                    @if($document->category)
                        <div class="col-md-6">
                            <strong>Kategori:</strong> {{ $document->category->name }}
                        </div>
                    @endif
                    @if($document->description)
                        <div class="col-md-12">
                            <strong>Açıklama:</strong> {{ $document->description }}
                        </div>
                    @endif
                    @if($document->notes)
                        <div class="col-md-12">
                            <strong>Notlar:</strong> 
                            <div class="text-muted">{{ nl2br($document->notes) }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        @if($document->activeAllocations->count() > 0)
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0">Kapamalar ({{ $document->activeAllocations->count() }})</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tarih</th>
                                    <th>Ödeme No</th>
                                    <th class="text-end">Tutar</th>
                                    <th class="text-end">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($document->activeAllocations as $allocation)
                                    <tr>
                                        <td>{{ $allocation->allocation_date->format('d.m.Y') }}</td>
                                        <td>
                                            <a href="{{ route('accounting.payments.show', $allocation->payment_id) }}" class="text-decoration-none">
                                                {{ $allocation->payment->payment_number }}
                                            </a>
                                        </td>
                                        <td class="text-end">{{ number_format($allocation->amount, 2) }} ₺</td>
                                        <td class="text-end">
                                            @if(!$document->isInLockedPeriod())
                                                <form action="{{ route('accounting.allocations.cancel', $allocation) }}" method="POST" class="d-inline" onsubmit="return confirm('Bu kapamayı iptal etmek istediğinize emin misiniz?');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
    
    <div class="col-md-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0">Özet</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Toplam Tutar:</span>
                        <strong>{{ number_format($document->total_amount, 2) }} ₺</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Ödenen:</span>
                        <strong class="text-success">{{ number_format($document->allocated_amount, 2) }} ₺</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold">Kalan:</span>
                        <strong class="{{ $document->unpaid_amount > 0 ? 'text-danger' : 'text-success' }}">
                            {{ number_format($document->unpaid_amount, 2) }} ₺
                        </strong>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0">İşlemler</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    @if($document->unpaid_amount > 0)
                        <a href="{{ route('accounting.payments.create', ['party_id' => $document->party_id]) }}" class="btn btn-success">
                            <i class="bi bi-cash-coin me-1"></i>Ödeme/Tahsilat Gir
                        </a>
                    @endif
                    
                    @if(in_array($document->status, ['pending', 'partial']) && !$document->isInLockedPeriod())
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#cancelModal">
                            <i class="bi bi-x-circle me-1"></i>İptal Et
                        </button>
                    @endif
                    
                    @if(!in_array($document->status, ['cancelled', 'reversed']))
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#reverseModal">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Ters Kayıt
                        </button>
                    @endif
                </div>
            </div>
        </div>
        
        @if($document->reversedDocument)
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0">Ters Kayıt</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <a href="{{ route('accounting.documents.show', $document->reversedDocument) }}" class="text-decoration-none">
                            {{ $document->reversedDocument->document_number }}
                        </a>
                    </p>
                    <small class="text-muted">{{ $document->reversedDocument->document_date->format('d.m.Y') }}</small>
                </div>
            </div>
        @endif
        
        @if($document->reversalDocument)
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0">Bu Belge Ters Kayıt</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>Orijinal Belge:</strong>
                        <a href="{{ route('accounting.documents.show', $document->reversedDocument) }}" class="text-decoration-none">
                            {{ $document->reversedDocument->document_number }}
                        </a>
                    </p>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('accounting.documents.cancel', $document) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Belgeyi İptal Et</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Bu belgeyi iptal etmek istediğinize emin misiniz?</p>
                    <div class="mb-3">
                        <label class="form-label">İptal Nedeni</label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="İptal nedeni..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
                    <button type="submit" class="btn btn-warning">İptal Et</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reverse Modal -->
<div class="modal fade" id="reverseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('accounting.documents.reverse', $document) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Ters Kayıt Oluştur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Bu belge için ters kayıt oluşturulacak. Bu işlem geri alınamaz.</p>
                    <div class="mb-3">
                        <label class="form-label">Ters Kayıt Nedeni</label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="Ters kayıt nedeni..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
                    <button type="submit" class="btn btn-danger">Ters Kayıt Oluştur</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
