@extends('layouts.admin')

@section('title', 'Banka Hesabı Düzenle')
@section('page-title', 'Banka Hesabı Düzenle')
@section('page-subtitle', $bankAccount->name)

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('accounting.cash.bank.update', $bankAccount) }}">
            @csrf
            @method('PUT')
            
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Şube</label>
                    <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                        <option value="">Seçiniz</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('branch_id', $bankAccount->branch_id) == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('branch_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Hesap Kodu</label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $bankAccount->code) }}" placeholder="Örn: BNK001">
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Hesap Adı <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $bankAccount->name) }}" required placeholder="Örn: Ana Hesap">
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Banka Adı <span class="text-danger">*</span></label>
                    <input type="text" name="bank_name" class="form-control @error('bank_name') is-invalid @enderror" value="{{ old('bank_name', $bankAccount->bank_name) }}" required placeholder="Örn: Ziraat Bankası">
                    @error('bank_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Banka Şubesi</label>
                    <input type="text" name="branch_name" class="form-control @error('branch_name') is-invalid @enderror" value="{{ old('branch_name', $bankAccount->branch_name) }}" placeholder="Örn: Kadıköy Şubesi">
                    @error('branch_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Hesap Numarası</label>
                    <input type="text" name="account_number" class="form-control @error('account_number') is-invalid @enderror" value="{{ old('account_number', $bankAccount->account_number) }}" placeholder="Örn: 1234567890">
                    @error('account_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">IBAN</label>
                    <input type="text" name="iban" class="form-control @error('iban') is-invalid @enderror" value="{{ old('iban', $bankAccount->iban) }}" placeholder="TR00 0000 0000 0000 0000 0000 00">
                    @error('iban')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Para Birimi <span class="text-danger">*</span></label>
                    <select name="currency" class="form-select @error('currency') is-invalid @enderror" required>
                        <option value="TRY" {{ old('currency', $bankAccount->currency) == 'TRY' ? 'selected' : '' }}>TRY</option>
                        <option value="USD" {{ old('currency', $bankAccount->currency) == 'USD' ? 'selected' : '' }}>USD</option>
                        <option value="EUR" {{ old('currency', $bankAccount->currency) == 'EUR' ? 'selected' : '' }}>EUR</option>
                    </select>
                    @error('currency')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Hesap Tipi <span class="text-danger">*</span></label>
                    <select name="account_type" class="form-select @error('account_type') is-invalid @enderror" required>
                        <option value="checking" {{ old('account_type', $bankAccount->account_type) == 'checking' ? 'selected' : '' }}>Vadesiz</option>
                        <option value="savings" {{ old('account_type', $bankAccount->account_type) == 'savings' ? 'selected' : '' }}>Vadeli</option>
                        <option value="credit" {{ old('account_type', $bankAccount->account_type) == 'credit' ? 'selected' : '' }}>Kredi</option>
                        <option value="pos" {{ old('account_type', $bankAccount->account_type) == 'pos' ? 'selected' : '' }}>POS</option>
                    </select>
                    @error('account_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Açılış Bakiyesi</label>
                    <input type="number" name="opening_balance" class="form-control @error('opening_balance') is-invalid @enderror" value="{{ old('opening_balance', $bankAccount->opening_balance) }}" step="0.01">
                    @error('opening_balance')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Açılış Bakiye Tarihi</label>
                    <input type="date" name="opening_balance_date" class="form-control @error('opening_balance_date') is-invalid @enderror" value="{{ old('opening_balance_date', $bankAccount->opening_balance_date?->format('Y-m-d')) }}">
                    @error('opening_balance_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-12">
                    <label class="form-label">Açıklama</label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2">{{ old('description', $bankAccount->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $bankAccount->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">
                            Aktif
                        </label>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_default" id="is_default" value="1" {{ old('is_default', $bankAccount->is_default) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_default">
                            Varsayılan Banka Hesabı
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-between">
                <a href="{{ route('accounting.cash.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Geri
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i>Güncelle
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
