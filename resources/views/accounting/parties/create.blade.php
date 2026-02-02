@extends('layouts.admin')

@section('title', 'Yeni Cari')
@section('page-title', 'Yeni Cari')
@section('page-subtitle', 'Yeni cari hesap oluştur')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('accounting.parties.store') }}">
            @csrf
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Şube <span class="text-danger">*</span></label>
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
                    <label class="form-label">Tip <span class="text-danger">*</span></label>
                    <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                        <option value="">Seçiniz</option>
                        <option value="customer" {{ old('type') == 'customer' ? 'selected' : '' }}>Müşteri</option>
                        <option value="supplier" {{ old('type') == 'supplier' ? 'selected' : '' }}>Tedarikçi</option>
                        <option value="employee" {{ old('type') == 'employee' ? 'selected' : '' }}>Çalışan</option>
                        <option value="other" {{ old('type') == 'other' ? 'selected' : '' }}>Diğer</option>
                        <option value="tax_authority" {{ old('type') == 'tax_authority' ? 'selected' : '' }}>Vergi Dairesi</option>
                        <option value="bank" {{ old('type') == 'bank' ? 'selected' : '' }}>Banka</option>
                    </select>
                    @error('type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Kod</label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" placeholder="Otomatik oluşturulacak">
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Boş bırakılırsa otomatik oluşturulur</small>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">İsim <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Vergi Numarası</label>
                    <input type="text" name="tax_number" class="form-control @error('tax_number') is-invalid @enderror" value="{{ old('tax_number') }}">
                    @error('tax_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Vergi Dairesi</label>
                    <input type="text" name="tax_office" class="form-control @error('tax_office') is-invalid @enderror" value="{{ old('tax_office') }}">
                    @error('tax_office')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Telefon</label>
                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}">
                    @error('phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">E-posta</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-12">
                    <label class="form-label">Adres</label>
                    <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address') }}</textarea>
                    @error('address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Şehir</label>
                    <input type="text" name="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city') }}">
                    @error('city')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Ülke</label>
                    <input type="text" name="country" class="form-control @error('country') is-invalid @enderror" value="{{ old('country', 'Türkiye') }}">
                    @error('country')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Ödeme Vadesi (Gün)</label>
                    <input type="number" name="payment_terms_days" class="form-control @error('payment_terms_days') is-invalid @enderror" value="{{ old('payment_terms_days', 0) }}" min="0" placeholder="0">
                    @error('payment_terms_days')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Kredi Limiti</label>
                    <input type="number" name="credit_limit" class="form-control @error('credit_limit') is-invalid @enderror" value="{{ old('credit_limit') }}" step="0.01" min="0">
                    @error('credit_limit')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Durum</label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Aktif</label>
                    </div>
                </div>
                
                <div class="col-md-12">
                    <label class="form-label">Notlar</label>
                    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes') }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-between">
                <a href="{{ route('accounting.parties.index') }}" class="btn btn-secondary">
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
