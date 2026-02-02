@extends('layouts.admin')

@section('title', 'Mesai Girişi')
@section('page-title', 'Mesai Girişi')
@section('page-subtitle', 'Yeni mesai tahakkuku oluştur')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0">Yeni Mesai Girişi</h5>
        <small class="text-muted">Mesai tahakkuku oluşturun</small>
    </div>
    <a href="{{ route('accounting.overtime.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Geri
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('accounting.overtime.store') }}" id="overtimeForm">
            @csrf
            
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Personel <span class="text-danger">*</span></label>
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
                </div>
                
                <div class="col-md-4">
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
                
                <div class="col-md-4">
                    <label class="form-label">Belge Tarihi <span class="text-danger">*</span></label>
                    <input type="date" name="document_date" class="form-control @error('document_date') is-invalid @enderror" 
                           value="{{ old('document_date', now()->toDateString()) }}" required>
                    @error('document_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Mesai Tarihi <span class="text-danger">*</span></label>
                    <input type="date" name="overtime_date" class="form-control @error('overtime_date') is-invalid @enderror" 
                           value="{{ old('overtime_date', now()->toDateString()) }}" required>
                    @error('overtime_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Saat</label>
                    <input type="number" name="hours" step="0.5" min="0" 
                           class="form-control @error('hours') is-invalid @enderror" 
                           value="{{ old('hours') }}" placeholder="Örn: 2.5" id="hours_input">
                    @error('hours')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Mesai saati (opsiyonel)</small>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Saatlik Ücret</label>
                    <div class="input-group">
                        <input type="number" name="rate" step="0.01" min="0" 
                               class="form-control @error('rate') is-invalid @enderror" 
                               value="{{ old('rate') }}" placeholder="0.00" id="rate_input" readonly>
                        <span class="input-group-text">₺</span>
                    </div>
                    @error('rate')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted" id="rate_info">Personel seçildiğinde otomatik hesaplanır</small>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Toplam Tutar <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" name="total_amount" step="0.01" min="0.01" 
                               class="form-control @error('total_amount') is-invalid @enderror" 
                               value="{{ old('total_amount') }}" required id="total_amount_input">
                        <span class="input-group-text">₺</span>
                    </div>
                    @error('total_amount')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Vade Tarihi</label>
                    <input type="date" name="due_date" class="form-control @error('due_date') is-invalid @enderror" 
                           value="{{ old('due_date') }}">
                    @error('due_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Açıklama</label>
                    <input type="text" name="description" class="form-control @error('description') is-invalid @enderror" 
                           value="{{ old('description') }}" placeholder="Örn: Hafta sonu mesai">
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="row g-3 mb-4">
                <div class="col-md-12">
                    <label class="form-label">Notlar</label>
                    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" 
                              placeholder="Ek notlar...">{{ old('notes') }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-between">
                <a href="{{ route('accounting.overtime.index') }}" class="btn btn-secondary">
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
document.addEventListener('DOMContentLoaded', function() {
    const partySelect = document.getElementById('party_id');
    const hoursInput = document.getElementById('hours_input');
    const rateInput = document.getElementById('rate_input');
    const totalInput = document.getElementById('total_amount_input');
    const rateInfo = document.getElementById('rate_info');
    
    let hourlyOvertimeRate = 0;
    
    // Load contract info when party is selected
    partySelect.addEventListener('change', function() {
        const partyId = this.value;
        
        if (!partyId) {
            rateInput.value = '';
            rateInput.setAttribute('readonly', true);
            rateInfo.textContent = 'Personel seçildiğinde otomatik hesaplanır';
            rateInfo.className = 'text-muted';
            hourlyOvertimeRate = 0;
            calculateTotal();
            return;
        }
        
        // Show loading state
        rateInfo.textContent = 'Sözleşme bilgisi yükleniyor...';
        rateInfo.className = 'text-info';
        rateInput.value = '';
        
        // Fetch contract info via AJAX
        const url = `{{ route('accounting.overtime.contract-info') }}?party_id=${partyId}`;
        console.log('Fetching contract info from:', url);
        
        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin'
        })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    return response.json().then(data => {
                        console.error('Error response:', data);
                        throw new Error(data.error || 'HTTP ' + response.status);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Contract info received:', data);
                if (data.error) {
                    rateInfo.textContent = data.error;
                    rateInfo.className = 'text-danger';
                    rateInput.value = '';
                    hourlyOvertimeRate = 0;
                } else {
                    hourlyOvertimeRate = parseFloat(data.hourly_overtime_rate) || 0;
                    rateInput.value = hourlyOvertimeRate.toFixed(2);
                    rateInput.setAttribute('readonly', true);
                    rateInfo.textContent = `Aylık maaş: ${parseFloat(data.monthly_salary).toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2})} ₺ | Saatlik mesai ücreti: ${hourlyOvertimeRate.toFixed(2)} ₺`;
                    rateInfo.className = 'text-muted';
                    // Auto-calculate if hours already entered
                    calculateTotal();
                }
            })
            .catch(error => {
                console.error('Error fetching contract info:', error);
                rateInfo.textContent = 'Sözleşme bilgisi alınamadı: ' + error.message;
                rateInfo.className = 'text-danger';
                rateInput.value = '';
                hourlyOvertimeRate = 0;
            });
    });
    
    // Calculate total when hours change
    function calculateTotal() {
        const hours = parseFloat(hoursInput.value) || 0;
        const rate = hourlyOvertimeRate || parseFloat(rateInput.value) || 0;
        
        if (hours > 0 && rate > 0) {
            const calculated = hours * rate;
            // Auto-update total
            totalInput.value = calculated.toFixed(2);
        } else if (hours == 0) {
            // Clear total if hours is 0
            if (!totalInput.value || parseFloat(totalInput.value) == 0) {
                totalInput.value = '';
            }
        }
    }
    
    if (hoursInput && rateInput && totalInput) {
        hoursInput.addEventListener('input', calculateTotal);
        
        // Also allow manual rate entry (if needed)
        rateInput.addEventListener('input', function() {
            if (!this.readOnly) {
                hourlyOvertimeRate = parseFloat(this.value) || 0;
                calculateTotal();
            }
        });
    }
    
    // If party is pre-selected, trigger change event
    @if($partyId)
        partySelect.dispatchEvent(new Event('change'));
    @endif
});
</script>
@endsection
