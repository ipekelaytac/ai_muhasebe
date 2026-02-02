@extends('layouts.admin')

@section('title', 'Avans Kesintileri')
@section('page-title', 'Avans Kesintileri')
@section('page-subtitle', $salaryDocument->document_number . ' - ' . $party->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0">Maaş Belgesi: {{ $salaryDocument->document_number }}</h5>
        <small class="text-muted">{{ $party->name }} - {{ $salaryDocument->document_date->format('d.m.Y') }}</small>
    </div>
    <a href="{{ route('accounting.documents.show', $salaryDocument) }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-1"></i>
        Belgeye Dön
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0">Maaş Özeti</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <td><strong>Toplam Maaş:</strong></td>
                        <td class="text-end">{{ number_format($salaryDocument->total_amount, 2) }} ₺</td>
                    </tr>
                    <tr>
                        <td><strong>Ödenen:</strong></td>
                        <td class="text-end text-success">{{ number_format($salaryDocument->paid_amount, 2) }} ₺</td>
                    </tr>
                    <tr>
                        <td><strong>Kalan:</strong></td>
                        <td class="text-end fw-bold {{ $salaryDocument->unpaid_amount > 0 ? 'text-danger' : 'text-success' }}">
                            {{ number_format($salaryDocument->unpaid_amount, 2) }} ₺
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0">Mevcut Kesintiler</h6>
            </div>
            <div class="card-body">
                @if($existingDeductions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Ödeme No</th>
                                    <th class="text-end">Tutar</th>
                                    <th>Tarih</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($existingDeductions as $deduction)
                                    <tr>
                                        <td><code>{{ $deduction->payment_number }}</code></td>
                                        <td class="text-end">{{ number_format($deduction->amount, 2) }} ₺</td>
                                        <td>{{ $deduction->payment_date->format('d.m.Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted mb-0">Henüz kesinti yapılmamış.</p>
                @endif
            </div>
        </div>
    </div>
</div>

@if($isLocked)
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Dikkat:</strong> Bu dönem kilitli. Kesinti ekleyemezsiniz.
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0">Açık Avanslar</h6>
    </div>
    <div class="card-body">
        @if(count($openAdvances) > 0)
            <form method="POST" action="{{ route('accounting.payroll.deductions.store', $salaryDocument) }}" id="deductionForm">
                @csrf
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50">
                                    <input type="checkbox" id="select_all">
                                </th>
                                <th>Belge No</th>
                                <th>Tarih</th>
                                <th class="text-end">Toplam</th>
                                <th class="text-end">Ödenen</th>
                                <th class="text-end">Kalan</th>
                                <th class="text-end">Kesinti Tutarı</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($openAdvances as $index => $advance)
                                <tr>
                                    <td>
                                        <input type="checkbox" name="deductions[{{ $index }}][advance_document_id]" 
                                               value="{{ $advance['document_id'] }}" 
                                               class="advance-checkbox" 
                                               data-unpaid="{{ $advance['unpaid_amount'] }}">
                                    </td>
                                    <td><code>{{ $advance['document_number'] }}</code></td>
                                    <td>{{ \Carbon\Carbon::parse($advance['document_date'])->format('d.m.Y') }}</td>
                                    <td class="text-end">{{ number_format($advance['total_amount'], 2) }} ₺</td>
                                    <td class="text-end">{{ number_format($advance['paid_amount'], 2) }} ₺</td>
                                    <td class="text-end fw-bold text-danger">{{ number_format($advance['unpaid_amount'], 2) }} ₺</td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input type="number" 
                                                   name="deductions[{{ $index }}][amount]" 
                                                   step="0.01" 
                                                   min="0.01" 
                                                   max="{{ $advance['unpaid_amount'] }}"
                                                   class="form-control deduction-amount" 
                                                   placeholder="0.00"
                                                   disabled>
                                            <span class="input-group-text">₺</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6" class="text-end"><strong>Toplam Kesinti:</strong></td>
                                <td class="text-end">
                                    <strong id="total_deduction">0.00 ₺</strong>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="6" class="text-end"><strong>Net Ödenecek:</strong></td>
                                <td class="text-end">
                                    <strong id="net_payable" class="text-primary">
                                        {{ number_format($salaryDocument->unpaid_amount, 2) }} ₺
                                    </strong>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn btn-primary" {{ $isLocked ? 'disabled' : '' }}>
                        <i class="bi bi-check-circle me-1"></i>
                        Kesintileri Uygula
                    </button>
                </div>
            </form>
        @else
            <p class="text-muted mb-0">Bu personel için açık avans bulunmamaktadır.</p>
        @endif
    </div>
</div>

<script>
const salaryUnpaid = {{ $salaryDocument->unpaid_amount }};
let totalDeduction = 0;

// Handle checkbox change
document.querySelectorAll('.advance-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const amountInput = this.closest('tr').querySelector('.deduction-amount');
        if (this.checked) {
            amountInput.disabled = false;
            amountInput.value = this.dataset.unpaid;
            amountInput.focus();
        } else {
            amountInput.disabled = true;
            amountInput.value = '';
        }
        updateTotals();
    });
});

// Handle amount input change
document.querySelectorAll('.deduction-amount').forEach(input => {
    input.addEventListener('input', function() {
        const maxAmount = parseFloat(this.closest('tr').querySelector('.advance-checkbox').dataset.unpaid);
        const value = parseFloat(this.value) || 0;
        
        if (value > maxAmount) {
            this.value = maxAmount;
            alert('Kesinti tutarı kalan avans tutarını aşamaz.');
        }
        
        updateTotals();
    });
});

// Select all checkbox
document.getElementById('select_all').addEventListener('change', function() {
    document.querySelectorAll('.advance-checkbox').forEach(checkbox => {
        checkbox.checked = this.checked;
        const amountInput = checkbox.closest('tr').querySelector('.deduction-amount');
        if (this.checked) {
            amountInput.disabled = false;
            amountInput.value = checkbox.dataset.unpaid;
        } else {
            amountInput.disabled = true;
            amountInput.value = '';
        }
    });
    updateTotals();
});

function updateTotals() {
    totalDeduction = 0;
    document.querySelectorAll('.advance-checkbox:checked').forEach(checkbox => {
        const amountInput = checkbox.closest('tr').querySelector('.deduction-amount');
        const amount = parseFloat(amountInput.value) || 0;
        totalDeduction += amount;
    });
    
    document.getElementById('total_deduction').textContent = totalDeduction.toFixed(2) + ' ₺';
    
    const netPayable = Math.max(0, salaryUnpaid - totalDeduction);
    const netPayableEl = document.getElementById('net_payable');
    netPayableEl.textContent = netPayable.toFixed(2) + ' ₺';
    
    if (netPayable < 0) {
        netPayableEl.classList.add('text-danger');
        netPayableEl.classList.remove('text-primary');
    } else {
        netPayableEl.classList.remove('text-danger');
        netPayableEl.classList.add('text-primary');
    }
}

// Form validation
document.getElementById('deductionForm').addEventListener('submit', function(e) {
    const checkedBoxes = document.querySelectorAll('.advance-checkbox:checked');
    if (checkedBoxes.length === 0) {
        e.preventDefault();
        alert('Lütfen en az bir avans seçin.');
        return false;
    }
    
    let hasInvalidAmount = false;
    checkedBoxes.forEach(checkbox => {
        const amountInput = checkbox.closest('tr').querySelector('.deduction-amount');
        const amount = parseFloat(amountInput.value) || 0;
        if (amount <= 0) {
            hasInvalidAmount = true;
        }
    });
    
    if (hasInvalidAmount) {
        e.preventDefault();
        alert('Lütfen tüm seçili avanslar için geçerli bir tutar girin.');
        return false;
    }
    
    if (totalDeduction > salaryUnpaid) {
        e.preventDefault();
        alert('Toplam kesinti tutarı maaşın kalan borcundan fazla olamaz.');
        return false;
    }
});
</script>
@endsection
