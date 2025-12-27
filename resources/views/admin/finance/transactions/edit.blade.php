@extends('layouts.admin')

@section('title', 'İşlem Düzenle')
@section('page-title', 'İşlem Düzenle')
@section('page-subtitle', 'İşlem bilgilerini güncelleyin')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.finance.transactions.update', $transaction) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="company_id" class="form-label">Şirket</label>
                            <select name="company_id" id="company_id" required class="form-select">
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" {{ old('company_id', $transaction->company_id) == $company->id ? 'selected' : '' }}>
                                        {{ $company->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="branch_id" class="form-label">Şube</label>
                            <select name="branch_id" id="branch_id" required class="form-select">
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id', $transaction->branch_id) == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="type" class="form-label">Tip</label>
                            <select name="type" id="type" required class="form-select">
                                <option value="expense" {{ old('type', $transaction->type) == 'expense' ? 'selected' : '' }}>Gider</option>
                                <option value="income" {{ old('type', $transaction->type) == 'income' ? 'selected' : '' }}>Gelir</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="category_id" class="form-label">Kategori</label>
                            <select name="category_id" id="category_id" required class="form-select">
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id', $transaction->category_id) == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="transaction_date" class="form-label">Tarih</label>
                        <input type="date" name="transaction_date" id="transaction_date" value="{{ old('transaction_date', $transaction->transaction_date->format('Y-m-d')) }}" required
                            class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="amount" class="form-label">Tutar</label>
                        <input type="number" step="0.01" name="amount" id="amount" value="{{ old('amount', $transaction->amount) }}" required
                            class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Açıklama</label>
                        <textarea name="description" id="description" rows="3" class="form-control">{{ old('description', $transaction->description) }}</textarea>
                    </div>
                    
                    @if($transaction->attachments->count() > 0)
                        <div class="mb-3">
                            <label class="form-label">Mevcut Ekler</label>
                            <div class="list-group">
                                @foreach($transaction->attachments as $attachment)
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="bi bi-paperclip me-2"></i>
                                            <a href="{{ Storage::url($attachment->file_path) }}" target="_blank" class="text-decoration-none">
                                                {{ basename($attachment->file_path) }}
                                            </a>
                                        </div>
                                        <form action="{{ route('admin.finance.transactions.attachment.destroy', $attachment) }}" method="POST" class="d-inline" onsubmit="return confirm('Emin misiniz?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    
                    <div class="mb-3">
                        <label for="attachments" class="form-label">Yeni Ekler Ekle (Maksimum 5MB, Birden fazla seçilebilir)</label>
                        <input type="file" name="attachments[]" id="attachments" multiple class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <small class="text-muted">PDF, resim veya belge dosyaları yükleyebilirsiniz.</small>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.finance.transactions.index') }}" class="btn btn-secondary">
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
