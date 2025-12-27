@extends('layouts.admin')

@section('title', 'Yeni Şube')
@section('page-title', 'Yeni Şube')
@section('page-subtitle', 'Yeni bir şube ekleyin')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.branches.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">Şube Adı <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                            class="form-control @error('name') is-invalid @enderror" placeholder="Şube adını girin">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Adres</label>
                        <textarea name="address" id="address" rows="3"
                            class="form-control @error('address') is-invalid @enderror" placeholder="Şube adresini girin">{{ old('address') }}</textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.branches.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>
                            İptal
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>
                            Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

