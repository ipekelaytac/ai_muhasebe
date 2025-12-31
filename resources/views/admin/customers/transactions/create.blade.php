@extends('layouts.admin')

@section('title', 'Hareket Ekle')
@section('page-title', 'Hareket Ekle - ' . $customer->name)
@section('page-subtitle', 'Yeni bir gelir/gider hareketi ekleyin')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.customers.transactions.store', $customer) }}">
                    @csrf
                    <div class="mb-3">
                        <label for="type" class="form-label">Tip <span class="text-danger">*</span></label>
                        <select name="type" id="type" required class="form-select">
                            <option value="income" {{ old('type', 'income') == 'income' ? 'selected' : '' }}>Gelir</option>
                            <option value="expense" {{ old('type') == 'expense' ? 'selected' : '' }}>Gider</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="transaction_date" class="form-label">Tarih <span class="text-danger">*</span></label>
                        <input type="date" name="transaction_date" id="transaction_date" 
                            value="{{ old('transaction_date', date('Y-m-d')) }}" required class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="amount" class="form-label">Tutar <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="amount" id="amount" step="0.01" min="0.01" 
                                value="{{ old('amount') }}" required class="form-control" placeholder="0.00">
                            <span class="input-group-text">₺</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Açıklama</label>
                        <textarea name="description" id="description" rows="3" class="form-control" placeholder="Açıklama">{{ old('description') }}</textarea>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-secondary">
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

