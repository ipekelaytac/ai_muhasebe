@extends('layouts.admin')

@section('title', 'Kesinti Tipi Düzenle')
@section('page-title', 'Kesinti Tipi Düzenle')
@section('page-subtitle', 'Kesinti tipi bilgilerini güncelleyin')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.payroll.deduction-types.update', $deductionType) }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="name" class="form-label">Kesinti Tipi Adı</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $deductionType->name) }}" required
                            class="form-control">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $deductionType->is_active) ? 'checked' : '' }} class="form-check-input">
                        <label class="form-check-label" for="is_active">Aktif</label>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.payroll.deduction-types.index') }}" class="btn btn-secondary">
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

