@extends('layouts.admin')

@section('title', 'Bordro Detay')
@section('page-title', 'Bordro Detay - ' . $item->employee->full_name)
@section('page-subtitle', $item->payrollPeriod->period_name . ' | ' . $item->payrollPeriod->company->name . ' - ' . $item->payrollPeriod->branch->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <a href="{{ route('admin.payroll.show', $item->payrollPeriod) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Geri
    </a>
    <div>
        {{-- Add other actions here if needed --}}
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-2">Net Maaş</h6>
                <p class="card-text fs-4 fw-bold text-primary mb-0">{{ number_format($item->base_net_salary, 2) }} ₺</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-2">Yemek Yardımı</h6>
                <p class="card-text fs-4 fw-bold text-info mb-0">{{ number_format($item->meal_allowance, 2) }} ₺</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-2">Net Ödenecek</h6>
                <p class="card-text fs-4 fw-bold text-primary mb-0">{{ number_format($item->net_payable, 2) }} ₺</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-2">Toplam Ödenen</h6>
                <p class="card-text fs-4 fw-bold text-success mb-0">{{ number_format($item->total_paid, 2) }} ₺</p>
            </div>
        </div>
    </div>
</div>
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-2">Kalan</h6>
                <p class="card-text fs-3 fw-bold text-danger mb-0">{{ number_format($item->total_remaining, 2) }} ₺</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-2">Kesintiler</h6>
                <p class="card-text fs-3 fw-bold text-warning mb-0">{{ number_format($item->deduction_total, 2) }} ₺</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-subtitle text-muted mb-2">Avans Mahsupları</h6>
                <p class="card-text fs-3 fw-bold text-secondary mb-0">{{ number_format($item->advances_deducted_total, 2) }} ₺</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    @forelse($item->installments as $installment)
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title mb-3">{{ $installment->title ?? 'Taksit ' . $installment->installment_no }}</h5>
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Vade:</dt>
                        <dd class="col-sm-8">{{ $installment->due_date->format('d.m.Y') }}</dd>

                        <dt class="col-sm-4">Planlanan:</dt>
                        <dd class="col-sm-8">{{ number_format($installment->planned_amount, 2) }} ₺</dd>

                        <dt class="col-sm-4">Ödenen:</dt>
                        <dd class="col-sm-8">{{ number_format($installment->paid_amount, 2) }} ₺</dd>

                        <dt class="col-sm-4">Kalan:</dt>
                        <dd class="col-sm-8">
                            <span class="fw-bold {{ $installment->remaining_amount < 0 ? 'text-danger' : ($installment->remaining_amount > 0 ? 'text-warning' : 'text-success') }}">
                                {{ number_format($installment->remaining_amount, 2) }} ₺
                            </span>
                        </dd>
                    </dl>
                    @if($installment->deductions->count() > 0 || $installment->advanceSettlements->count() > 0)
                        <hr class="my-2">
                        @if($installment->deductions->count() > 0)
                            <div class="mb-2">
                                <small class="text-muted d-block mb-1">Kesintiler:</small>
                                @foreach($installment->deductions as $deduction)
                                    <span class="badge bg-warning text-dark me-1">
                                        {{ $deduction->deductionType->name }}: {{ number_format($deduction->amount, 2) }} ₺
                                    </span>
                                @endforeach
                            </div>
                        @endif
                        @if($installment->advanceSettlements->count() > 0)
                            <div>
                                <small class="text-muted d-block mb-1">Avans Mahsupları:</small>
                                @foreach($installment->advanceSettlements as $settlement)
                                    <span class="badge bg-info text-dark me-1">
                                        {{ $settlement->advance->advance_date->format('d.m.Y') }}: {{ number_format($settlement->settled_amount, 2) }} ₺
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="bi bi-cash-stack display-4 text-muted mb-3"></i>
                    <p class="lead text-muted">Bu bordro kalemi için henüz taksit bulunmuyor.</p>
                </div>
            </div>
        </div>
    @endforelse
</div>

<div class="mb-4">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Kesintiler</h5>
            <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#deductionModal">
                <i class="bi bi-plus-circle me-2"></i>Kesinti Ekle
            </button>
        </div>
        <div class="card-body">
            @forelse($item->deductions as $deduction)
                <div class="row g-3 align-items-center border-bottom pb-3 mb-3">
                    <div class="col-md-2">
                        <div class="fw-bold text-primary">{{ $deduction->deductionType->name }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-1">
                            <small class="text-muted d-block">Taksit Seçimi:</small>
                            @if($deduction->payroll_installment_id && $deduction->installment)
                                <div>
                                    <span class="badge bg-info text-dark">
                                        {{ $deduction->installment->installment_no }}. Taksit
                                    </span>
                                    @if($deduction->installment->title)
                                        <br><small class="text-muted fw-bold">{{ $deduction->installment->title }}</small>
                                    @endif
                                    <br><small class="text-muted">({{ $deduction->installment->due_date->format('d.m.Y') }})</small>
                                </div>
                            @else
                                <span class="badge bg-secondary">Genel (Her İki Taksitten)</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-2 text-end">
                        <div class="mb-1">
                            <small class="text-muted d-block">Tutar:</small>
                            <span class="fw-bold fs-5 text-warning">{{ number_format($deduction->amount, 2) }} ₺</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-1">
                            <small class="text-muted d-block">Not:</small>
                            @if($deduction->description)
                                <span class="text-muted">{{ $deduction->description }}</span>
                            @else
                                <span class="text-muted fst-italic">Not yok</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-2 text-end">
                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteDeductionModal{{ $deduction->id }}">
                            <i class="bi bi-trash"></i> Sil
                        </button>
                    </div>
                </div>
            @empty
                <div class="text-center py-5">
                    <i class="bi bi-tag display-4 text-muted d-block mb-3"></i>
                    <p class="text-muted mb-0">Kesinti bulunmuyor</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

<div class="mb-4">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Avans Mahsuplaşmaları</h5>
            <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#advanceModal">
                <i class="bi bi-plus-circle me-2"></i>Avans Mahsup Et
            </button>
        </div>
        <div class="card-body">
            @forelse($item->advanceSettlements as $settlement)
                <div class="row g-3 align-items-center border-bottom pb-3 mb-3">
                    <div class="col-md-2">
                        <div class="mb-1">
                            <small class="text-muted d-block">Avans Tarihi:</small>
                            <div class="fw-bold">{{ $settlement->advance->advance_date->format('d.m.Y') }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-1">
                            <small class="text-muted d-block">Taksit Seçimi:</small>
                            @if($settlement->payroll_installment_id && $settlement->installment)
                                <div>
                                    <span class="badge bg-info text-dark">
                                        {{ $settlement->installment->installment_no }}. Taksit
                                    </span>
                                    @if($settlement->installment->title)
                                        <br><small class="text-muted fw-bold">{{ $settlement->installment->title }}</small>
                                    @endif
                                    <br><small class="text-muted">({{ $settlement->installment->due_date->format('d.m.Y') }})</small>
                                </div>
                            @else
                                <span class="badge bg-secondary">Genel (Her İki Taksitten)</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-1">
                            <small class="text-muted d-block">Mahsup Tarihi:</small>
                            <div>{{ $settlement->settled_date->format('d.m.Y') }}</div>
                        </div>
                    </div>
                    <div class="col-md-3 text-end">
                        <div class="mb-1">
                            <small class="text-muted d-block">Tutar:</small>
                            <span class="fw-bold fs-5 text-info">{{ number_format($settlement->settled_amount, 2) }} ₺</span>
                        </div>
                    </div>
                    <div class="col-md-2 text-end">
                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAdvanceSettlementModal{{ $settlement->id }}">
                            <i class="bi bi-trash"></i> Sil
                        </button>
                    </div>
                </div>
            @empty
                <div class="text-center py-5">
                    <i class="bi bi-cash-stack display-4 text-muted d-block mb-3"></i>
                    <p class="text-muted mb-0">Avans mahsuplaşması bulunmuyor</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Ödemeler</h5>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#paymentModal">
            <i class="bi bi-plus-circle me-2"></i>Yeni Ödeme Ekle
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tarih</th>
                            <th>Tutar</th>
                            <th>Yöntem</th>
                            <th>Taksit Seçimi</th>
                            <th>Taksitler</th>
                            <th class="text-end">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($item->payments as $payment)
                            @php
                                $allocations = $payment->allocations ?? collect();
                                $installmentNos = $allocations->map(function($allocation) {
                                    return ($allocation->payroll_installment_id && $allocation->installment) ? $allocation->installment->installment_no : null;
                                })->filter()->unique()->sort()->values();
                                
                                $allocationType = '';
                                $allocationTitle = '';
                                if ($allocations->isEmpty() || $installmentNos->count() == 0) {
                                    $allocationType = 'Otomatik';
                                } elseif ($installmentNos->count() == 1) {
                                    $no = $installmentNos->first();
                                    $allocation = $allocations->first(function($a) use ($no) {
                                        return $a->payroll_installment_id && $a->installment && $a->installment->installment_no == $no;
                                    });
                                    if ($allocation && $allocation->installment) {
                                        $installment = $allocation->installment;
                                        $allocationType = $no . '. Taksit';
                                        $allocationTitle = $installment->title ? $installment->title : ($no == 1 ? '5\'i' : '20\'si');
                                    } else {
                                        $allocationType = $no . '. Taksit';
                                    }
                                } else {
                                    $allocationType = 'Her İkisine Böl';
                                }
                            @endphp
                            <tr>
                                <td>{{ $payment->payment_date->format('d.m.Y') }}</td>
                                <td>{{ number_format($payment->amount, 2) }} ₺</td>
                                <td>
                                    @if($payment->method == 'bank')
                                        <span class="badge bg-primary">Banka</span>
                                    @elseif($payment->method == 'cash')
                                        <span class="badge bg-success">Nakit</span>
                                    @else
                                        <span class="badge bg-secondary">Diğer</span>
                                    @endif
                                </td>
                                <td>
                                    @if($allocationType == 'Her İkisine Böl')
                                        <span class="badge bg-primary">{{ $allocationType }}</span>
                                    @elseif($allocationType == 'Otomatik')
                                        <span class="badge bg-secondary">{{ $allocationType }}</span>
                                    @else
                                        <div>
                                            <span class="badge bg-warning text-dark">{{ $allocationType }}</span>
                                            @if($allocationTitle)
                                                <br><small class="text-muted fw-bold">{{ $allocationTitle }}</small>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($payment->allocations->count() > 0)
                                        @foreach($payment->allocations as $allocation)
                                            @if($allocation->payroll_installment_id && $allocation->installment)
                                                <div class="mb-1">
                                                    <span class="badge bg-info text-dark">
                                                        {{ $allocation->installment->installment_no }}. Taksit
                                                        @if($allocation->installment->title)
                                                            ({{ $allocation->installment->title }})
                                                        @else
                                                            ({{ $allocation->installment->due_date->format('d.m.Y') }})
                                                        @endif
                                                    </span>
                                                    <br>
                                                    <small class="text-muted">{{ number_format($allocation->allocated_amount, 2) }} ₺</small>
                                                </div>
                                            @else
                                                <span class="badge bg-secondary">
                                                    Genel: {{ number_format($allocation->allocated_amount, 2) }} ₺
                                                </span>
                                            @endif
                                        @endforeach
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deletePaymentModal{{ $payment->id }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Henüz ödeme yok</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Payment Modal --}}
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">Yeni Ödeme Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.payroll.add-payment', $item) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="payment_date" class="form-label">Tarih</label>
                        <input type="date" name="payment_date" id="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" required class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="amount" class="form-label">Tutar</label>
                        <input type="number" step="0.01" name="amount" id="amount" value="{{ old('amount') }}" required class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="method" class="form-label">Yöntem</label>
                        <select name="method" id="method" required class="form-select">
                            <option value="bank">Banka</option>
                            <option value="cash">Nakit</option>
                            <option value="other">Diğer</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="allocation_type" class="form-label">Taksit Seçimi</label>
                        <select name="allocation_type" id="allocation_type" required class="form-select">
                            <option value="auto">Otomatik (En Eski Taksit)</option>
                            <option value="installment_1">1. Taksit (5'i)</option>
                            <option value="installment_2">2. Taksit (20'si)</option>
                            <option value="both">Her İkisine Böl</option>
                        </select>
                    </div>
                    <div id="bothAmounts" class="row g-3 mb-3 d-none">
                        <div class="col-md-6">
                            <label for="amount_1" class="form-label">1. Taksit</label>
                            <input type="number" step="0.01" name="amount_1" id="amount_1" value="{{ old('amount_1') }}" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="amount_2" class="form-label">2. Taksit</label>
                            <input type="number" step="0.01" name="amount_2" id="amount_2" value="{{ old('amount_2') }}" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Deduction Modal --}}
<div class="modal fade" id="deductionModal" tabindex="-1" aria-labelledby="deductionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deductionModalLabel">Kesinti Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.payroll.add-deduction', $item) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="deduction_type_id" class="form-label">Kesinti Tipi</label>
                        <select name="deduction_type_id" id="deduction_type_id" required class="form-select">
                            <option value="">Seçiniz</option>
                            @foreach($deductionTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="deduction_amount" class="form-label">Tutar</label>
                        <input type="number" step="0.01" name="amount" id="deduction_amount" value="{{ old('amount') }}" required class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="deduction_installment_id" class="form-label">Taksit Seçimi</label>
                        <select name="installment_id" id="deduction_installment_id" class="form-select">
                            <option value="">Genel (Her İki Taksitten)</option>
                            @foreach($item->installments as $installment)
                                <option value="{{ $installment->id }}">
                                    T{{ $installment->installment_no }}
                                    @if($installment->title)
                                        - {{ $installment->title }}
                                    @endif
                                    ({{ $installment->due_date->format('d.m.Y') }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Hangi taksitten kesileceğini seçin. Boş bırakırsanız genel kesinti olur.</small>
                    </div>
                    <div class="mb-3">
                        <label for="deduction_description" class="form-label">Açıklama</label>
                        <textarea name="description" id="deduction_description" rows="2" class="form-control">{{ old('description') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-warning">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Advance Settlement Modal --}}
<div class="modal fade" id="advanceModal" tabindex="-1" aria-labelledby="advanceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="advanceModalLabel">Avans Mahsup Et</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.payroll.settle-advance', $item) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="advance_id" class="form-label">Avans</label>
                        <select name="advance_id" id="advance_id" required class="form-select">
                            <option value="">Seçiniz</option>
                            @foreach($openAdvances as $advance)
                                <option value="{{ $advance->id }}" data-remaining="{{ $advance->remaining_amount }}">
                                    {{ $advance->advance_date->format('d.m.Y') }} - Kalan: {{ number_format($advance->remaining_amount, 2) }} ₺
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="settled_amount" class="form-label">Mahsup Tutarı</label>
                        <input type="number" step="0.01" name="settled_amount" id="settled_amount" value="{{ old('settled_amount') }}" required class="form-control" max="">
                    </div>
                    <div class="mb-3">
                        <label for="advance_installment_id" class="form-label">Taksit Seçimi</label>
                        <select name="installment_id" id="advance_installment_id" class="form-select">
                            <option value="">Genel (Her İki Taksitten)</option>
                            @foreach($item->installments as $installment)
                                <option value="{{ $installment->id }}">
                                    T{{ $installment->installment_no }}
                                    @if($installment->title)
                                        - {{ $installment->title }}
                                    @endif
                                    ({{ $installment->due_date->format('d.m.Y') }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Hangi taksitten mahsup edileceğini seçin. Boş bırakırsanız genel mahsuplaşma olur.</small>
                    </div>
                    <div class="mb-3">
                        <label for="settled_date" class="form-label">Tarih</label>
                        <input type="date" name="settled_date" id="settled_date" value="{{ old('settled_date', now()->toDateString()) }}" required class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-info">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Delete Payment Modals --}}
@foreach($item->payments as $payment)
<div class="modal fade" id="deletePaymentModal{{ $payment->id }}" tabindex="-1" aria-labelledby="deletePaymentModalLabel{{ $payment->id }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deletePaymentModalLabel{{ $payment->id }}">Ödemeyi Sil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Bu ödemeyi silmek istediğinizden emin misiniz?</p>
                <div class="alert alert-warning">
                    <strong>Tarih:</strong> {{ $payment->payment_date->format('d.m.Y') }}<br>
                    <strong>Tutar:</strong> {{ number_format($payment->amount, 2) }} ₺<br>
                    <strong>Yöntem:</strong> {{ $payment->method == 'bank' ? 'Banka' : ($payment->method == 'cash' ? 'Nakit' : 'Diğer') }}
                </div>
                <p class="text-danger mb-0"><small>Bu işlem geri alınamaz!</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <form action="{{ route('admin.payroll.delete-payment', [$item, $payment]) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Evet, Sil</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endforeach

{{-- Delete Deduction Modals --}}
@foreach($item->deductions as $deduction)
<div class="modal fade" id="deleteDeductionModal{{ $deduction->id }}" tabindex="-1" aria-labelledby="deleteDeductionModalLabel{{ $deduction->id }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteDeductionModalLabel{{ $deduction->id }}">Kesintiyi Sil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Bu kesintiyi silmek istediğinizden emin misiniz?</p>
                <div class="alert alert-warning">
                    <strong>Kesinti Tipi:</strong> {{ $deduction->deductionType->name }}<br>
                    <strong>Tutar:</strong> {{ number_format($deduction->amount, 2) }} ₺<br>
                    @if($deduction->payroll_installment_id && $deduction->installment)
                        <strong>Taksit:</strong> {{ $deduction->installment->installment_no }}. Taksit
                        @if($deduction->installment->title)
                            ({{ $deduction->installment->title }})
                        @endif
                    @else
                        <strong>Taksit:</strong> Genel (Her İki Taksitten)
                    @endif
                </div>
                <p class="text-danger mb-0"><small>Bu işlem geri alınamaz!</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <form action="{{ route('admin.payroll.delete-deduction', [$item, $deduction]) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Evet, Sil</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endforeach

{{-- Delete Advance Settlement Modals --}}
@foreach($item->advanceSettlements as $settlement)
<div class="modal fade" id="deleteAdvanceSettlementModal{{ $settlement->id }}" tabindex="-1" aria-labelledby="deleteAdvanceSettlementModalLabel{{ $settlement->id }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteAdvanceSettlementModalLabel{{ $settlement->id }}">Avans Mahsuplaşmasını Sil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Bu avans mahsuplaşmasını silmek istediğinizden emin misiniz?</p>
                <div class="alert alert-warning">
                    <strong>Avans Tarihi:</strong> {{ $settlement->advance->advance_date->format('d.m.Y') }}<br>
                    <strong>Mahsup Tarihi:</strong> {{ $settlement->settled_date->format('d.m.Y') }}<br>
                    <strong>Tutar:</strong> {{ number_format($settlement->settled_amount, 2) }} ₺<br>
                    @if($settlement->payroll_installment_id && $settlement->installment)
                        <strong>Taksit:</strong> {{ $settlement->installment->installment_no }}. Taksit
                        @if($settlement->installment->title)
                            ({{ $settlement->installment->title }})
                        @endif
                    @else
                        <strong>Taksit:</strong> Genel (Her İki Taksitten)
                    @endif
                </div>
                <p class="text-danger mb-0"><small>Bu işlem geri alınamaz!</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <form action="{{ route('admin.payroll.delete-advance-settlement', [$item, $settlement]) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Evet, Sil</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endforeach

<script>
document.addEventListener('DOMContentLoaded', function() {
    var allocationTypeSelect = document.getElementById('allocation_type');
    var bothAmountsDiv = document.getElementById('bothAmounts');

    function toggleBothAmounts() {
        if (allocationTypeSelect.value === 'both') {
            bothAmountsDiv.classList.remove('d-none');
        } else {
            bothAmountsDiv.classList.add('d-none');
        }
    }

    allocationTypeSelect.addEventListener('change', toggleBothAmounts);
    toggleBothAmounts(); // Initial call to set visibility based on default/old value

    // Advance modal - set max amount based on selected advance
    var advanceSelect = document.getElementById('advance_id');
    var settledAmountInput = document.getElementById('settled_amount');
    
    if (advanceSelect) {
        advanceSelect.addEventListener('change', function() {
            var selectedOption = this.options[this.selectedIndex];
            var remaining = selectedOption.getAttribute('data-remaining');
            if (remaining) {
                settledAmountInput.setAttribute('max', remaining);
                settledAmountInput.value = remaining;
            }
        });
    }
});
</script>
@endsection
