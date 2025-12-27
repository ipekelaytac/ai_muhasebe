@extends('layouts.admin')

@section('title', 'Yeni Bordro Dönemi')
@section('page-title', 'Yeni Bordro Dönemi')
@section('page-subtitle', 'Yeni bir bordro dönemi oluşturun')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.payroll.store') }}">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="company_id" class="form-label">Şirket</label>
                            <select name="company_id" id="company_id" required class="form-select">
                                <option value="">Seçiniz</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                        {{ $company->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="branch_id" class="form-label">Şube</label>
                            <select name="branch_id" id="branch_id" required class="form-select">
                                <option value="">Seçiniz</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="year" class="form-label">Yıl</label>
                            <input type="number" min="2020" max="2100" name="year" id="year" value="{{ old('year', now()->year) }}" required
                                class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="month" class="form-label">Ay</label>
                            <select name="month" id="month" required class="form-select">
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ old('month', now()->month) == $i ? 'selected' : '' }}>
                                        {{ ['', 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'][$i] }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.payroll.index') }}" class="btn btn-secondary">
                            İptal
                        </a>
                        <button type="submit" class="btn btn-primary">
                            Oluştur
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
