@extends('layouts.admin')

@section('title', 'Yeni Tahakkuk')
@section('page-title', 'Yeni Tahakkuk')
@section('page-subtitle', 'Yeni belge oluştur')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('accounting.documents.store') }}" id="documentForm">
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
                    <label class="form-label">Belge Tipi <span class="text-danger">*</span></label>
                    <select name="type" id="document_type" class="form-select @error('type') is-invalid @enderror" required>
                        <option value="">Seçiniz</option>
                        <optgroup label="Alacak Belgeleri">
                            <option value="customer_invoice" {{ old('type') == 'customer_invoice' ? 'selected' : '' }}>Satış Faturası</option>
                            <option value="income_due" {{ old('type') == 'income_due' ? 'selected' : '' }}>Gelir Tahakkuku</option>
                            <option value="advance_given" {{ old('type') == 'advance_given' ? 'selected' : '' }}>Verilen Avans</option>
                            <option value="cheque_receivable" {{ old('type') == 'cheque_receivable' ? 'selected' : '' }}>Alınan Çek</option>
                            <option value="adjustment_credit" {{ old('type') == 'adjustment_credit' ? 'selected' : '' }}>Alacak Düzeltme</option>
                        </optgroup>
                        <optgroup label="Borç Belgeleri">
                            <option value="supplier_invoice" {{ old('type') == 'supplier_invoice' ? 'selected' : '' }}>Alım Faturası</option>
                            <option value="expense_due" {{ old('type') == 'expense_due' ? 'selected' : '' }}>Gider Tahakkuku</option>
                            <option value="payroll_due" {{ old('type') == 'payroll_due' ? 'selected' : '' }}>Maaş Tahakkuku</option>
                            <option value="overtime_due" {{ old('type') == 'overtime_due' ? 'selected' : '' }}>Mesai Tahakkuku</option>
                            <option value="meal_due" {{ old('type') == 'meal_due' ? 'selected' : '' }}>Yemek Parası Tahakkuku</option>
                            <option value="advance_received" {{ old('type') == 'advance_received' ? 'selected' : '' }}>Alınan Avans</option>
                            <option value="cheque_payable" {{ old('type') == 'cheque_payable' ? 'selected' : '' }}>Verilen Çek</option>
                            <option value="adjustment_debit" {{ old('type') == 'adjustment_debit' ? 'selected' : '' }}>Borç Düzeltme</option>
                        </optgroup>
                    </select>
                    @error('type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <script>
                // Filter party dropdown based on document type
                document.addEventListener('DOMContentLoaded', function() {
                    const documentTypeSelect = document.getElementById('document_type');
                    const partySelect = document.getElementById('party_id');
                    const employeeTypes = @json(\App\Domain\Accounting\Enums\DocumentType::EMPLOYEE_TYPES);
                    
                    function filterParties() {
                        const selectedType = documentTypeSelect.value;
                        const optgroups = partySelect.querySelectorAll('optgroup');
                        const options = partySelect.querySelectorAll('option[data-party-type]');
                        
                        if (!selectedType) {
                            // Show all parties
                            optgroups.forEach(opt => opt.style.display = '');
                            options.forEach(opt => opt.style.display = '');
                            return;
                        }
                        
                        const isEmployeeType = employeeTypes.includes(selectedType);
                        
                        optgroups.forEach(optgroup => {
                            const partyType = optgroup.getAttribute('data-party-type');
                            if (isEmployeeType) {
                                // For employee types, show all but highlight employee parties
                                optgroup.style.display = '';
                                if (partyType === 'employee') {
                                    optgroup.style.fontWeight = 'bold';
                                }
                            } else {
                                // For non-employee types, hide employee optgroup by default
                                if (partyType === 'employee') {
                                    optgroup.style.display = 'none';
                                } else {
                                    optgroup.style.display = '';
                                }
                            }
                        });
                    }
                    
                    documentTypeSelect.addEventListener('change', filterParties);
                    filterParties(); // Run on page load
                });
                </script>
                
                <div class="col-md-6">
                    <label class="form-label">Cari <span class="text-danger">*</span></label>
                    <select name="party_id" id="party_id" class="form-select @error('party_id') is-invalid @enderror" required>
                        <option value="">Seçiniz</option>
                        @php
                            $groupedParties = $parties->groupBy('type');
                        @endphp
                        @foreach($groupedParties as $type => $typeParties)
                            <optgroup label="{{ \App\Domain\Accounting\Enums\PartyType::getLabel($type) }}" data-party-type="{{ $type }}">
                                @foreach($typeParties as $party)
                                    <option value="{{ $party->id }}" data-party-type="{{ $type }}" {{ old('party_id', $partyId) == $party->id ? 'selected' : '' }}>
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
                
                <div class="col-md-3">
                    <label class="form-label">Belge Tarihi <span class="text-danger">*</span></label>
                    <input type="date" name="document_date" class="form-control @error('document_date') is-invalid @enderror" value="{{ old('document_date', now()->toDateString()) }}" required>
                    @error('document_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Vade Tarihi</label>
                    <input type="date" name="due_date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date') }}">
                    @error('due_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Referans No</label>
                    <input type="text" name="reference_number" class="form-control @error('reference_number') is-invalid @enderror" value="{{ old('reference_number') }}" placeholder="Fatura no, vb.">
                    @error('reference_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Kategori</label>
                    <select name="category_id" class="form-select @error('category_id') is-invalid @enderror">
                        <option value="">Kategorisiz</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Toplam Tutar <span class="text-danger">*</span></label>
                    <input type="number" name="total_amount" id="total_amount" class="form-control @error('total_amount') is-invalid @enderror" value="{{ old('total_amount') }}" step="0.01" min="0.01" required>
                    @error('total_amount')
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
                <a href="{{ route('accounting.documents.index') }}" class="btn btn-secondary">
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
    // Auto-set due date based on document date if not set
    document.getElementById('document_date').addEventListener('change', function() {
        const dueDateInput = document.querySelector('input[name="due_date"]');
        if (!dueDateInput.value) {
            dueDateInput.value = this.value;
        }
    });
</script>
@endsection
