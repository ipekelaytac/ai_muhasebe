@extends('layouts.admin')

@section('title', 'Çek Düzenle')
@section('page-title', 'Çek Düzenle')
@section('page-subtitle', 'Çek bilgilerini güncelleyin')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.checks.update', $check) }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="branch_id" class="form-label">Şube <span class="text-danger">*</span></label>
                            <select name="branch_id" id="branch_id" required class="form-select">
                                <option value="">Seçiniz</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id', $check->branch_id) == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="customer_id" class="form-label">Cari <span class="text-danger">*</span></label>
                            <select name="customer_id" id="customer_id" required class="form-select">
                                <option value="">Seçiniz</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" {{ old('customer_id', $check->customer_id) == $customer->id ? 'selected' : '' }}>
                                        {{ $customer->name }} ({{ $customer->type == 'customer' ? 'Müşteri' : 'Tedarikçi' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="check_number" class="form-label">Çek Numarası <span class="text-danger">*</span></label>
                            <input type="text" name="check_number" id="check_number" value="{{ old('check_number', $check->check_number) }}" required
                                class="form-control" placeholder="Çek numarası">
                        </div>
                        <div class="col-md-6">
                            <label for="bank_name" class="form-label">Banka Adı <span class="text-danger">*</span></label>
                            <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name', $check->bank_name) }}" required
                                class="form-control" placeholder="Banka adı">
                        </div>
                        <div class="col-md-6">
                            <label for="amount" class="form-label">Tutar <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="amount" id="amount" step="0.01" min="0.01" 
                                    value="{{ old('amount', $check->amount) }}" required class="form-control" placeholder="0.00">
                                <span class="input-group-text">₺</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label">Durum <span class="text-danger">*</span></label>
                            <select name="status" id="status" required class="form-select">
                                <option value="pending" {{ old('status', $check->status) == 'pending' ? 'selected' : '' }}>Bekliyor</option>
                                <option value="cashed" {{ old('status', $check->status) == 'cashed' ? 'selected' : '' }}>Bozduruldu</option>
                                <option value="cancelled" {{ old('status', $check->status) == 'cancelled' ? 'selected' : '' }}>İptal</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="received_date" class="form-label">Geldiği Tarih <span class="text-danger">*</span></label>
                            <input type="date" name="received_date" id="received_date" 
                                value="{{ old('received_date', $check->received_date->format('Y-m-d')) }}" required class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="due_date" class="form-label">Vade Tarihi <span class="text-danger">*</span></label>
                            <input type="date" name="due_date" id="due_date" 
                                value="{{ old('due_date', $check->due_date->format('Y-m-d')) }}" required class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="cashed_date" class="form-label">Bozdurulma Tarihi</label>
                            <input type="date" name="cashed_date" id="cashed_date" 
                                value="{{ old('cashed_date', $check->cashed_date ? $check->cashed_date->format('Y-m-d') : '') }}" class="form-control">
                            <small class="text-muted">Sadece bozdurulduğunda doldurun</small>
                        </div>
                        <div class="col-md-12">
                            <label for="notes" class="form-label">Notlar</label>
                            <textarea name="notes" id="notes" rows="3" class="form-control" placeholder="Notlar">{{ old('notes', $check->notes) }}</textarea>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('admin.checks.index') }}" class="btn btn-secondary">
                            İptal
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>Güncelle
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const receivedDateInput = document.getElementById('received_date');
    const dueDateInput = document.getElementById('due_date');
    const cashedDateInput = document.getElementById('cashed_date');
    const statusSelect = document.getElementById('status');
    
    // Set due_date min to received_date
    receivedDateInput.addEventListener('change', function() {
        dueDateInput.min = this.value;
        if (dueDateInput.value && dueDateInput.value < this.value) {
            dueDateInput.value = this.value;
        }
    });
    
    // Show/hide cashed_date based on status
    function toggleCashedDate() {
        if (statusSelect.value === 'cashed') {
            cashedDateInput.closest('.col-md-6').style.display = 'block';
        } else {
            cashedDateInput.value = '';
            cashedDateInput.closest('.col-md-6').style.display = 'none';
        }
    }
    
    statusSelect.addEventListener('change', toggleCashedDate);
    toggleCashedDate();
});
</script>
@endsection

