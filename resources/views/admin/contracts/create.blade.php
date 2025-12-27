@extends('layouts.admin')

@section('title', 'Yeni Sözleşme')
@section('page-title', 'Yeni Sözleşme')
@section('page-subtitle', 'Yeni bir sözleşme ekleyin')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.contracts.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="employee_id" class="form-label">Çalışan</label>
                        <select name="employee_id" id="employee_id" required class="form-select">
                            <option value="">Seçiniz</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" 
                                    data-company="{{ $employee->company->name }}"
                                    data-branch="{{ $employee->branch->name }}"
                                    {{ old('employee_id', request('employee_id')) == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->full_name }} - {{ $employee->company->name }} / {{ $employee->branch->name }}
                                </option>
                            @endforeach
                        </select>
                        <div id="employeeInfo" class="mt-2 text-muted small" style="display: none;">
                            <span id="companyInfo"></span> / <span id="branchInfo"></span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="effective_from" class="form-label">Başlangıç Tarihi</label>
                            <input type="date" name="effective_from" id="effective_from" value="{{ old('effective_from') }}" required
                                class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="effective_to" class="form-label">Bitiş Tarihi (Opsiyonel)</label>
                            <input type="date" name="effective_to" id="effective_to" value="{{ old('effective_to') }}"
                                class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="monthly_net_salary" class="form-label">Aylık Net Maaş</label>
                        <input type="number" step="0.01" name="monthly_net_salary" id="monthly_net_salary" value="{{ old('monthly_net_salary') }}" required
                            class="form-control">
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="pay_day_1" class="form-label">1. Ödeme Günü</label>
                            <input type="number" min="1" max="31" name="pay_day_1" id="pay_day_1" value="{{ old('pay_day_1', 5) }}" required
                                class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="pay_amount_1" class="form-label">1. Ödeme Tutarı</label>
                            <input type="number" step="0.01" name="pay_amount_1" id="pay_amount_1" value="{{ old('pay_amount_1') }}" required
                                class="form-control">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="pay_day_2" class="form-label">2. Ödeme Günü</label>
                            <input type="number" min="1" max="31" name="pay_day_2" id="pay_day_2" value="{{ old('pay_day_2', 20) }}" required
                                class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="pay_amount_2" class="form-label">2. Ödeme Tutarı</label>
                            <input type="number" step="0.01" name="pay_amount_2" id="pay_amount_2" value="{{ old('pay_amount_2') }}" required
                                class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="meal_allowance" class="form-label">Yemek Yardımı</label>
                        <input type="number" step="0.01" name="meal_allowance" id="meal_allowance" value="{{ old('meal_allowance', 0) }}"
                            class="form-control">
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.contracts.index') }}" class="btn btn-secondary">
                            İptal
                        </a>
                        <button type="submit" class="btn btn-primary">
                            Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

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
@endsection
