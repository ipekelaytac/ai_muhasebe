@extends('layouts.admin')

@section('title', 'Avans Düzenle')
@section('page-title', 'Avans Düzenle')
@section('page-subtitle', 'Avans bilgilerini güncelleyin')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.advances.update', $advance) }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="employee_id" class="form-label">Çalışan</label>
                        <select name="employee_id" id="employee_id" required class="form-select">
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" 
                                    data-company="{{ $employee->company->name }}"
                                    data-branch="{{ $employee->branch->name }}"
                                    {{ old('employee_id', $advance->employee_id) == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->full_name }} - {{ $employee->company->name }} / {{ $employee->branch->name }}
                                </option>
                            @endforeach
                        </select>
                        <div id="employeeInfo" class="mt-2 text-muted small">
                            <span id="companyInfo">{{ $advance->company->name }}</span> / <span id="branchInfo">{{ $advance->branch->name }}</span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="advance_date" class="form-label">Tarih</label>
                            <input type="date" name="advance_date" id="advance_date" value="{{ old('advance_date', $advance->advance_date->format('Y-m-d')) }}" required
                                class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="amount" class="form-label">Tutar</label>
                            <input type="number" step="0.01" name="amount" id="amount" value="{{ old('amount', $advance->amount) }}" required
                                class="form-control" min="0.01">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="method" class="form-label">Ödeme Yöntemi</label>
                        <select name="method" id="method" required class="form-select">
                            <option value="cash" {{ old('method', $advance->method) == 'cash' ? 'selected' : '' }}>Nakit</option>
                            <option value="bank" {{ old('method', $advance->method) == 'bank' ? 'selected' : '' }}>Banka</option>
                            <option value="other" {{ old('method', $advance->method) == 'other' ? 'selected' : '' }}>Diğer</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="note" class="form-label">Not</label>
                        <textarea name="note" id="note" rows="3" class="form-control">{{ old('note', $advance->note) }}</textarea>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.advances.index') }}" class="btn btn-secondary">
                            İptal
                        </a>
                        <button type="submit" class="btn btn-primary">
                            Güncelle
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
    const companyInfo = document.getElementById('companyInfo');
    const branchInfo = document.getElementById('branchInfo');

    employeeSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            companyInfo.textContent = selectedOption.getAttribute('data-company');
            branchInfo.textContent = selectedOption.getAttribute('data-branch');
        }
    });
});
</script>
@endsection
