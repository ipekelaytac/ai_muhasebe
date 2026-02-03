@extends('layouts.admin')

@section('title', 'Çalışan Borcu Ekle')
@section('page-title', 'Çalışan Borcu Ekle')
@section('page-subtitle', 'Yeni çalışan borcu oluştur')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('accounting.employees.debts.store') }}" id="debtForm">
            @csrf
            
            @if(request('payroll_item_id'))
                <input type="hidden" name="redirect_to" value="payroll">
                <input type="hidden" name="payroll_item_id" value="{{ request('payroll_item_id') }}">
            @endif
            
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
                    <label class="form-label">Çalışan <span class="text-danger">*</span></label>
                    <select name="party_id" id="party_id" class="form-select @error('party_id') is-invalid @enderror" required>
                        <option value="">Seçiniz</option>
                        @foreach($parties as $party)
                            <option value="{{ $party->id }}" {{ old('party_id', $partyId) == $party->id ? 'selected' : '' }}>
                                {{ $party->name }}@if($party->code) ({{ $party->code }})@endif
                            </option>
                        @endforeach
                    </select>
                    @error('party_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Borçlu olan çalışanı seçin</small>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Borç Tarihi <span class="text-danger">*</span></label>
                    <input type="date" name="document_date" class="form-control @error('document_date') is-invalid @enderror" value="{{ old('document_date', now()->toDateString()) }}" required>
                    @error('document_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Vade Tarihi</label>
                    <input type="date" name="due_date" id="due_date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date') }}">
                    @error('due_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Boş bırakılırsa borç tarihi kullanılır</small>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Borç Tutarı <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" name="total_amount" id="total_amount" class="form-control @error('total_amount') is-invalid @enderror" value="{{ old('total_amount') }}" step="0.01" min="0.01" required>
                        <span class="input-group-text">₺</span>
                    </div>
                    @error('total_amount')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-12">
                    <label class="form-label">Açıklama</label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="Borç nedeni, açıklama...">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-12">
                    <label class="form-label">Notlar</label>
                    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2" placeholder="Ek notlar...">{{ old('notes') }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Bilgi:</strong> Bu borç, muhasebe sisteminde "Gider Tahakkuku" (expense_due) belgesi olarak kaydedilecektir. 
                Borç ödemeleri bordro detay sayfasından veya ödeme/tahsilat menüsünden yapılabilir.
            </div>
            
            <div class="mt-4 d-flex justify-content-between">
                @if(request('payroll_item_id'))
                    <a href="{{ route('admin.payroll.item', request('payroll_item_id')) }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Geri
                    </a>
                @else
                    <a href="{{ route('accounting.documents.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Geri
                    </a>
                @endif
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-check-circle me-1"></i>Borç Oluştur
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Auto-set due date based on document date if not set
    document.getElementById('document_date')?.addEventListener('change', function() {
        const dueDateInput = document.getElementById('due_date');
        if (dueDateInput && !dueDateInput.value) {
            dueDateInput.value = this.value;
        }
    });
</script>
@endsection
