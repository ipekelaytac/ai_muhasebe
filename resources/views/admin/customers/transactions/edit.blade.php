@extends('layouts.admin')

@section('title', 'Hareket Düzenle')
@section('page-title', 'Hareket Düzenle - ' . $customer->name)
@section('page-subtitle', 'Hareket bilgilerini güncelleyin')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.customers.transactions.update', [$customer, $transaction]) }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="type" class="form-label">Tip <span class="text-danger">*</span></label>
                        <select name="type" id="type" required class="form-select">
                            <option value="income" {{ old('type', $transaction->type) == 'income' ? 'selected' : '' }}>Gelir</option>
                            <option value="expense" {{ old('type', $transaction->type) == 'expense' ? 'selected' : '' }}>Gider</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="transaction_date" class="form-label">Tarih <span class="text-danger">*</span></label>
                        <input type="date" name="transaction_date" id="transaction_date" 
                            value="{{ old('transaction_date', $transaction->transaction_date->format('Y-m-d')) }}" required class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="amount" class="form-label">Tutar <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="amount" id="amount" step="0.01" min="0.01" 
                                value="{{ old('amount', $transaction->amount) }}" required class="form-control" placeholder="0.00">
                            <span class="input-group-text">₺</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Açıklama</label>
                        <textarea name="description" id="description" rows="3" class="form-control" placeholder="Açıklama">{{ old('description', $transaction->description) }}</textarea>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-secondary">
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

