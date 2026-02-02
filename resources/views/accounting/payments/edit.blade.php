@extends('layouts.admin')

@section('title', 'Ödeme Düzenle')
@section('page-title', 'Ödeme Düzenle')
@section('page-subtitle', $payment->payment_number)

@section('content')
@if(!$payment->canModify())
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle me-2"></i>
        Bu ödeme değiştirilemez. Dönem kilitli veya ödeme kapalı.
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('accounting.payments.update', $payment) }}">
            @csrf
            @method('PUT')
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Referans No</label>
                    <input type="text" name="reference_number" class="form-control @error('reference_number') is-invalid @enderror" value="{{ old('reference_number', $payment->reference_number) }}">
                    @error('reference_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-12">
                    <label class="form-label">Açıklama</label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2">{{ old('description', $payment->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-12">
                    <label class="form-label">Notlar</label>
                    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes', $payment->notes) }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-between">
                <a href="{{ route('accounting.payments.show', $payment) }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Geri
                </a>
                @if($payment->canModify())
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Güncelle
                    </button>
                @endif
            </div>
        </form>
    </div>
</div>
@endsection
