@extends('layouts.admin')

@section('title', 'Şirket Düzenle')
@section('page-title', 'Şirket Düzenle')
@section('page-subtitle', 'Şirket bilgilerini güncelleyin')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.companies.update', $company) }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="name" class="form-label">Şirket Adı</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $company->name) }}" required
                            class="form-control">
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.companies.index') }}" class="btn btn-secondary">
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
