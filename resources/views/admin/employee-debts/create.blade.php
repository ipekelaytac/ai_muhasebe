@extends('layouts.admin')

@section('title', 'Yeni Borç')
@section('page-title', 'Yeni Borç')
@section('page-subtitle', 'Yeni bir borç ekleyin')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.employee-debts.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="branch_id" class="form-label">Şube <span class="text-danger">*</span></label>
                            <select name="branch_id" id="branch_id" required class="form-select">
                                <option value="">Seçiniz</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
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
                                    <option value="{{ $employee->id }}" {{ old('employee_id', $selectedEmployeeId ?? '') == $employee->id ? 'selected' : '' }}>
                                        {{ $employee->full_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="debt_date" class="form-label">Borç Tarihi <span class="text-danger">*</span></label>
                            <input type="date" name="debt_date" id="debt_date" 
                                value="{{ old('debt_date', date('Y-m-d')) }}" required class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="amount" class="form-label">Borç Tutarı <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="amount" id="amount" step="0.01" min="0.01" 
                                    value="{{ old('amount') }}" required class="form-control" placeholder="0.00">
                                <span class="input-group-text">₺</span>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label for="description" class="form-label">Açıklama</label>
                            <textarea name="description" id="description" rows="3" class="form-control" placeholder="Borç açıklaması">{{ old('description') }}</textarea>
                        </div>
                        <div class="col-md-12">
                            <div class="form-check">
                                <input type="checkbox" name="status" value="1" id="status" {{ old('status', true) ? 'checked' : '' }} class="form-check-input">
                                <label class="form-check-label" for="status">Açık</label>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('admin.employee-debts.index') }}" class="btn btn-secondary">
                            İptal
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

