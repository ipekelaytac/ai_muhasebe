@extends('layouts.admin')

@section('title', 'Yeni Kesinti Tipi')
@section('page-title', 'Yeni Kesinti Tipi')
@section('page-subtitle', 'Yeni bir kesinti tipi ekleyin')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.payroll.deduction-types.store') }}">
                    @csrf
                    <div class="mb-3">
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
                    <div class="mb-3">
                        <label for="name" class="form-label">Kesinti Tipi Adı</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                            class="form-control" placeholder="Örn: SGK Kesintisi, Vergi Kesintisi">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', true) ? 'checked' : '' }} class="form-check-input">
                        <label class="form-check-label" for="is_active">Aktif</label>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.payroll.deduction-types.index') }}" class="btn btn-secondary">
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
@endsection

