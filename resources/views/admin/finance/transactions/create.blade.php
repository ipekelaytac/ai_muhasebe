@extends('layouts.admin')

@section('title', 'Yeni İşlem')
@section('page-title', 'Yeni İşlem')
@section('page-subtitle', 'Yeni bir finans işlemi ekleyin')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.finance.transactions.store') }}" enctype="multipart/form-data">
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
                            <label for="type" class="form-label">Tip</label>
                            <select name="type" id="type" required class="form-select">
                                <option value="expense" {{ old('type') == 'expense' ? 'selected' : '' }}>Gider</option>
                                <option value="income" {{ old('type') == 'income' ? 'selected' : '' }}>Gelir</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="category_id" class="form-label">Kategori</label>
                            <select name="category_id" id="category_id" required class="form-select">
                                <option value="">Seçiniz</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="transaction_date" class="form-label">Tarih</label>
                        <input type="date" name="transaction_date" id="transaction_date" value="{{ old('transaction_date', now()->toDateString()) }}" required
                            class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="amount" class="form-label">Tutar</label>
                        <input type="number" step="0.01" name="amount" id="amount" value="{{ old('amount') }}" required
                            class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Açıklama</label>
                        <textarea name="description" id="description" rows="3" class="form-control">{{ old('description') }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label for="attachments" class="form-label">Ekler (Maksimum 5MB, Birden fazla seçilebilir)</label>
                        <input type="file" name="attachments[]" id="attachments" multiple class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <small class="text-muted">PDF, resim veya belge dosyaları yükleyebilirsiniz.</small>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.finance.transactions.index') }}" class="btn btn-secondary">
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
