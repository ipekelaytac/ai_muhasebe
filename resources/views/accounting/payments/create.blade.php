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
                        @php
                            $groupedParties = $parties->groupBy('type');
                        @endphp
                        @foreach($groupedParties as $type => $typeParties)
                            <optgroup label="{{ \App\Domain\Accounting\Enums\PartyType::getLabel($type) }}">
                                @foreach($typeParties as $party)
                                    <option value="{{ $party->id }}" {{ old('party_id', $partyId) == $party->id ? 'selected' : '' }}>
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
                    <label class="form-label">Ödeme Tarihi <span class="text-danger">*</span></label>
                    <input type="date" name="payment_date" class="form-control @error('payment_date') is-invalid @enderror" value="{{ old('payment_date', now()->toDateString()) }}" required>
                    @error('payment_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Tutar <span class="text-danger">*</span></label>
                    <input type="number" name="amount" id="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount', $suggestedAmount ?? '') }}" step="0.01" min="0.01" required>
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
            
            {{-- Document Selection for Allocation --}}
            @if(isset($openDocuments) && $openDocuments->count() > 0)
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="bi bi-file-earmark-text me-2"></i>
                            Bu Ödemeyi Hangi Belgeye Dağıtacaksınız?
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="40">Seç</th>
                                        <th>Belge No</th>
                                        <th>Belge Tarihi</th>
                                        <th>Tür</th>
                                        <th>Açıklama</th>
                                        <th class="text-end">Toplam</th>
                                        <th class="text-end">Ödenen</th>
                                        <th class="text-end">Kalan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($openDocuments as $doc)
                                        <tr>
                                            <td>
                                                <input type="radio" 
                                                       name="allocation_document_id" 
                                                       value="{{ $doc->id }}" 
                                                       class="form-check-input document-radio"
                                                       data-unpaid="{{ $doc->unpaid_amount }}"
                                                       {{ (isset($selectedDocument) && $selectedDocument && $selectedDocument->id == $doc->id) ? 'checked' : '' }}
                                                       required>
                                            </td>
                                            <td>
                                                <strong>{{ $doc->document_number ?? '-' }}</strong>
                                            </td>
                                            <td>{{ $doc->document_date->format('d.m.Y') }}</td>
                                            <td>
                                                <span class="badge bg-secondary">{{ \App\Domain\Accounting\Enums\DocumentType::getLabel($doc->type) }}</span>
                                            </td>
                                            <td>
                                                <small>{{ Str::limit($doc->description, 50) }}</small>
                                            </td>
                                            <td class="text-end">{{ number_format($doc->total_amount, 2) }} ₺</td>
                                            <td class="text-end text-success">{{ number_format($doc->paid_amount, 2) }} ₺</td>
                                            <td class="text-end fw-bold text-warning">{{ number_format($doc->unpaid_amount, 2) }} ₺</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <small class="text-muted mt-2 d-block">
                            <i class="bi bi-info-circle me-1"></i>
                            Ödeme kaydedildikten sonra seçili belgeye otomatik olarak dağıtılacaktır.
                        </small>
                    </div>
                </div>
            @elseif(isset($selectedDocument) && $selectedDocument)
                {{-- If a specific document is selected but not in openDocuments list, show it --}}
                <input type="hidden" name="allocation_document_id" value="{{ $selectedDocument->id }}">
                <div class="alert alert-info mt-4">
                    <h6 class="mb-2">
                        <i class="bi bi-file-earmark-check me-2"></i>
                        Seçili Belge
                    </h6>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $selectedDocument->document_number ?? '-' }}</strong>
                            <br>
                            <small class="text-muted">
                                {{ $selectedDocument->document_date->format('d.m.Y') }}
                                - {{ \App\Domain\Accounting\Enums\DocumentType::getLabel($selectedDocument->type) }}
                            </small>
                        </div>
                        <div class="text-end">
                            <strong>{{ number_format($selectedDocument->unpaid_amount, 2) }} ₺</strong>
                            <br>
                            <small class="text-muted">Kalan Tutar</small>
                        </div>
                    </div>
                </div>
            @endif
            
            {{-- Open Overtime Documents Info --}}
            @if(isset($openOvertimes) && $openOvertimes->count() > 0 && (!isset($openDocuments) || $openDocuments->count() == 0))
                <div class="alert alert-info mt-4">
                    <h6 class="mb-3">
                        <i class="bi bi-clock-history me-2"></i>
                        Açık Mesai Tahakkukları
                    </h6>
                    <div class="list-group">
                        @foreach($openOvertimes as $overtimeDoc)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $overtimeDoc->document_number }}</strong>
                                        <br>
                                        <small class="text-muted">
                                            {{ $overtimeDoc->document_date->format('d.m.Y') }}
                                            @if($overtimeDoc->description)
                                                - {{ $overtimeDoc->description }}
                                            @endif
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <strong>{{ number_format($overtimeDoc->unpaid_amount, 2) }} ₺</strong>
                                        <br>
                                        <small class="text-muted">Kalan</small>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <small class="text-muted mt-2 d-block">
                        <i class="bi bi-info-circle me-1"></i>
                        Bu mesai tahakkuklarına ödeme yapmak için yukarıdaki "Belge Seç" bölümünden ilgili belgeyi seçin.
                    </small>
                </div>
            @endif
            
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
    
    // Auto-fill amount when document is selected
    const documentRadios = document.querySelectorAll('.document-radio');
    const amountInput = document.getElementById('amount');
    
    documentRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                const unpaidAmount = parseFloat(this.dataset.unpaid);
                if (unpaidAmount > 0 && (!amountInput.value || amountInput.value == 0)) {
                    amountInput.value = unpaidAmount.toFixed(2);
                }
            }
        });
    });
    
    // If a document is pre-selected, trigger the change event
    const checkedRadio = document.querySelector('.document-radio:checked');
    if (checkedRadio) {
        checkedRadio.dispatchEvent(new Event('change'));
    }
</script>
@endsection
