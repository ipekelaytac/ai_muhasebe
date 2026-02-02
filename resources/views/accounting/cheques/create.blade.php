@extends('layouts.admin')

@section('title', 'Yeni Çek/Senet')
@section('page-title', 'Yeni Çek/Senet')
@section('page-subtitle', 'Yeni çek veya senet kaydet')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('accounting.cheques.store') }}">
            @csrf
            
            <div class="row g-3">
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
                    <label class="form-label">Tip <span class="text-danger">*</span></label>
                    <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                        <option value="">Seçiniz</option>
                        <option value="received" {{ old('type') == 'received' ? 'selected' : '' }}>Alınan Çek</option>
                        <option value="issued" {{ old('type') == 'issued' ? 'selected' : '' }}>Verilen Çek</option>
                    </select>
                    @error('type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Cari <span class="text-danger">*</span></label>
                    <select name="party_id" class="form-select @error('party_id') is-invalid @enderror" required>
                        <option value="">Seçiniz</option>
                        @php
                            $groupedParties = $parties->groupBy('type');
                        @endphp
                        @foreach($groupedParties as $type => $typeParties)
                            <optgroup label="{{ \App\Domain\Accounting\Enums\PartyType::getLabel($type) }}">
                                @foreach($typeParties as $party)
                                    <option value="{{ $party->id }}" {{ old('party_id') == $party->id ? 'selected' : '' }}>
                                        {{ $party->name }}@if($party->code) ({{ $party->code }})@endif
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @error('party_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Personeller de bu listede görünür</small>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Çek Numarası <span class="text-danger">*</span></label>
                    <input type="text" name="cheque_number" class="form-control @error('cheque_number') is-invalid @enderror" value="{{ old('cheque_number') }}" required>
                    @error('cheque_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Banka Adı <span class="text-danger">*</span></label>
                    <input type="text" name="bank_name" class="form-control @error('bank_name') is-invalid @enderror" value="{{ old('bank_name') }}" required>
                    @error('bank_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Banka Hesabı</label>
                    <select name="bank_account_id" class="form-select @error('bank_account_id') is-invalid @enderror">
                        <option value="">Seçiniz (Opsiyonel)</option>
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
                
                <div class="col-md-6">
                    <label class="form-label">Kesim Tarihi <span class="text-danger">*</span></label>
                    <input type="date" name="issue_date" class="form-control @error('issue_date') is-invalid @enderror" value="{{ old('issue_date', now()->toDateString()) }}" required>
                    @error('issue_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Vade Tarihi <span class="text-danger">*</span></label>
                    <input type="date" name="due_date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date') }}" required>
                    @error('due_date')
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
                
                <div class="col-md-12">
                    <label class="form-label">Notlar</label>
                    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes') }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-between">
                <a href="{{ route('accounting.cheques.index') }}" class="btn btn-secondary">
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
    // Auto-set due date based on issue date if not set
    document.querySelector('input[name="issue_date"]').addEventListener('change', function() {
        const dueDateInput = document.querySelector('input[name="due_date"]');
        if (!dueDateInput.value) {
            const issueDate = new Date(this.value);
            issueDate.setDate(issueDate.getDate() + 30); // Default 30 days
            dueDateInput.value = issueDate.toISOString().split('T')[0];
        }
    });
</script>
@endsection
