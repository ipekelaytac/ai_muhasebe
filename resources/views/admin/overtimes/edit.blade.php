@extends('layouts.admin')

@section('title', 'Mesai Düzenle')
@section('page-title', 'Mesai Düzenle')
@section('page-subtitle', 'Mesai bilgilerini güncelleyin')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.overtimes.update', $overtime) }}" id="overtimeForm">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="branch_id" class="form-label">Şube <span class="text-danger">*</span></label>
                            <select name="branch_id" id="branch_id" required class="form-select">
                                <option value="">Seçiniz</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id', $overtime->branch_id) == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="employee_id" class="form-label">Personel <span class="text-danger">*</span></label>
                            <select name="employee_id" id="employee_id" required class="form-select">
                                <option value="">Seçiniz</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}" {{ old('employee_id', $overtime->employee_id) == $employee->id ? 'selected' : '' }}>
                                        {{ $employee->full_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="overtime_date" class="form-label">Mesai Tarihi <span class="text-danger">*</span></label>
                            <input type="date" name="overtime_date" id="overtime_date" 
                                value="{{ old('overtime_date', $overtime->overtime_date->format('Y-m-d')) }}" required class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label for="start_time" class="form-label">Başlangıç Saati <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" id="start_time" 
                                value="{{ old('start_time', date('H:i', strtotime($overtime->start_time))) }}" required class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label for="end_time" class="form-label">Bitiş Saati <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" id="end_time" 
                                value="{{ old('end_time', date('H:i', strtotime($overtime->end_time))) }}" required class="form-control">
                        </div>
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Bilgi:</strong> Mesai ücreti normal saatlik ücretin 1.5 katı olarak hesaplanacaktır.
                                <br>
                                <strong>Mevcut:</strong> {{ number_format($overtime->hours, 2) }} saat × {{ number_format($overtime->rate, 2) }} ₺ = {{ number_format($overtime->amount, 2) }} ₺
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label for="notes" class="form-label">Notlar</label>
                            <textarea name="notes" id="notes" rows="3" class="form-control" placeholder="Notlar">{{ old('notes', $overtime->notes) }}</textarea>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('admin.overtimes.index') }}" class="btn btn-secondary">
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
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    
    // Validate end time is after start time
    function validateTimes() {
        if (startTimeInput.value && endTimeInput.value) {
            const start = new Date('2000-01-01 ' + startTimeInput.value);
            const end = new Date('2000-01-01 ' + endTimeInput.value);
            
            // If end is before start, it means it's next day (overnight)
            if (end < start) {
                // This is allowed for overnight shifts
                return true;
            }
            
            if (end <= start) {
                endTimeInput.setCustomValidity('Bitiş saati başlangıç saatinden sonra olmalıdır.');
                return false;
            }
        }
        endTimeInput.setCustomValidity('');
        return true;
    }
    
    startTimeInput.addEventListener('change', validateTimes);
    endTimeInput.addEventListener('change', validateTimes);
});
</script>
@endsection

