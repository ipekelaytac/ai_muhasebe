@extends('layouts.admin')

@section('title', 'Cari Düzenle')
@section('page-title', 'Cari Düzenle')
@section('page-subtitle', 'Cari bilgilerini güncelleyin')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.customers.update', $customer) }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="branch_id" class="form-label">Şube <span class="text-danger">*</span></label>
                            <select name="branch_id" id="branch_id" required class="form-select">
                                <option value="">Seçiniz</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id', $customer->branch_id) == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="type" class="form-label">Tip <span class="text-danger">*</span></label>
                            <select name="type" id="type" required class="form-select">
                                <option value="customer" {{ old('type', $customer->type) == 'customer' ? 'selected' : '' }}>Müşteri</option>
                                <option value="supplier" {{ old('type', $customer->type) == 'supplier' ? 'selected' : '' }}>Tedarikçi</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="code" class="form-label">Cari Kodu</label>
                            <input type="text" name="code" id="code" value="{{ old('code', $customer->code) }}"
                                class="form-control" placeholder="Opsiyonel">
                        </div>
                        <div class="col-md-6">
                            <label for="name" class="form-label">Cari Adı <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name', $customer->name) }}" required
                                class="form-control" placeholder="Cari adı">
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Telefon</label>
                            <input type="text" name="phone" id="phone" value="{{ old('phone', $customer->phone) }}"
                                class="form-control" placeholder="0532 123 4567">
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">E-posta</label>
                            <input type="email" name="email" id="email" value="{{ old('email', $customer->email) }}"
                                class="form-control" placeholder="ornek@email.com">
                        </div>
                        <div class="col-md-12">
                            <label for="address" class="form-label">Adres</label>
                            <textarea name="address" id="address" rows="2" class="form-control" placeholder="Adres bilgisi">{{ old('address', $customer->address) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="tax_number" class="form-label">Vergi No</label>
                            <input type="text" name="tax_number" id="tax_number" value="{{ old('tax_number', $customer->tax_number) }}"
                                class="form-control" placeholder="Vergi numarası">
                        </div>
                        <div class="col-md-6">
                            <label for="tax_office" class="form-label">Vergi Dairesi</label>
                            <input type="text" name="tax_office" id="tax_office" value="{{ old('tax_office', $customer->tax_office) }}"
                                class="form-control" placeholder="Vergi dairesi">
                        </div>
                        <div class="col-md-12">
                            <label for="notes" class="form-label">Notlar</label>
                            <textarea name="notes" id="notes" rows="3" class="form-control" placeholder="Notlar">{{ old('notes', $customer->notes) }}</textarea>
                        </div>
                        <div class="col-md-12">
                            <div class="form-check">
                                <input type="checkbox" name="status" value="1" id="status" {{ old('status', $customer->status) ? 'checked' : '' }} class="form-check-input">
                                <label class="form-check-label" for="status">Aktif</label>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('admin.customers.index') }}" class="btn btn-secondary">
                            İptal
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>Güncelle
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

