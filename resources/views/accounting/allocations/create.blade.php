@extends('layouts.admin')

@section('title', 'Ödeme Dağıt')
@section('page-title', 'Ödeme Dağıt')
@section('page-subtitle', $payment->payment_number)

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <strong>Ödeme No:</strong> {{ $payment->payment_number }}
            </div>
            <div class="col-md-6">
                <strong>Tarih:</strong> {{ $payment->payment_date->format('d.m.Y') }}
            </div>
            <div class="col-md-6">
                <strong>Tutar:</strong> {{ number_format($payment->amount, 2) }} ₺
            </div>
            <div class="col-md-6">
                <strong>Kalan:</strong> 
                <span class="fw-bold text-warning">{{ number_format($payment->unallocated_amount, 2) }} ₺</span>
            </div>
            @if($payment->party)
                <div class="col-md-12">
                    <strong>Cari:</strong> {{ $payment->party->name }}
                </div>
            @endif
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('accounting.allocations.store', $payment) }}" id="allocationForm">
            @csrf
            
            <div id="allocations-container">
                @if($suggestions && count($suggestions) > 0)
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Öneri:</strong> En eski vadeli belgeler otomatik seçildi. Gerekirse değiştirebilirsiniz.
                    </div>
                @endif
                
                <div class="table-responsive mb-3">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Belge No</th>
                                <th>Tip</th>
                                <th>Vade</th>
                                <th class="text-end">Toplam</th>
                                <th class="text-end">Kalan</th>
                                <th class="text-end">Dağıtılacak</th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="allocations-tbody">
                            @if($suggestions && count($suggestions) > 0)
                                @foreach($suggestions as $index => $suggestion)
                                    <tr data-document-id="{{ $suggestion['document_id'] }}">
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <a href="{{ route('accounting.documents.show', $suggestion['document_id']) }}" target="_blank" class="text-decoration-none">
                                                {{ $suggestion['document_number'] }}
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge {{ $suggestion['direction'] === 'receivable' ? 'bg-success' : 'bg-danger' }}">
                                                {{ $suggestion['type_label'] }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($suggestion['due_date'])
                                                {{ \Carbon\Carbon::parse($suggestion['due_date'])->format('d.m.Y') }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-end">{{ number_format($suggestion['total_amount'], 2) }} ₺</td>
                                        <td class="text-end">
                                            <strong class="text-danger">{{ number_format($suggestion['unpaid_amount'], 2) }} ₺</strong>
                                        </td>
                                        <td>
                                            <input type="hidden" name="allocations[{{ $index }}][document_id]" value="{{ $suggestion['document_id'] }}">
                                            <input type="number" 
                                                   name="allocations[{{ $index }}][amount]" 
                                                   class="form-control allocation-amount" 
                                                   value="{{ min($suggestion['unpaid_amount'], $payment->unallocated_amount) }}" 
                                                   step="0.01" 
                                                   min="0.01" 
                                                   max="{{ $suggestion['unpaid_amount'] }}"
                                                   data-unpaid="{{ $suggestion['unpaid_amount'] }}"
                                                   required>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-row">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr id="no-documents">
                                    <td colspan="8" class="text-center py-4">
                                        <p class="text-muted mb-2">Bu cari için açık belge bulunamadı.</p>
                                        <a href="{{ route('accounting.documents.create', ['party_id' => $payment->party_id]) }}" class="btn btn-sm btn-primary">
                                            <i class="bi bi-plus-circle me-1"></i>Yeni Belge Oluştur
                                        </a>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6" class="text-end"><strong>Toplam:</strong></td>
                                <td class="text-end">
                                    <strong id="total-allocated">0,00 ₺</strong>
                                </td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="6" class="text-end"><strong>Kalan:</strong></td>
                                <td class="text-end">
                                    <strong id="remaining-amount" class="text-warning">{{ number_format($payment->unallocated_amount, 2) }} ₺</strong>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-between">
                <a href="{{ route('accounting.payments.show', $payment) }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Geri
                </a>
                <button type="submit" class="btn btn-primary" id="submit-btn" {{ (!$suggestions || count($suggestions) == 0) ? 'disabled' : '' }}>
                    <i class="bi bi-check-circle me-1"></i>Dağıt
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let availableAmount = {{ $payment->unallocated_amount }};
    
    function updateTotals() {
        let total = 0;
        document.querySelectorAll('.allocation-amount').forEach(input => {
            const amount = parseFloat(input.value) || 0;
            total += amount;
        });
        
        document.getElementById('total-allocated').textContent = total.toFixed(2).replace('.', ',') + ' ₺';
        
        const remaining = availableAmount - total;
        const remainingEl = document.getElementById('remaining-amount');
        remainingEl.textContent = remaining.toFixed(2).replace('.', ',') + ' ₺';
        
        if (remaining < 0) {
            remainingEl.classList.add('text-danger');
            remainingEl.classList.remove('text-warning');
            document.getElementById('submit-btn').disabled = true;
        } else {
            remainingEl.classList.remove('text-danger');
            remainingEl.classList.add('text-warning');
            document.getElementById('submit-btn').disabled = false;
        }
    }
    
    document.querySelectorAll('.allocation-amount').forEach(input => {
        input.addEventListener('input', function() {
            const max = parseFloat(this.dataset.unpaid);
            if (parseFloat(this.value) > max) {
                this.value = max;
            }
            updateTotals();
        });
    });
    
    document.querySelectorAll('.remove-row').forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('tr').remove();
            updateTotals();
        });
    });
    
    updateTotals();
</script>
@endsection
