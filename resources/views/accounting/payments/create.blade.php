@extends('layouts.admin')

@section('title', 'Yeni Ödeme/Tahsilat')
@section('page-title', 'Yeni Ödeme/Tahsilat')
@section('page-subtitle', 'Yeni ödeme veya tahsilat kaydet')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('accounting.payments.store') }}" id="paymentForm">
            @csrf
            
            <div class="row g-3 mb-4">
                <div class="col-md-6">
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
                
                <div class="col-md-6">
                    <label class="form-label">Ödeme Tipi <span class="text-danger">*</span></label>
                    <select name="type" id="payment_type" class="form-select @error('type') is-invalid @enderror" required>
                        <option value="">Seçiniz</option>
                        <optgroup label="Girişler">
                            <option value="cash_in" {{ old('type') == 'cash_in' ? 'selected' : '' }}>Kasa Girişi</option>
                            <option value="bank_in" {{ old('type') == 'bank_in' ? 'selected' : '' }}>Banka Girişi</option>
                            <option value="pos_in" {{ old('type') == 'pos_in' ? 'selected' : '' }}>POS Tahsilat</option>
                            <option value="cheque_in" {{ old('type') == 'cheque_in' ? 'selected' : '' }}>Çek Tahsilat</option>
                        </optgroup>
                        <optgroup label="Çıkışlar">
                            <option value="cash_out" {{ old('type') == 'cash_out' ? 'selected' : '' }}>Kasa Çıkışı</option>
                            <option value="bank_out" {{ old('type') == 'bank_out' ? 'selected' : '' }}>Banka Çıkışı</option>
                            <option value="cheque_out" {{ old('type') == 'cheque_out' ? 'selected' : '' }}>Çek Ödeme</option>
                        </optgroup>
                        <optgroup label="Transferler">
                            <option value="transfer" {{ old('type') == 'transfer' ? 'selected' : '' }}>Virman</option>
                            <option value="bank_transfer" {{ old('type') == 'bank_transfer' ? 'selected' : '' }}>Havale/EFT</option>
                        </optgroup>
                    </select>
                    @error('type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Cari</label>
                    <select name="party_id" id="party_id" class="form-select @error('party_id') is-invalid @enderror">
                        <option value="">Seçiniz (Opsiyonel)</option>
                        @foreach($parties as $party)
                            <option value="{{ $party->id }}" {{ old('party_id', $partyId) == $party->id ? 'selected' : '' }}>
                                {{ $party->name }} ({{ $party->type_label }})
                            </option>
                        @endforeach
                    </select>
                    @error('party_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Ödeme Tarihi <span class="text-danger">*</span></label>
                    <input type="date" name="payment_date" class="form-control @error('payment_date') is-invalid @enderror" value="{{ old('payment_date', now()->toDateString()) }}" required>
                    @error('payment_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Tutar <span class="text-danger">*</span></label>
                    <input type="number" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" step="0.01" min="0.01" required>
                    @error('amount')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6" id="cashbox_field" style="display: none;">
                    <label class="form-label">Kasa <span class="text-danger">*</span></label>
                    <select name="cashbox_id" class="form-select @error('cashbox_id') is-invalid @enderror">
                        <option value="">Seçiniz</option>
                        @foreach($cashboxes as $cashbox)
                            <option value="{{ $cashbox->id }}" {{ old('cashbox_id') == $cashbox->id ? 'selected' : '' }}>
                                {{ $cashbox->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('cashbox_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6" id="bank_field" style="display: none;">
                    <label class="form-label">Banka <span class="text-danger">*</span></label>
                    <select name="bank_account_id" class="form-select @error('bank_account_id') is-invalid @enderror">
                        <option value="">Seçiniz</option>
                        @foreach($bankAccounts as $bank)
                            <option value="{{ $bank->id }}" {{ old('bank_account_id') == $bank->id ? 'selected' : '' }}>
                                {{ $bank->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('bank_account_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6" id="to_cashbox_field" style="display: none;">
                    <label class="form-label">Hedef Kasa</label>
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
                    <label class="form-label">Hedef Banka</label>
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
                    <label class="form-label">Komisyon/Ücret</label>
                    <input type="number" name="fee_amount" class="form-control @error('fee_amount') is-invalid @enderror" value="{{ old('fee_amount') }}" step="0.01" min="0">
                    @error('fee_amount')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Referans No</label>
                    <input type="text" name="reference_number" class="form-control @error('reference_number') is-invalid @enderror" value="{{ old('reference_number') }}" placeholder="Makbuz no, vb.">
                    @error('reference_number')
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
                
                <div class="col-md-12">
                    <label class="form-label">Notlar</label>
                    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2">{{ old('notes') }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-between">
                <a href="{{ route('accounting.payments.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Geri
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i>Kaydet
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const paymentTypeSelect = document.getElementById('payment_type');
    const cashboxField = document.getElementById('cashbox_field');
    const bankField = document.getElementById('bank_field');
    const toCashboxField = document.getElementById('to_cashbox_field');
    const toBankField = document.getElementById('to_bank_field');
    
    function updateFields() {
        const type = paymentTypeSelect.value;
        const cashTypes = ['cash_in', 'cash_out'];
        const bankTypes = ['bank_in', 'bank_out', 'pos_in', 'cheque_in', 'cheque_out', 'bank_transfer'];
        const transferTypes = ['transfer'];
        
        // Hide all fields first
        cashboxField.style.display = 'none';
        bankField.style.display = 'none';
        toCashboxField.style.display = 'none';
        toBankField.style.display = 'none';
        
        // Show relevant fields
        if (cashTypes.includes(type)) {
            cashboxField.style.display = 'block';
        } else if (bankTypes.includes(type)) {
            bankField.style.display = 'block';
        } else if (transferTypes.includes(type)) {
            cashboxField.style.display = 'block';
            bankField.style.display = 'block';
            toCashboxField.style.display = 'block';
            toBankField.style.display = 'block';
        }
    }
    
    paymentTypeSelect.addEventListener('change', updateFields);
    updateFields(); // Initial call
</script>
@endsection
