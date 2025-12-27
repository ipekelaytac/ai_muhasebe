@extends('layouts.admin')

@section('title', 'Yeni Avans')
@section('page-title', 'Yeni Avans')
@section('page-subtitle', 'Yeni bir avans ekleyin')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.advances.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="employee_id" class="form-label">Çalışan</label>
                        <select name="employee_id" id="employee_id" required class="form-select">
                            <option value="">Seçiniz</option>
                            @foreach($employees as $employee)
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
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="advance_date" class="form-label">Tarih</label>
                            <input type="date" name="advance_date" id="advance_date" value="{{ old('advance_date', now()->toDateString()) }}" required
                                class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="amount" class="form-label">Tutar</label>
                            <input type="number" step="0.01" name="amount" id="amount" value="{{ old('amount') }}" required
                                class="form-control" min="0.01">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="method" class="form-label">Ödeme Yöntemi</label>
                        <select name="method" id="method" required class="form-select">
                            <option value="cash">Nakit</option>
                            <option value="bank">Banka</option>
                            <option value="other">Diğer</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="note" class="form-label">Not</label>
                        <textarea name="note" id="note" rows="3" class="form-control">{{ old('note') }}</textarea>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.advances.index') }}" class="btn btn-secondary">
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
