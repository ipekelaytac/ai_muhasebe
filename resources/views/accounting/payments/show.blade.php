@extends('layouts.admin')

@section('title', 'Ödeme Detay')
@section('page-title', $payment->payment_number)
@section('page-subtitle', $payment->type_label)

@section('content')
@if($payment->isInLockedPeriod())
    <div class="alert alert-warning">
        <i class="bi bi-lock me-2"></i>
        <strong>Uyarı:</strong> Bu ödeme kilitli bir dönemde. Değişiklik yapmak için ters kayıt kullanın.
    </div>
@endif

<div class="row mb-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Ödeme Bilgileri</h6>
                @if($payment->canModify())
                    <a href="{{ route('accounting.payments.edit', $payment) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil me-1"></i>Düzenle
                    </a>
                @endif
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <strong>Ödeme No:</strong> {{ $payment->payment_number }}
                    </div>
                    <div class="col-md-6">
                        <strong>Tip:</strong> 
                        <span class="badge {{ $payment->direction === 'in' ? 'bg-success' : 'bg-danger' }}">
                            {{ $payment->type_label }}
                        </span>
                    </div>
                    @if($payment->party)
                        <div class="col-md-6">
                            <strong>Cari:</strong> 
                            <a href="{{ route('accounting.parties.show', $payment->party_id) }}" class="text-decoration-none">
                                {{ $payment->party->name }}
                            </a>
                        </div>
                    @endif
                    <div class="col-md-6">
                        <strong>Durum:</strong> 
                        @if($payment->status === 'draft')
                            <span class="badge bg-secondary">Taslak</span>
                        @elseif($payment->status === 'confirmed')
                            <span class="badge bg-success">Onaylı</span>
                        @elseif($payment->status === 'cancelled')
                            <span class="badge bg-danger">İptal</span>
                        @elseif($payment->status === 'reversed')
                            <span class="badge bg-dark">Ters Kayıt</span>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <strong>Ödeme Tarihi:</strong> {{ $payment->payment_date->format('d.m.Y') }}
                    </div>
                    <div class="col-md-6">
                        <strong>Tutar:</strong> 
                        <span class="fw-bold">{{ number_format($payment->amount, 2) }} ₺</span>
                    </div>
                    @if($payment->cashbox)
                        <div class="col-md-6">
                            <strong>Kasa:</strong> {{ $payment->cashbox->name }}
                        </div>
                    @endif
                    @if($payment->bankAccount)
                        <div class="col-md-6">
                            <strong>Banka:</strong> {{ $payment->bankAccount->name }}
                        </div>
                    @endif
                    @if($payment->fee_amount > 0)
                        <div class="col-md-6">
                            <strong>Komisyon:</strong> {{ number_format($payment->fee_amount, 2) }} ₺
                        </div>
                        <div class="col-md-6">
                            <strong>Net Tutar:</strong> {{ number_format($payment->net_amount, 2) }} ₺
                        </div>
                    @endif
                    @if($payment->reference_number)
                        <div class="col-md-6">
                            <strong>Referans No:</strong> {{ $payment->reference_number }}
                        </div>
                    @endif
                    @if($payment->description)
                        <div class="col-md-12">
                            <strong>Açıklama:</strong> {{ $payment->description }}
                        </div>
                    @endif
                    @if($payment->notes)
                        <div class="col-md-12">
                            <strong>Notlar:</strong> 
                            <div class="text-muted">{{ nl2br($payment->notes) }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        @if($payment->activeAllocations->count() > 0)
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0">Dağıtımlar ({{ $payment->activeAllocations->count() }})</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tarih</th>
                                    <th>Belge No</th>
                                    <th class="text-end">Tutar</th>
                                    <th class="text-end">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($payment->activeAllocations as $allocation)
                                    <tr>
                                        <td>{{ $allocation->allocation_date->format('d.m.Y') }}</td>
                                        <td>
                                            <a href="{{ route('accounting.documents.show', $allocation->document_id) }}" class="text-decoration-none">
                                                {{ $allocation->document->document_number }}
                                            </a>
                                        </td>
                                        <td class="text-end">{{ number_format($allocation->amount, 2) }} ₺</td>
                                        <td class="text-end">
                                            @if(!$payment->isInLockedPeriod())
                                                <form action="{{ route('accounting.allocations.cancel', $allocation) }}" method="POST" class="d-inline" onsubmit="return confirm('Bu dağıtımı iptal etmek istediğinize emin misiniz?');">
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
                        <strong>{{ number_format($payment->amount, 2) }} ₺</strong>
                    </div>
                    @if($payment->fee_amount > 0)
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Komisyon:</span>
                            <strong class="text-danger">-{{ number_format($payment->fee_amount, 2) }} ₺</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Net Tutar:</span>
                            <strong>{{ number_format($payment->net_amount, 2) }} ₺</strong>
                        </div>
                    @endif
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Dağıtılan:</span>
                        <strong class="text-success">{{ number_format($payment->allocated_amount, 2) }} ₺</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold">Kalan:</span>
                        <strong class="{{ $payment->unallocated_amount > 0 ? 'text-warning' : 'text-success' }}">
                            {{ number_format($payment->unallocated_amount, 2) }} ₺
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
                    @if($payment->status === 'confirmed' && $payment->unallocated_amount > 0)
                        <a href="{{ route('accounting.allocations.create', $payment) }}" class="btn btn-success">
                            <i class="bi bi-diagram-3 me-1"></i>Dağıt
                        </a>
                    @endif
                    
                    @if(in_array($payment->status, ['draft', 'confirmed']) && !$payment->isInLockedPeriod())
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#cancelModal">
                            <i class="bi bi-x-circle me-1"></i>İptal Et
                        </button>
                    @endif
                    
                    @if(!in_array($payment->status, ['cancelled', 'reversed']))
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#reverseModal">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Ters Kayıt
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('accounting.payments.cancel', $payment) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Ödemeyi İptal Et</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Bu ödemeyi iptal etmek istediğinize emin misiniz?</p>
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
            <form method="POST" action="{{ route('accounting.payments.reverse', $payment) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Ters Kayıt Oluştur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Bu ödeme için ters kayıt oluşturulacak. Bu işlem geri alınamaz.</p>
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
