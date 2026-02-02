@extends('layouts.admin')

@section('title', 'Mesai Detay')
@section('page-title', $document->document_number)
@section('page-subtitle', 'Mesai Tahakkuku')

@section('content')
@if($document->isInLockedPeriod())
    <div class="alert alert-warning">
        <i class="bi bi-lock me-2"></i>
        <strong>Uyarı:</strong> Bu belge kilitli bir dönemde. Değişiklik yapmak için ters kayıt kullanın.
    </div>
@endif

<div class="row mb-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0">Mesai Bilgileri</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <strong>Belge No:</strong> {{ $document->document_number }}
                    </div>
                    <div class="col-md-6">
                        <strong>Personel:</strong> 
                        <a href="{{ route('accounting.parties.show', $document->party_id) }}" class="text-decoration-none">
                            {{ $document->party->name }}
                        </a>
                    </div>
                    <div class="col-md-6">
                        <strong>Belge Tarihi:</strong> {{ $document->document_date->format('d.m.Y') }}
                    </div>
                    <div class="col-md-6">
                        <strong>Vade Tarihi:</strong> {{ $document->due_date ? $document->due_date->format('d.m.Y') : '-' }}
                    </div>
                    <div class="col-md-6">
                        <strong>Toplam Tutar:</strong> 
                        <span class="fw-bold fs-5 text-primary">{{ number_format($document->total_amount, 2) }} ₺</span>
                    </div>
                    <div class="col-md-6">
                        <strong>Durum:</strong> 
                        <span class="badge bg-{{ $document->status == 'settled' ? 'success' : ($document->status == 'partial' ? 'warning' : 'secondary') }}">
                            {{ \App\Domain\Accounting\Enums\DocumentStatus::getLabel($document->status) }}
                        </span>
                    </div>
                    @if($document->description)
                        <div class="col-md-12">
                            <strong>Açıklama:</strong> {{ $document->description }}
                        </div>
                    @endif
                    @if($document->notes)
                        <div class="col-md-12">
                            <strong>Notlar:</strong>
                            <pre class="bg-light p-2 rounded">{{ $document->notes }}</pre>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        @if($document->activeAllocations->count() > 0)
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0">Yapılan Ödemeler</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Ödeme No</th>
                                    <th>Tarih</th>
                                    <th>Tutar</th>
                                    <th>Kaynak</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($document->activeAllocations as $allocation)
                                    <tr>
                                        <td>
                                            <a href="{{ route('accounting.payments.show', $allocation->payment_id) }}">
                                                {{ $allocation->payment->payment_number }}
                                            </a>
                                        </td>
                                        <td>{{ $allocation->payment->payment_date->format('d.m.Y') }}</td>
                                        <td class="fw-bold text-success">{{ number_format($allocation->amount, 2) }} ₺</td>
                                        <td>
                                            @if($allocation->payment->cashbox)
                                                <span class="badge bg-success">Kasa: {{ $allocation->payment->cashbox->name }}</span>
                                            @elseif($allocation->payment->bankAccount)
                                                <span class="badge bg-primary">Banka: {{ $allocation->payment->bankAccount->name }}</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $allocation->payment->type_label }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="2">Toplam Ödenen:</th>
                                    <th class="text-success">{{ number_format($document->paid_amount, 2) }} ₺</th>
                                    <th></th>
                                </tr>
                                <tr>
                                    <th colspan="2">Kalan:</th>
                                    <th class="{{ $document->unpaid_amount > 0 ? 'text-warning' : 'text-success' }}">
                                        {{ number_format($document->unpaid_amount, 2) }} ₺
                                    </th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
    
    <div class="col-md-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Toplam Tutar</h6>
                <h3 class="text-primary mb-0">{{ number_format($document->total_amount, 2) }} ₺</h3>
            </div>
        </div>
        
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Ödenen</h6>
                <h4 class="text-success mb-0">{{ number_format($document->paid_amount, 2) }} ₺</h4>
            </div>
        </div>
        
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Kalan</h6>
                <h4 class="{{ $document->unpaid_amount > 0 ? 'text-warning' : 'text-success' }} mb-0">
                    {{ number_format($document->unpaid_amount, 2) }} ₺
                </h4>
            </div>
        </div>
        
        @if($document->unpaid_amount > 0)
            <div class="d-grid">
                <a href="{{ route('accounting.payments.create', ['party_id' => $document->party_id, 'document_id' => $document->id]) }}" 
                   class="btn btn-primary">
                    <i class="bi bi-cash-coin me-1"></i>Ödeme Yap
                </a>
            </div>
        @endif
        
        <div class="mt-3">
            <a href="{{ route('accounting.overtime.index') }}" class="btn btn-outline-secondary w-100">
                <i class="bi bi-arrow-left me-1"></i>Geri
            </a>
        </div>
    </div>
</div>
@endsection
