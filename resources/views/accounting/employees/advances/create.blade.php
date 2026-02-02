@extends('layouts.admin')

@section('title', 'Avans Ver')
@section('page-title', 'Avans Ver')
@section('page-subtitle', $party->name)

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('accounting.employees.advances.store', $party) }}" id="advanceForm">
            @csrf
            
            @if($isLocked)
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Dikkat:</strong> Bu dönem kilitli. Avans veremezsiniz. Lütfen açık bir dönem seçin veya dönemi açın.
                </div>
            @endif
            
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Personel</label>
                    <input type="text" class="form-control" value="{{ $party->name }}" disabled>
                    <input type="hidden" name="party_id" value="{{ $party->id }}">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Şube</label>
                    <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                        <option value="">Seçiniz</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('branch_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Avans Tarihi <span class="text-danger">*</span></label>
                    <input type="date" name="advance_date" class="form-control @error('advance_date') is-invalid @enderror" 
                           value="{{ old('advance_date', now()->toDateString()) }}" required>
                    @error('advance_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Tutar <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" name="amount" step="0.01" min="0.01" 
                               class="form-control @error('amount') is-invalid @enderror" 
                               value="{{ old('amount') }}" required>
                        <span class="input-group-text">₺</span>
                    </div>
                    @error('amount')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Vade Tarihi</label>
                    <input type="date" name="due_date" class="form-control @error('due_date') is-invalid @enderror" 
                           value="{{ old('due_date') }}">
                    @error('due_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Ödeme Kaynağı <span class="text-danger">*</span></label>
                    <select name="payment_source_type" id="payment_source_type" 
                            class="form-select @error('payment_source_type') is-invalid @enderror" required>
                        <option value="">Seçiniz</option>
                        <option value="cash" {{ old('payment_source_type') == 'cash' ? 'selected' : '' }}>Kasa</option>
                        <option value="bank" {{ old('payment_source_type') == 'bank' ? 'selected' : '' }}>Banka</option>
                    </select>
                    @error('payment_source_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6" id="cashbox_field" style="display: none;">
                    <label class="form-label">Kasa <span class="text-danger">*</span></label>
                    <select name="cashbox_id" class="form-select @error('cashbox_id') is-invalid @enderror">
                        <option value="">Seçiniz</option>
                        @foreach($cashboxes as $cashbox)
                            <option value="{{ $cashbox->id }}" {{ old('cashbox_id') == $cashbox->id ? 'selected' : '' }}>
                                {{ $cashbox->name }} ({{ $cashbox->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('cashbox_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6" id="bank_field" style="display: none;">
                    <label class="form-label">Banka Hesabı <span class="text-danger">*</span></label>
                    <select name="bank_account_id" class="form-select @error('bank_account_id') is-invalid @enderror">
                        <option value="">Seçiniz</option>
                        @foreach($bankAccounts as $bankAccount)
                            <option value="{{ $bankAccount->id }}" {{ old('bank_account_id') == $bankAccount->id ? 'selected' : '' }}>
                                {{ $bankAccount->name }} ({{ $bankAccount->account_number }})
                            </option>
                        @endforeach
                    </select>
                    @error('bank_account_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="row g-3 mb-4">
                <div class="col-md-12">
                    <label class="form-label">Açıklama</label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-12">
                    <label class="form-label">Notlar</label>
                    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2">{{ old('notes') }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="{{ route('accounting.employees.advances.index', $party) }}" class="btn btn-secondary">
                    <i class="bi bi-x-circle me-1"></i>
                    İptal
                </a>
                <button type="submit" class="btn btn-primary" {{ $isLocked ? 'disabled' : '' }}>
                    <i class="bi bi-check-circle me-1"></i>
                    Avans Ver
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('payment_source_type').addEventListener('change', function() {
    const sourceType = this.value;
    const cashboxField = document.getElementById('cashbox_field');
    const bankField = document.getElementById('bank_field');
    
    if (sourceType === 'cash') {
        cashboxField.style.display = 'block';
        bankField.style.display = 'none';
        document.querySelector('[name="bank_account_id"]').value = '';
    } else if (sourceType === 'bank') {
        cashboxField.style.display = 'none';
        bankField.style.display = 'block';
        document.querySelector('[name="cashbox_id"]').value = '';
    } else {
        cashboxField.style.display = 'none';
        bankField.style.display = 'none';
    }
});

// Trigger on page load if value exists
if (document.getElementById('payment_source_type').value) {
    document.getElementById('payment_source_type').dispatchEvent(new Event('change'));
}
</script>
@endsection
