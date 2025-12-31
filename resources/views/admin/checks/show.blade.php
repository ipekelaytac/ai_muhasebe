@extends('layouts.admin')

@section('title', 'Çek Detay')
@section('page-title', 'Çek Detay - ' . $check->check_number)
@section('page-subtitle', $check->company->name . ' - ' . $check->branch->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <a href="{{ route('admin.checks.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Geri
    </a>
    <div>
        <a href="{{ route('admin.checks.edit', $check) }}" class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i>Düzenle
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-2">Tutar</h6>
                <p class="card-text fs-4 fw-bold text-primary mb-0">
                    {{ number_format($check->amount, 2) }} ₺
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-2">Durum</h6>
                <p class="card-text mb-0">
                    @if($check->status == 'pending')
                        <span class="badge bg-warning fs-6">Bekliyor</span>
                    @elseif($check->status == 'cashed')
                        <span class="badge bg-success fs-6">Bozduruldu</span>
                    @else
                        <span class="badge bg-secondary fs-6">İptal</span>
                    @endif
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-2">Vade Tarihi</h6>
                <p class="card-text fs-5 fw-bold {{ $check->due_date->isPast() && $check->status == 'pending' ? 'text-danger' : '' }} mb-0">
                    {{ $check->due_date->format('d.m.Y') }}
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-2">Bozdurulma Tarihi</h6>
                <p class="card-text fs-5 fw-bold mb-0">
                    {{ $check->cashed_date ? $check->cashed_date->format('d.m.Y') : '-' }}
                </p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light">
                <h6 class="mb-0">Çek Bilgileri</h6>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Çek Numarası:</dt>
                    <dd class="col-sm-8 fw-bold">{{ $check->check_number }}</dd>

                    <dt class="col-sm-4">Banka:</dt>
                    <dd class="col-sm-8">{{ $check->bank_name }}</dd>

                    <dt class="col-sm-4">Cari:</dt>
                    <dd class="col-sm-8">
                        <a href="{{ route('admin.customers.show', $check->customer) }}" class="text-decoration-none">
                            {{ $check->customer->name }}
                        </a>
                        <span class="badge {{ $check->customer->type == 'customer' ? 'bg-info' : 'bg-warning' }} ms-2">
                            {{ $check->customer->type == 'customer' ? 'Müşteri' : 'Tedarikçi' }}
                        </span>
                    </dd>

                    <dt class="col-sm-4">Geldiği Tarih:</dt>
                    <dd class="col-sm-8">{{ $check->received_date->format('d.m.Y') }}</dd>

                    <dt class="col-sm-4">Vade Tarihi:</dt>
                    <dd class="col-sm-8">
                        <span class="{{ $check->due_date->isPast() && $check->status == 'pending' ? 'text-danger fw-bold' : '' }}">
                            {{ $check->due_date->format('d.m.Y') }}
                        </span>
                        @if($check->due_date->isPast() && $check->status == 'pending')
                            <span class="badge bg-danger ms-2">Vadesi Geçti</span>
                        @endif
                    </dd>

                    @if($check->cashed_date)
                    <dt class="col-sm-4">Bozdurulma Tarihi:</dt>
                    <dd class="col-sm-8">{{ $check->cashed_date->format('d.m.Y') }}</dd>
                    @endif

                    <dt class="col-sm-4">Durum:</dt>
                    <dd class="col-sm-8">
                        @if($check->status == 'pending')
                            <span class="badge bg-warning">Bekliyor</span>
                        @elseif($check->status == 'cashed')
                            <span class="badge bg-success">Bozduruldu</span>
                        @else
                            <span class="badge bg-secondary">İptal</span>
                        @endif
                    </dd>

                    @if($check->notes)
                    <dt class="col-sm-4">Notlar:</dt>
                    <dd class="col-sm-8">{{ $check->notes }}</dd>
                    @endif

                    <dt class="col-sm-4">Oluşturan:</dt>
                    <dd class="col-sm-8">{{ $check->creator->name ?? '-' }}</dd>

                    <dt class="col-sm-4">Oluşturulma:</dt>
                    <dd class="col-sm-8">{{ $check->created_at->format('d.m.Y H:i') }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection

