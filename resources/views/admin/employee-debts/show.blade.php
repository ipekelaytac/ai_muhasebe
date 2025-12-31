@extends('layouts.admin')

@section('title', 'Borç Detay')
@section('page-title', 'Borç Detay')
@section('page-subtitle', $employeeDebt->employee->full_name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <a href="{{ route('admin.employee-debts.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Geri
    </a>
    <div>
        <a href="{{ route('admin.employee-debts.edit', $employeeDebt) }}" class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i>Düzenle
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-2">Borç Tutarı</h6>
                <p class="card-text fs-4 fw-bold text-danger mb-0">
                    {{ number_format($employeeDebt->amount, 2) }} ₺
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-2">Ödenen</h6>
                <p class="card-text fs-4 fw-bold text-success mb-0">
                    {{ number_format($employeeDebt->paid_amount, 2) }} ₺
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-2">Kalan</h6>
                <p class="card-text fs-4 fw-bold {{ $employeeDebt->remaining_amount > 0 ? 'text-danger' : 'text-success' }} mb-0">
                    {{ number_format($employeeDebt->remaining_amount, 2) }} ₺
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-2">Durum</h6>
                <p class="card-text mb-0">
                    <span class="badge {{ $employeeDebt->status ? 'bg-warning' : 'bg-success' }} fs-6">
                        {{ $employeeDebt->status ? 'Açık' : 'Kapalı' }}
                    </span>
                </p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light">
                <h6 class="mb-0">Borç Bilgileri</h6>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Personel:</dt>
                    <dd class="col-sm-8 fw-bold">{{ $employeeDebt->employee->full_name }}</dd>

                    <dt class="col-sm-4">Şirket / Şube:</dt>
                    <dd class="col-sm-8">{{ $employeeDebt->company->name }} / {{ $employeeDebt->branch->name }}</dd>

                    <dt class="col-sm-4">Borç Tarihi:</dt>
                    <dd class="col-sm-8">{{ $employeeDebt->debt_date->format('d.m.Y') }}</dd>

                    <dt class="col-sm-4">Borç Tutarı:</dt>
                    <dd class="col-sm-8 fw-bold text-danger">{{ number_format($employeeDebt->amount, 2) }} ₺</dd>

                    @if($employeeDebt->description)
                    <dt class="col-sm-4">Açıklama:</dt>
                    <dd class="col-sm-8">{{ $employeeDebt->description }}</dd>
                    @endif

                    <dt class="col-sm-4">Oluşturan:</dt>
                    <dd class="col-sm-8">{{ $employeeDebt->creator->name ?? '-' }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-light">
        <h6 class="mb-0">Ödemeler</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Ödeme Tarihi</th>
                        <th>Tutar</th>
                        <th>Bordro</th>
                        <th>Notlar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employeeDebt->payments as $payment)
                        <tr>
                            <td>{{ $payment->payment_date->format('d.m.Y') }}</td>
                            <td class="fw-bold text-success">{{ number_format($payment->amount, 2) }} ₺</td>
                            <td>
                                @if($payment->payrollItem)
                                    <a href="{{ route('admin.payroll.item', $payment->payrollItem) }}" class="text-decoration-none">
                                        {{ $payment->payrollItem->payrollPeriod->period_name }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $payment->notes ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                                <p class="text-muted mb-0">Henüz ödeme bulunmuyor</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

