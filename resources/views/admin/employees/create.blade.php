@extends('layouts.admin')

@section('title', 'Yeni Çalışan')
@section('page-title', 'Yeni Çalışan')
@section('page-subtitle', 'Yeni bir çalışan ekleyin')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.employees.store') }}">
                    @csrf
                    <div class="mb-3">
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
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Ad Soyad</label>
                        <input type="text" name="full_name" id="full_name" value="{{ old('full_name') }}" required
                            class="form-control" placeholder="Çalışanın adı ve soyadı">
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Telefon</label>
                            <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                                class="form-control" placeholder="0532 123 4567">
                        </div>
                        <div class="col-md-6">
                            <label for="start_date" class="form-label">İşe Başlama Tarihi</label>
                            <input type="date" name="start_date" id="start_date" value="{{ old('start_date') }}"
                                class="form-control">
                        </div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="status" value="1" id="status" {{ old('status', true) ? 'checked' : '' }} class="form-check-input">
                        <label class="form-check-label" for="status">Aktif</label>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.employees.index') }}" class="btn btn-secondary">
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
