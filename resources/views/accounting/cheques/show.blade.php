@extends('layouts.admin')

@section('title', 'Çek Detay')
@section('page-title', $cheque->cheque_number)
@section('page-subtitle', 'Çek detayı ve işlemler')

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0">Çek Bilgileri</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <strong>Çek No:</strong> {{ $cheque->cheque_number }}
                    </div>
                    <div class="col-md-6">
                        <strong>Tip:</strong> 
                        <span class="badge {{ $cheque->type === 'received' ? 'bg-success' : 'bg-danger' }}">
                            {{ $cheque->type === 'received' ? 'Alınan' : 'Verilen' }}
                        </span>
                    </div>
                    <div class="col-md-6">
                        <strong>Cari:</strong> 
                        <a href="{{ route('accounting.parties.show', $cheque->party_id) }}" class="text-decoration-none">
                            {{ $cheque->party->name }}
                        </a>
                    </div>
                    <div class="col-md-6">
                        <strong>Durum:</strong> 
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
                        @endif
                    </div>
                    <div class="col-md-6">
                        <strong>Kesim Tarihi:</strong> {{ $cheque->issue_date->format('d.m.Y') }}
                    </div>
                    <div class="col-md-6">
                        <strong>Vade Tarihi:</strong> 
                        {{ $cheque->due_date->format('d.m.Y') }}
                        @if($cheque->due_date < now() && !in_array($cheque->status, ['collected', 'paid', 'bounced', 'cancelled']))
                            <span class="badge bg-danger ms-1">Vadesi Geçti</span>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <strong>Banka:</strong> {{ $cheque->bank_name }}
                    </div>
                    <div class="col-md-6">
                        <strong>Tutar:</strong> 
                        <span class="fw-bold">{{ number_format($cheque->amount, 2) }} ₺</span>
                    </div>
                    @if($cheque->bankAccount)
                        <div class="col-md-6">
                            <strong>Banka Hesabı:</strong> {{ $cheque->bankAccount->name }}
                        </div>
                    @endif
                    @if($cheque->notes)
                        <div class="col-md-12">
                            <strong>Notlar:</strong> 
                            <div class="text-muted">{{ nl2br($cheque->notes) }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        @if($cheque->document)
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0">İlişkili Belge</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <a href="{{ route('accounting.documents.show', $cheque->document) }}" class="text-decoration-none">
                            {{ $cheque->document->document_number }}
                        </a>
                    </p>
                    <small class="text-muted">{{ $cheque->document->document_date->format('d.m.Y') }} - {{ number_format($cheque->document->total_amount, 2) }} ₺</small>
                </div>
            </div>
        @endif
    </div>
    
    <div class="col-md-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0">İşlemler</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    @if($cheque->type === 'received' && $cheque->status === 'in_portfolio')
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#depositModal">
                            <i class="bi bi-bank me-1"></i>Bankaya Ver
                        </button>
                    @endif
                    
                    @if($cheque->type === 'received' && in_array($cheque->status, ['in_portfolio', 'deposited']))
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#collectModal">
                            <i class="bi bi-check-circle me-1"></i>Tahsil Et
                        </button>
                    @endif
                    
                    @if($cheque->type === 'received' && in_array($cheque->status, ['in_portfolio', 'deposited']))
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#bounceModal">
                            <i class="bi bi-x-circle me-1"></i>Karşılıksız İşaretle
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Deposit Modal -->
@if($cheque->type === 'received' && $cheque->status === 'in_portfolio')
    <div class="modal fade" id="depositModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('accounting.cheques.deposit', $cheque) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Çeki Bankaya Ver</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Banka Hesabı <span class="text-danger">*</span></label>
                            <select name="bank_account_id" class="form-select" required>
                                <option value="">Seçiniz</option>
                                @foreach(\App\Domain\Accounting\Models\BankAccount::where('company_id', auth()->user()->company_id)->active()->get() as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notlar</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
                        <button type="submit" class="btn btn-primary">Bankaya Ver</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

<!-- Collect Modal -->
@if($cheque->type === 'received' && in_array($cheque->status, ['in_portfolio', 'deposited']))
    <div class="modal fade" id="collectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('accounting.cheques.collect', $cheque) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Çeki Tahsil Et</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Bu çek tahsil edilecek ve otomatik olarak ödeme kaydı oluşturulacak.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
                        <button type="submit" class="btn btn-success">Tahsil Et</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

<!-- Bounce Modal -->
@if($cheque->type === 'received' && in_array($cheque->status, ['in_portfolio', 'deposited']))
    <div class="modal fade" id="bounceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('accounting.cheques.bounce', $cheque) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Çeki Karşılıksız İşaretle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Neden <span class="text-danger">*</span></label>
                            <textarea name="reason" class="form-control" rows="3" required placeholder="Karşılıksız nedeni..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Protesto Ücreti</label>
                            <input type="number" name="fee" class="form-control" step="0.01" min="0" value="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
                        <button type="submit" class="btn btn-danger">Karşılıksız İşaretle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection
