@extends('layouts.admin')

@section('title', 'Virman')
@section('page-title', 'Virman')
@section('page-subtitle', 'Kasa ve banka arası transfer')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('accounting.cash.transfer.store') }}" id="transferForm">
            @csrf
            
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Kaynak <span class="text-danger">*</span></label>
                    <select name="from_type" id="from_type" class="form-select @error('from_type') is-invalid @enderror" required>
                        <option value="">Seçiniz</option>
                        <option value="cashbox" {{ old('from_type') == 'cashbox' ? 'selected' : '' }}>Kasa</option>
                        <option value="bank" {{ old('from_type') == 'bank' ? 'selected' : '' }}>Banka</option>
                    </select>
                    @error('from_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6" id="from_cashbox_field" style="display: none;">
                    <label class="form-label">Kasa <span class="text-danger">*</span></label>
                    <select name="from_cashbox_id" class="form-select @error('from_cashbox_id') is-invalid @enderror">
                        <option value="">Seçiniz</option>
                        @foreach($cashboxes as $cashbox)
                            <option value="{{ $cashbox->id }}" {{ old('from_cashbox_id') == $cashbox->id ? 'selected' : '' }}>
                                {{ $cashbox->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('from_cashbox_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6" id="from_bank_field" style="display: none;">
                    <label class="form-label">Banka <span class="text-danger">*</span></label>
                    <select name="from_bank_account_id" class="form-select @error('from_bank_account_id') is-invalid @enderror">
                        <option value="">Seçiniz</option>
                        @foreach($bankAccounts as $bank)
                            <option value="{{ $bank->id }}" {{ old('from_bank_account_id') == $bank->id ? 'selected' : '' }}>
                                {{ $bank->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('from_bank_account_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Hedef <span class="text-danger">*</span></label>
                    <select name="to_type" id="to_type" class="form-select @error('to_type') is-invalid @enderror" required>
                        <option value="">Seçiniz</option>
                        <option value="cashbox" {{ old('to_type') == 'cashbox' ? 'selected' : '' }}>Kasa</option>
                        <option value="bank" {{ old('to_type') == 'bank' ? 'selected' : '' }}>Banka</option>
                    </select>
                    @error('to_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6" id="to_cashbox_field" style="display: none;">
                    <label class="form-label">Hedef Kasa <span class="text-danger">*</span></label>
                    <select name="to_cashbox_id" class="form-select @error('to_cashbox_id') is-invalid @enderror">
                        <option value="">Seçiniz</option>
                        @foreach($cashboxes as $cashbox)
                            <option value="{{ $cashbox->id }}" {{ old('to_cashbox_id') == $cashbox->id ? 'selected' : '' }}>
                                {{ $cashbox->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('to_cashbox_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6" id="to_bank_field" style="display: none;">
                    <label class="form-label">Hedef Banka <span class="text-danger">*</span></label>
                    <select name="to_bank_account_id" class="form-select @error('to_bank_account_id') is-invalid @enderror">
                        <option value="">Seçiniz</option>
                        @foreach($bankAccounts as $bank)
                            <option value="{{ $bank->id }}" {{ old('to_bank_account_id') == $bank->id ? 'selected' : '' }}>
                                {{ $bank->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('to_bank_account_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Tutar <span class="text-danger">*</span></label>
                    <input type="number" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" step="0.01" min="0.01" required>
                    @error('amount')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Transfer Tarihi <span class="text-danger">*</span></label>
                    <input type="date" name="transfer_date" class="form-control @error('transfer_date') is-invalid @enderror" value="{{ old('transfer_date', now()->toDateString()) }}" required>
                    @error('transfer_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-12">
                    <label class="form-label">Açıklama</label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-between">
                <a href="{{ route('accounting.cash.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Geri
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i>Virman Yap
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const fromTypeSelect = document.getElementById('from_type');
    const toTypeSelect = document.getElementById('to_type');
    const fromCashboxField = document.getElementById('from_cashbox_field');
    const fromBankField = document.getElementById('from_bank_field');
    const toCashboxField = document.getElementById('to_cashbox_field');
    const toBankField = document.getElementById('to_bank_field');
    
    function updateFields() {
        // Hide all fields first and disable them
        fromCashboxField.style.display = 'none';
        fromCashboxField.querySelector('select').disabled = true;
        fromCashboxField.querySelector('select').value = '';
        
        fromBankField.style.display = 'none';
        fromBankField.querySelector('select').disabled = true;
        fromBankField.querySelector('select').value = '';
        
        toCashboxField.style.display = 'none';
        toCashboxField.querySelector('select').disabled = true;
        toCashboxField.querySelector('select').value = '';
        
        toBankField.style.display = 'none';
        toBankField.querySelector('select').disabled = true;
        toBankField.querySelector('select').value = '';
        
        // Show relevant fields and enable them
        if (fromTypeSelect.value === 'cashbox') {
            fromCashboxField.style.display = 'block';
            fromCashboxField.querySelector('select').disabled = false;
        } else if (fromTypeSelect.value === 'bank') {
            fromBankField.style.display = 'block';
            fromBankField.querySelector('select').disabled = false;
        }
        
        if (toTypeSelect.value === 'cashbox') {
            toCashboxField.style.display = 'block';
            toCashboxField.querySelector('select').disabled = false;
        } else if (toTypeSelect.value === 'bank') {
            toBankField.style.display = 'block';
            toBankField.querySelector('select').disabled = false;
        }
    }
    
    fromTypeSelect.addEventListener('change', updateFields);
    toTypeSelect.addEventListener('change', updateFields);
    updateFields(); // Initial call
</script>
@endsection
