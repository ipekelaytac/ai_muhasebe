@extends('layouts.admin')

@section('title', 'Çalışan Düzenle')
@section('page-title', 'Çalışan Düzenle')
@section('page-subtitle', 'Çalışan bilgilerini güncelleyin')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.employees.update', $employee) }}">
                    @csrf
                    @method('PUT')
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="company_id" class="form-label">Şirket</label>
                            <select name="company_id" id="company_id" required class="form-select">
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" {{ old('company_id', $employee->company_id) == $company->id ? 'selected' : '' }}>
                                        {{ $company->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="branch_id" class="form-label">Şube</label>
                            <select name="branch_id" id="branch_id" required class="form-select">
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id', $employee->branch_id) == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Ad Soyad</label>
                        <input type="text" name="full_name" id="full_name" value="{{ old('full_name', $employee->full_name) }}" required
                            class="form-control">
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Telefon</label>
                            <input type="text" name="phone" id="phone" value="{{ old('phone', $employee->phone) }}"
                                class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="start_date" class="form-label">İşe Başlama Tarihi</label>
                            <input type="date" name="start_date" id="start_date" value="{{ old('start_date', $employee->start_date ? $employee->start_date->format('Y-m-d') : '') }}"
                                class="form-control">
                        </div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="status" value="1" id="status" {{ old('status', $employee->status) ? 'checked' : '' }} class="form-check-input">
                        <label class="form-check-label" for="status">Aktif</label>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.employees.index') }}" class="btn btn-secondary">
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
@endsection
