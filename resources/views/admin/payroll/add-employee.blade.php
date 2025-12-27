@extends('layouts.admin')

@section('title', 'Bordroya Personel Ekle')
@section('page-title', 'Bordroya Personel Ekle')
@section('page-subtitle', $period->period_name . ' | ' . $period->company->name . ' - ' . $period->branch->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <a href="{{ route('admin.payroll.show', $period) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Geri
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                @if($availableEmployees->isEmpty())
                    <div class="alert alert-info text-center">
                        <i class="bi bi-info-circle fs-1 d-block mb-3"></i>
                        <p class="mb-0">Bu bordroya eklenebilecek yeni personel bulunmuyor.</p>
                        <p class="mb-0 mt-2">Tüm aktif personeller zaten bordroya eklenmiş.</p>
                    </div>
                @else
                    <form method="POST" action="{{ route('admin.payroll.add-employee', $period) }}">
                        @csrf
                        <div class="mb-3">
                            <label for="employee_id" class="form-label">Eklenecek Personel</label>
                            <select name="employee_id" id="employee_id" required class="form-select">
                                <option value="">Seçiniz</option>
                                @foreach($availableEmployees as $employee)
                                    <option value="{{ $employee->id }}" 
                                        data-company="{{ $employee->company->name }}"
                                        data-branch="{{ $employee->branch->name }}"
                                        {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                                        {{ $employee->full_name }} - {{ $employee->company->name }} / {{ $employee->branch->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div id="employeeInfo" class="mt-2 text-muted small" style="display: none;">
                                <span id="companyInfo"></span> / <span id="branchInfo"></span>
                            </div>
                            <small class="text-muted">Sadece bu bordroya eklenmemiş aktif personeller listelenmektedir.</small>
                        </div>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Uyarı:</strong> Seçilen personel için bu dönem için aktif sözleşme olmalıdır. 
                            Sözleşme yoksa personel bordroya eklenemez.
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.payroll.show', $period) }}" class="btn btn-secondary">
                                İptal
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-2"></i>Personeli Ekle
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

@if($availableEmployees->isNotEmpty())
<script>
document.addEventListener('DOMContentLoaded', function() {
    const employeeSelect = document.getElementById('employee_id');
    const employeeInfo = document.getElementById('employeeInfo');
    const companyInfo = document.getElementById('companyInfo');
    const branchInfo = document.getElementById('branchInfo');

    employeeSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            companyInfo.textContent = selectedOption.getAttribute('data-company');
            branchInfo.textContent = selectedOption.getAttribute('data-branch');
            employeeInfo.style.display = 'block';
        } else {
            employeeInfo.style.display = 'none';
        }
    });

    // Trigger on page load if there's a selected value
    if (employeeSelect.value) {
        employeeSelect.dispatchEvent(new Event('change'));
    }
});
</script>
@endif
@endsection

