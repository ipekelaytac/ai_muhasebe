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

{{-- Compact Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-2 col-sm-4 col-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-3 text-center">
                <h6 class="card-subtitle text-muted mb-1 small">Net Maaş</h6>
                <p class="card-text fs-5 fw-bold text-primary mb-0">{{ number_format($item->base_net_salary, 2) }} ₺</p>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4 col-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-3 text-center">
                <h6 class="card-subtitle text-muted mb-1 small">Yemek</h6>
                <p class="card-text fs-5 fw-bold text-info mb-0">{{ number_format($item->meal_allowance, 2) }} ₺</p>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4 col-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-3 text-center">
                <h6 class="card-subtitle text-muted mb-1 small">Mesai</h6>
                <p class="card-text fs-5 fw-bold text-success mb-0">{{ number_format($item->overtime_total ?? 0, 2) }} ₺</p>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4 col-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-3 text-center">
                <h6 class="card-subtitle text-muted mb-1 small">Kesintiler</h6>
                <p class="card-text fs-5 fw-bold text-warning mb-0">{{ number_format($item->deduction_total, 2) }} ₺</p>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4 col-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-3 text-center">
                <h6 class="card-subtitle text-muted mb-1 small">Avans</h6>
                <p class="card-text fs-5 fw-bold text-secondary mb-0">{{ number_format($item->advances_deducted_total, 2) }} ₺</p>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4 col-6">
        <div class="card border-0 shadow-sm bg-primary text-white">
            <div class="card-body p-3 text-center">
                <h6 class="card-subtitle mb-1 small opacity-75">Net Ödenecek</h6>
                <p class="card-text fs-5 fw-bold mb-0">{{ number_format($item->net_payable, 2) }} ₺</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-4 col-6">
        <div class="card border-0 shadow-sm bg-success text-white">
            <div class="card-body p-3 text-center">
                <h6 class="card-subtitle mb-1 small opacity-75">Toplam Ödenen</h6>
                <p class="card-text fs-5 fw-bold mb-0">{{ number_format($item->total_paid, 2) }} ₺</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-4 col-6">
        <div class="card border-0 shadow-sm bg-danger text-white">
            <div class="card-body p-3 text-center">
                <h6 class="card-subtitle mb-1 small opacity-75">Kalan</h6>
                <p class="card-text fs-5 fw-bold mb-0">{{ number_format($item->total_remaining, 2) }} ₺</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-4 col-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-3 text-center">
                <h6 class="card-subtitle text-muted mb-1 small">Borç Ödemeleri</h6>
                <p class="card-text fs-5 fw-bold text-danger mb-0">{{ number_format($item->debt_payments_total ?? 0, 2) }} ₺</p>
            </div>
        </div>
    </div>
</div>

{{-- Installment Details --}}
{{-- Warning if documents are missing --}}
@if($item->installments->where('accounting_document_id', null)->count() > 0)
<div class="alert alert-warning mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Uyarı:</strong> Bu bordro kalemi için muhasebe belgeleri oluşturulmamış. 
            Ödeme yapabilmek için önce belgeleri oluşturun.
        </div>
        <form method="POST" action="{{ route('admin.payroll.create-documents', $item) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-warning btn-sm">
                <i class="bi bi-file-earmark-plus me-1"></i>Muhasebe Belgelerini Oluştur
            </button>
        </form>
    </div>
</div>
@endif

<div class="row g-4 mb-4">
    @forelse($item->installments as $installment)
        @php
            $payments = $installmentPayments[$installment->installment_no] ?? collect([]);
            $hasDocument = $installment->accounting_document_id && $installment->document;
            $isLocked = $hasDocument && $installment->document->isInLockedPeriod();
        @endphp
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100 {{ $isLocked ? 'border-warning' : '' }}">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-calendar-check me-2"></i>{{ $installment->title ?? 'Taksit ' . $installment->installment_no }}
                    </h5>
                    @if($isLocked)
                        <span class="badge bg-warning text-dark">
                            <i class="bi bi-lock me-1"></i>Kilitli Dönem
                        </span>
                    @endif
                </div>
                <div class="card-body">
                    <dl class="row mb-3">
                        <dt class="col-sm-5">Vade Tarihi:</dt>
                        <dd class="col-sm-7">{{ $installment->due_date->format('d.m.Y') }}</dd>

                        <dt class="col-sm-5">Planlanan Tutar:</dt>
                        <dd class="col-sm-7 fw-bold">{{ number_format($installment->planned_amount, 2) }} ₺</dd>

                        <dt class="col-sm-5">Ödenen Tutar:</dt>
                        <dd class="col-sm-7 fw-bold text-success">{{ number_format($installment->paid_amount, 2) }} ₺</dd>

                        <dt class="col-sm-5">Kalan Tutar:</dt>
                        <dd class="col-sm-7">
                            <span class="fw-bold fs-5 {{ $installment->remaining_amount < 0 ? 'text-danger' : ($installment->remaining_amount > 0 ? 'text-warning' : 'text-success') }}">
                                {{ number_format($installment->remaining_amount, 2) }} ₺
                            </span>
                        </dd>
                        
                        @if($hasDocument)
                            <dt class="col-sm-5">Muhasebe Belge No:</dt>
                            <dd class="col-sm-7">
                                <a href="{{ route('accounting.documents.show', $installment->document->id) }}" class="text-decoration-none" target="_blank">
                                    {{ $installment->document->document_number }}
                                    <i class="bi bi-box-arrow-up-right ms-1"></i>
                                </a>
                            </dd>
                        @else
                            <dt class="col-sm-5">Durum:</dt>
                            <dd class="col-sm-7">
                                <span class="badge bg-danger">Muhasebe belgesi oluşturulmamış</span>
                            </dd>
                        @endif
                    </dl>

                    {{-- Payment Button --}}
                    @if($hasDocument && !$isLocked)
                        <div class="mb-3">
                            <a href="{{ route('accounting.payments.create') }}?party_id={{ $item->employee->party_id }}&document_id={{ $installment->accounting_document_id }}&suggested_amount={{ $installment->remaining_amount }}&context=payroll&payroll_item_id={{ $item->id }}&installment_no={{ $installment->installment_no }}" 
                               class="btn btn-primary btn-sm w-100">
                                <i class="bi bi-cash-coin me-2"></i>Muhasebede Ödeme Yap ({{ $installment->title }})
                            </a>
                        </div>
                    @elseif($isLocked)
                        <div class="alert alert-warning mb-0 py-2">
                            <small><i class="bi bi-lock me-1"></i>Kilitli dönem - ödeme yapılamaz. Ters kayıt kullanın.</small>
                        </div>
                    @endif

                    {{-- Payments List --}}
                    @if($payments->count() > 0)
                        <hr class="my-3">
                        <h6 class="mb-2"><i class="bi bi-list-ul me-1"></i>Yapılan Ödemeler</h6>
                        <div class="list-group list-group-flush">
                            @foreach($payments as $payment)
                                @php
                                    $allocation = $payment->allocations->first();
                                    $allocatedAmount = $payment->allocations->sum('amount');
                                @endphp
                                <div class="list-group-item px-0 py-2 border-0 border-bottom">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="fw-medium">
                                                <a href="{{ route('accounting.payments.show', $payment->id) }}" class="text-decoration-none" target="_blank">
                                                    {{ $payment->payment_number }}
                                                    <i class="bi bi-box-arrow-up-right ms-1"></i>
                                                </a>
                                            </div>
                                            <small class="text-muted">{{ $payment->payment_date->format('d.m.Y') }}</small>
                                            <div>
                                                @if($payment->type == 'bank_out' || $payment->type == 'bank_in')
                                                    <span class="badge bg-primary">Banka</span>
                                                @elseif($payment->type == 'cash_out' || $payment->type == 'cash_in')
                                                    <span class="badge bg-success">Nakit</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ \App\Domain\Accounting\Enums\PaymentType::getLabel($payment->type) }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold text-success">{{ number_format($allocatedAmount, 2) }} ₺</div>
                                            <small class="text-muted">Toplam: {{ number_format($payment->amount, 2) }} ₺</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Deductions for this installment --}}
                    @if($installment->deductions->count() > 0)
                        <hr class="my-3">
                        <h6 class="mb-2"><i class="bi bi-dash-circle me-1"></i>Kesintiler</h6>
                        @foreach($installment->deductions as $deduction)
                            <div class="mb-2">
                                <span class="badge bg-warning text-dark me-1">
                                    {{ $deduction->deductionType->name }}
                                </span>
                                <span class="fw-medium">{{ number_format($deduction->amount, 2) }} ₺</span>
                            </div>
                        @endforeach
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

@if((isset($overtimeDocuments) && $overtimeDocuments->count() > 0) || (isset($legacyOvertimes) && $legacyOvertimes->count() > 0))
<div class="mb-4">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Mesailer</h5>
            @if($item->employee->party_id)
                <a href="{{ route('accounting.overtime.create', ['party_id' => $item->employee->party_id]) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>Mesai Ekle
                </a>
            @endif
        </div>
        <div class="card-body">
            @if(isset($overtimeDocuments) && $overtimeDocuments->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Belge No</th>
                                <th>Belge Tarihi</th>
                                <th>Mesai Tarihi</th>
                                <th>Açıklama</th>
                                <th class="text-end">Tutar</th>
                                <th class="text-end">Ödenen</th>
                                <th class="text-end">Kalan</th>
                                <th class="text-center">Durum</th>
                                <th class="text-center">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($overtimeDocuments as $overtimeDoc)
                                @php
                                    $overtimeDate = null;
                                    $hours = null;
                                    $rate = null;
                                    if ($overtimeDoc->notes) {
                                        $notes = explode("\n", $overtimeDoc->notes);
                                        foreach ($notes as $note) {
                                            if (strpos($note, 'Mesai Tarihi:') !== false) {
                                                $overtimeDate = trim(str_replace('Mesai Tarihi:', '', $note));
                                            }
                                            if (strpos($note, 'Saat:') !== false) {
                                                $hours = trim(str_replace('Saat:', '', $note));
                                            }
                                            if (strpos($note, 'Saatlik Ücret:') !== false) {
                                                $rate = trim(str_replace('Saatlik Ücret:', '', str_replace('₺', '', $note)));
                                            }
                                        }
                                    }
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('accounting.overtime.show', $overtimeDoc) }}" class="text-decoration-none">
                                            <strong>{{ $overtimeDoc->document_number }}</strong>
                                        </a>
                                    </td>
                                    <td>{{ $overtimeDoc->document_date->format('d.m.Y') }}</td>
                                    <td>{{ $overtimeDate ? \Carbon\Carbon::parse($overtimeDate)->format('d.m.Y') : '-' }}</td>
                                    <td>
                                        {{ $overtimeDoc->description }}
                                        @if($hours)
                                            <br><small class="text-muted">{{ $hours }} saat</small>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold">{{ number_format($overtimeDoc->total_amount, 2) }} ₺</td>
                                    <td class="text-end text-success">{{ number_format($overtimeDoc->paid_amount, 2) }} ₺</td>
                                    <td class="text-end {{ $overtimeDoc->unpaid_amount > 0 ? 'text-warning' : 'text-success' }}">
                                        {{ number_format($overtimeDoc->unpaid_amount, 2) }} ₺
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $overtimeDoc->status == 'settled' ? 'success' : ($overtimeDoc->status == 'partial' ? 'warning' : 'secondary') }}">
                                            {{ \App\Domain\Accounting\Enums\DocumentStatus::getLabel($overtimeDoc->status) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('accounting.overtime.show', $overtimeDoc) }}" class="btn btn-outline-primary" title="Detay">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            @if($overtimeDoc->unpaid_amount > 0)
                                                <button type="button" 
                                                        class="btn btn-success" 
                                                        title="Ödeme Yap"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#overtimePaymentModal"
                                                        data-overtime-id="{{ $overtimeDoc->id }}"
                                                        data-overtime-number="{{ $overtimeDoc->document_number }}"
                                                        data-overtime-amount="{{ $overtimeDoc->unpaid_amount }}"
                                                        data-party-id="{{ $item->employee->party_id }}">
                                                    <i class="bi bi-cash-coin"></i>
                                                </button>
                                            @else
                                                <button class="btn btn-outline-secondary" disabled title="Ödendi">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="4" class="text-end">Toplam Mesai Ücreti:</th>
                                <th class="text-end text-success">{{ number_format($item->overtime_total ?? 0, 2) }} ₺</th>
                                <th colspan="4"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @elseif(isset($legacyOvertimes) && $legacyOvertimes->count() > 0)
                {{-- Legacy overtime records (deprecated) --}}
                <div class="alert alert-info mb-3">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Bilgi:</strong> Bu mesailer eski sistemden geliyor. Yeni mesai girişi için muhasebe sistemini kullanın.
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tarih</th>
                                <th>Başlangıç</th>
                                <th>Bitiş</th>
                                <th>Saat</th>
                                <th>Saatlik Ücret</th>
                                <th class="text-end">Tutar</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($legacyOvertimes as $overtime)
                                <tr>
                                    <td>{{ $overtime->overtime_date->format('d.m.Y') }}</td>
                                    <td>{{ date('H:i', strtotime($overtime->start_time)) }}</td>
                                    <td>{{ date('H:i', strtotime($overtime->end_time)) }}</td>
                                    <td class="fw-bold">{{ number_format($overtime->hours, 2) }} saat</td>
                                    <td>{{ number_format($overtime->rate, 2) }} ₺</td>
                                    <td class="text-end fw-bold text-success">{{ number_format($overtime->amount, 2) }} ₺</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="5" class="text-end">Toplam Mesai Ücreti:</th>
                                <th class="text-end text-success">{{ number_format($item->overtime_total ?? 0, 2) }} ₺</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@else
    @if($item->employee->party_id)
        <div class="mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-4">
                    <i class="bi bi-clock-history display-6 text-muted mb-3"></i>
                    <p class="text-muted mb-3">Bu dönem için mesai kaydı bulunmuyor.</p>
                    <a href="{{ route('accounting.overtime.create', ['party_id' => $item->employee->party_id]) }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Mesai Ekle
                    </a>
                </div>
            </div>
        </div>
    @endif
@endif

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
            <h5 class="mb-0">Borç Ödemeleri</h5>
            <div>
                @if($item->employee->party_id)
                    <a href="{{ route('accounting.employees.debts.create', ['party_id' => $item->employee->party_id, 'payroll_item_id' => $item->id]) }}" class="btn btn-outline-danger btn-sm me-2">
                        <i class="bi bi-plus-circle me-1"></i>Borç Ekle
                    </a>
                @endif
                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#debtPaymentModal">
                    <i class="bi bi-cash-coin me-2"></i>Borç Ödemesi Yap
                </button>
            </div>
        </div>
        <div class="card-body">
            @forelse($item->debtPayments as $debtPayment)
                <div class="row g-3 align-items-center border-bottom pb-3 mb-3">
                    <div class="col-md-3">
                        <div class="fw-bold text-danger">{{ $debtPayment->employeeDebt->debt_date->format('d.m.Y') }} Borcu</div>
                        <small class="text-muted">{{ $debtPayment->employeeDebt->description ?? 'Açıklama yok' }}</small>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-1">
                            <small class="text-muted d-block">Ödeme Tarihi:</small>
                            <div>{{ $debtPayment->payment_date->format('d.m.Y') }}</div>
                        </div>
                    </div>
                    <div class="col-md-2 text-end">
                        <div class="mb-1">
                            <small class="text-muted d-block">Tutar:</small>
                            <span class="fw-bold fs-5 text-danger">{{ number_format($debtPayment->amount, 2) }} ₺</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-1">
                            <small class="text-muted d-block">Kalan Borç:</small>
                            <span class="fw-bold {{ $debtPayment->employeeDebt->remaining_amount > 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($debtPayment->employeeDebt->remaining_amount, 2) }} ₺
                            </span>
                        </div>
                    </div>
                    <div class="col-md-2 text-end">
                        <form action="{{ route('admin.payroll.delete-debt-payment', [$item, $debtPayment]) }}" method="POST" class="d-inline" onsubmit="return confirm('Bu borç ödemesini silmek istediğinize emin misiniz?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i> Sil
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="text-center py-5">
                    <i class="bi bi-credit-card display-4 text-muted d-block mb-3"></i>
                    <p class="text-muted mb-0">Borç ödemesi bulunmuyor</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

{{-- Quick Actions --}}
@if($item->employee->party_id)
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="bi bi-lightning-charge me-2"></i>Hızlı İşlemler</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <a href="{{ route('accounting.employees.advances.create', $item->employee->party_id) }}" class="btn btn-info w-100">
                    <i class="bi bi-cash-coin me-2"></i>Avans Ver
                </a>
            </div>
            <div class="col-md-4">
                @if($item->installments->where('accounting_document_id', '!=', null)->count() > 0 && isset($openAdvances) && $openAdvances->count() > 0)
                    <button type="button" class="btn btn-warning w-100" data-bs-toggle="modal" data-bs-target="#advanceDeductionModal">
                        <i class="bi bi-arrow-left-right me-2"></i>Avans Mahsup Et
                    </button>
                @else
                    <button type="button" class="btn btn-warning w-100" disabled 
                            title="@if(!isset($openAdvances) || $openAdvances->count() == 0)Açık avans bulunmuyor@else Önce muhasebe belgeleri oluşturulmalı@endif">
                        <i class="bi bi-arrow-left-right me-2"></i>Avans Mahsup Et
                    </button>
                @endif
            </div>
            <div class="col-md-4">
                <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#deductionModal">
                    <i class="bi bi-dash-circle me-2"></i>Kesinti Gir
                </button>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Legacy PayrollPayment modal removed - payments now handled via Accounting system --}}

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

{{-- Advance Deduction Modal --}}
@if($item->employee->party_id)
<div class="modal fade" id="advanceDeductionModal" tabindex="-1" aria-labelledby="advanceDeductionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="advanceDeductionModalLabel">Avans Mahsup Et</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            @php
                $firstInstallmentDoc = $item->installments->where('accounting_document_id', '!=', null)->first();
            @endphp
            @if($firstInstallmentDoc && isset($openAdvances) && $openAdvances->count() > 0)
            @php
                $baseUrl = route('accounting.payroll.deductions.store', $firstInstallmentDoc->accounting_document_id);
                $baseUrlPattern = str_replace('/' . $firstInstallmentDoc->accounting_document_id, '/__DOC_ID__', $baseUrl);
            @endphp
            <form method="POST" id="advanceDeductionForm" action="{{ $baseUrl }}" data-base-url-pattern="{{ $baseUrlPattern }}">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Avans mahsuplaşması, maaş taksitlerinden düşülecek ve avans belgesi kapatılacaktır.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Açık Avanslar</label>
                        <div class="list-group">
                            @foreach($openAdvances as $advanceDoc)
                                <div class="list-group-item">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="advance_document_ids[]" value="{{ $advanceDoc->id }}" id="advance_{{ $advanceDoc->id }}">
                                        <label class="form-check-label w-100" for="advance_{{ $advanceDoc->id }}">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <strong>{{ $advanceDoc->document_number }}</strong>
                                                    <br>
                                                    <small class="text-muted">{{ $advanceDoc->document_date->format('d.m.Y') }}</small>
                                                </div>
                                                <div class="text-end">
                                                    <strong>{{ number_format($advanceDoc->unpaid_amount, 2) }} ₺</strong>
                                                    <br>
                                                    <small class="text-muted">Kalan</small>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="mt-2 ms-4">
                                        <label class="form-label small">Mahsup Tutarı:</label>
                                        <input type="number" step="0.01" name="advance_amounts[{{ $advanceDoc->id }}]" 
                                               value="{{ $advanceDoc->unpaid_amount }}" 
                                               max="{{ $advanceDoc->unpaid_amount }}"
                                               class="form-control form-control-sm">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="advance_installment_id" class="form-label">Hangi Taksitten Düşülecek?</label>
                        <select name="installment_id" id="advance_installment_id" class="form-select" required>
                            <option value="">Seçiniz</option>
                            @foreach($item->installments as $installment)
                                @if($installment->accounting_document_id)
                                    <option value="{{ $installment->accounting_document_id }}" 
                                            data-base-url="{{ route('accounting.payroll.deductions.store', ['salaryDocument' => '__DOC_ID__']) }}">
                                        {{ $installment->title ?? 'Taksit ' . $installment->installment_no }}
                                        (Kalan: {{ number_format($installment->remaining_amount, 2) }} ₺)
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        <small class="text-muted">Seçtiğiniz taksitten avans kesintisi yapılacaktır.</small>
                    </div>
                    
                    <input type="hidden" name="payroll_item_id" value="{{ $item->id }}">
                    <input type="hidden" name="party_id" value="{{ $item->employee->party_id }}">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-warning">Mahsup Et</button>
                </div>
            </form>
            @else
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        @if(!isset($openAdvances) || $openAdvances->count() == 0)
                            Bu çalışan için açık avans bulunmuyor. Önce avans vermeniz gerekiyor.
                        @else
                            Bu bordro kalemi için muhasebe belgesi bulunamadı. Lütfen bordroyu yeniden oluşturun.
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                </div>
            @endif
        </div>
    </div>
</div>
@endif

{{-- Legacy PayrollPayment delete modals removed - payments now handled via Accounting system --}}

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

{{-- Legacy delete advance settlement modals removed - migrated to new accounting system --}}

{{-- Overtime Payment Modal --}}
<div class="modal fade" id="overtimePaymentModal" tabindex="-1" aria-labelledby="overtimePaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="overtimePaymentModalLabel">
                    <i class="bi bi-cash-coin me-2"></i>Mesai Ödemesi Yap
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="overtimePaymentForm" method="POST" action="{{ route('accounting.payments.store') }}">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="allocation_document_id" id="overtime_document_id">
                    <input type="hidden" name="party_id" id="overtime_party_id">
                    <input type="hidden" name="context" value="payroll">
                    <input type="hidden" name="payroll_item_id" value="{{ $item->id }}">
                    <input type="hidden" name="branch_id" value="{{ $item->payrollPeriod->branch_id }}">
                    
                    <div class="alert alert-info mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong id="overtime_document_number">-</strong>
                                <br>
                                <small class="text-muted">Kalan Tutar: <span id="overtime_unpaid_amount">0</span> ₺</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Ödeme Tipi <span class="text-danger">*</span></label>
                        <select name="type" id="overtime_payment_type" class="form-select" required>
                            <option value="">Seçiniz</option>
                            <optgroup label="Çıkışlar">
                                <option value="cash_out">Kasa Çıkışı</option>
                                <option value="bank_out">Banka Çıkışı</option>
                            </optgroup>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="overtime_cashbox_field">
                        <label class="form-label">Kasa</label>
                        <select name="cashbox_id" id="overtime_cashbox_id" class="form-select">
                            <option value="">Seçiniz</option>
                            @foreach(\App\Domain\Accounting\Models\Cashbox::where('company_id', $item->payrollPeriod->company_id)->active()->get() as $cashbox)
                                <option value="{{ $cashbox->id }}">{{ $cashbox->name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback d-none" id="overtime_cashbox_error">Kasa ödemeleri için kasa seçilmelidir.</div>
                    </div>
                    
                    <div class="mb-3" id="overtime_bank_field">
                        <label class="form-label">Banka</label>
                        <select name="bank_account_id" id="overtime_bank_id" class="form-select">
                            <option value="">Seçiniz</option>
                            @foreach(\App\Domain\Accounting\Models\BankAccount::where('company_id', $item->payrollPeriod->company_id)->active()->get() as $bank)
                                <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback d-none" id="overtime_bank_error">Banka ödemeleri için banka hesabı seçilmelidir.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Ödeme Tarihi <span class="text-danger">*</span></label>
                        <input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tutar <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="overtime_amount" class="form-control" step="0.01" min="0.01" required>
                        <small class="text-muted">Kalan tutar otomatik dolduruldu</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Mesai ödemesi"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i>Ödeme Yap
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Debt Payment Modal --}}
<div class="modal fade" id="debtPaymentModal" tabindex="-1" aria-labelledby="debtPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="debtPaymentModalLabel">Borç Ödemesi Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            @if(isset($openDebts) && $openDebts->count() > 0)
            <form method="POST" action="{{ route('admin.payroll.add-debt-payment', $item) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="debt_document_id" class="form-label">Borç <span class="text-danger">*</span></label>
                        <select name="document_id" id="debt_document_id" required class="form-select">
                            <option value="">Seçiniz</option>
                            @foreach($openDebts as $debt)
                                @php
                                    // Support both old EmployeeDebt and new Document models
                                    $debtDate = $debt->debt_date ?? $debt->document_date;
                                    $debtAmount = $debt->amount ?? $debt->total_amount;
                                    $remainingAmount = $debt->remaining_amount ?? $debt->unpaid_amount;
                                    $debtId = $debt->id;
                                    $isDocument = $debt instanceof \App\Domain\Accounting\Models\Document;
                                @endphp
                                <option value="{{ $debtId }}" 
                                        data-remaining="{{ $remainingAmount }}" 
                                        data-is-document="{{ $isDocument ? '1' : '0' }}"
                                        @if(!$isDocument) data-employee-debt-id="{{ $debtId }}" @endif>
                                    {{ $debtDate->format('d.m.Y') }} - {{ number_format($debtAmount, 2) }} ₺ (Kalan: {{ number_format($remainingAmount, 2) }} ₺)
                                    @if($isDocument)
                                        - {{ $debt->document_number }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Yeni muhasebe sistemindeki borçlar belge numarası ile gösterilir</small>
                        {{-- Hidden field for legacy system support --}}
                        <input type="hidden" name="employee_debt_id" id="employee_debt_id" value="">
                    </div>
                    <div class="mb-3">
                        <label for="debt_payment_amount" class="form-label">Ödeme Tutarı <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="amount" id="debt_payment_amount" step="0.01" min="0.01" required class="form-control" placeholder="0.00">
                            <span class="input-group-text">₺</span>
                        </div>
                        <small class="text-muted" id="debt_max_amount_info"></small>
                    </div>
                    <div class="mb-3">
                        <label for="debt_payment_date" class="form-label">Ödeme Tarihi <span class="text-danger">*</span></label>
                        <input type="date" name="payment_date" id="debt_payment_date" value="{{ date('Y-m-d') }}" required class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="debt_payment_notes" class="form-label">Notlar</label>
                        <textarea name="notes" id="debt_payment_notes" rows="2" class="form-control" placeholder="Notlar"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-danger">Borç Ödemesi Ekle</button>
                </div>
            </form>
            @else
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Bu çalışan için açık borç bulunmuyor. Önce borç tanımlamanız gerekiyor.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                @if($item->employee->party_id)
                    <a href="{{ route('accounting.employees.debts.create', ['party_id' => $item->employee->party_id, 'payroll_item_id' => $item->id]) }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Yeni Borç Ekle
                    </a>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>

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

    // Debt payment modal - set max amount based on selected debt
    var debtSelect = document.getElementById('debt_document_id');
    var debtAmountInput = document.getElementById('debt_payment_amount');
    var debtMaxAmountInfo = document.getElementById('debt_max_amount_info');
    var employeeDebtIdInput = document.getElementById('employee_debt_id');
    
    if (debtSelect && debtAmountInput && debtMaxAmountInfo) {
        debtSelect.addEventListener('change', function() {
            var selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                var remaining = parseFloat(selectedOption.getAttribute('data-remaining'));
                debtAmountInput.setAttribute('max', remaining);
                debtMaxAmountInfo.textContent = 'Maksimum ödeme tutarı: ' + remaining.toFixed(2) + ' ₺';
                
                // Set employee_debt_id for legacy system if needed
                var isDocument = selectedOption.getAttribute('data-is-document') === '1';
                if (!isDocument && employeeDebtIdInput) {
                    var legacyDebtId = selectedOption.getAttribute('data-employee-debt-id');
                    if (legacyDebtId) {
                        employeeDebtIdInput.value = legacyDebtId;
                    }
                } else if (employeeDebtIdInput) {
                    employeeDebtIdInput.value = '';
                }
                
                // Show info if it's a new accounting document
                if (isDocument) {
                    debtMaxAmountInfo.textContent += ' (Yeni Muhasebe Sistemi - Ödeme sayfasına yönlendirileceksiniz)';
                }
            } else {
                debtAmountInput.removeAttribute('max');
                debtMaxAmountInfo.textContent = '';
                if (employeeDebtIdInput) {
                    employeeDebtIdInput.value = '';
                }
            }
        });
    }

    // Advance deduction modal - update form action based on selected installment
    var advanceInstallmentSelect = document.getElementById('advance_installment_id');
    var advanceDeductionForm = document.getElementById('advanceDeductionForm');
    
    if (advanceInstallmentSelect && advanceDeductionForm) {
        // Get base URL pattern with placeholder
        var baseUrlPattern = advanceDeductionForm.getAttribute('data-base-url-pattern');
        
        function updateFormAction() {
            var selectedInstallmentId = advanceInstallmentSelect.value;
            if (selectedInstallmentId && baseUrlPattern) {
                // Replace placeholder __DOC_ID__ with actual document ID
                var newAction = baseUrlPattern.replace('__DOC_ID__', selectedInstallmentId);
                advanceDeductionForm.setAttribute('action', newAction);
                console.log('Form action updated to:', newAction, 'for installment:', selectedInstallmentId);
            }
        }
        
        // Update form action when installment selection changes
        advanceInstallmentSelect.addEventListener('change', function() {
            updateFormAction();
            console.log('Installment changed to:', advanceInstallmentSelect.value);
        });
        
        // Validate and update on form submit
        advanceDeductionForm.addEventListener('submit', function(e) {
            var selectedInstallmentId = advanceInstallmentSelect.value;
            if (!selectedInstallmentId) {
                e.preventDefault();
                alert('Lütfen hangi taksitten düşüleceğini seçiniz.');
                return false;
            }
            // CRITICAL: Update form action BEFORE any other processing
            // This ensures the route parameter matches the selected installment
            updateFormAction();
            
            // Verify the action was updated correctly
            var currentAction = advanceDeductionForm.getAttribute('action');
            var expectedId = selectedInstallmentId;
            if (!currentAction.includes(expectedId)) {
                console.error('Form action mismatch! Expected:', expectedId, 'Got:', currentAction);
                // Force update one more time
                var baseUrlPattern = advanceDeductionForm.getAttribute('data-base-url-pattern');
                if (baseUrlPattern) {
                    var correctedAction = baseUrlPattern.replace('__DOC_ID__', expectedId);
                    advanceDeductionForm.setAttribute('action', correctedAction);
                    console.log('Corrected form action to:', correctedAction);
                }
            }
            
            console.log('Form submitting with action:', advanceDeductionForm.getAttribute('action'), 'installment_id:', selectedInstallmentId);
        });
    }
    
    // Overtime Payment Modal
    var overtimePaymentModal = document.getElementById('overtimePaymentModal');
    if (overtimePaymentModal) {
        overtimePaymentModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            if (!button) return;
            
            var overtimeId = button.getAttribute('data-overtime-id');
            var overtimeNumber = button.getAttribute('data-overtime-number');
            var overtimeAmount = button.getAttribute('data-overtime-amount');
            var partyId = button.getAttribute('data-party-id');
            
            // Fill form fields
            var docIdField = document.getElementById('overtime_document_id');
            var partyIdField = document.getElementById('overtime_party_id');
            var docNumberField = document.getElementById('overtime_document_number');
            var unpaidAmountField = document.getElementById('overtime_unpaid_amount');
            var amountField = document.getElementById('overtime_amount');
            
            if (docIdField) docIdField.value = overtimeId || '';
            if (partyIdField) partyIdField.value = partyId || '';
            if (docNumberField) docNumberField.textContent = overtimeNumber || '-';
            if (unpaidAmountField) unpaidAmountField.textContent = parseFloat(overtimeAmount || 0).toFixed(2);
            if (amountField) amountField.value = parseFloat(overtimeAmount || 0).toFixed(2);
            
            // Reset form
            var paymentTypeSelect = document.getElementById('overtime_payment_type');
            var cashboxSelect = document.getElementById('overtime_cashbox_id');
            var bankSelect = document.getElementById('overtime_bank_id');
            
            if (paymentTypeSelect) paymentTypeSelect.value = '';
            if (cashboxSelect) {
                cashboxSelect.value = '';
                cashboxSelect.classList.remove('is-invalid');
            }
            if (bankSelect) {
                bankSelect.value = '';
                bankSelect.classList.remove('is-invalid');
            }
            
            // Clear error messages
            var cashboxError = document.getElementById('overtime_cashbox_error');
            if (cashboxError) cashboxError.classList.add('d-none');
            var bankError = document.getElementById('overtime_bank_error');
            if (bankError) bankError.classList.add('d-none');
        });
        
        // Form submit handler - validate based on payment type
        var overtimePaymentForm = document.getElementById('overtimePaymentForm');
        if (overtimePaymentForm) {
            overtimePaymentForm.addEventListener('submit', function(e) {
                var paymentTypeSelect = document.getElementById('overtime_payment_type');
                var cashboxSelect = document.getElementById('overtime_cashbox_id');
                var bankSelect = document.getElementById('overtime_bank_id');
                
                var paymentType = paymentTypeSelect ? paymentTypeSelect.value : '';
                
                // Validate payment type is selected
                if (!paymentType) {
                    e.preventDefault();
                    alert('Lütfen ödeme tipini seçiniz.');
                    if (paymentTypeSelect) paymentTypeSelect.focus();
                    return false;
                }
                
                // Validate based on payment type
                if (paymentType === 'cash_out') {
                    // For cash_out, cashbox is required
                    if (!cashboxSelect || !cashboxSelect.value) {
                        e.preventDefault();
                        if (cashboxSelect) {
                            cashboxSelect.classList.add('is-invalid');
                            cashboxSelect.focus();
                        }
                        var cashboxError = document.getElementById('overtime_cashbox_error');
                        if (cashboxError) cashboxError.classList.remove('d-none');
                        alert('Kasa ödemeleri için kasa seçilmelidir.');
                        return false;
                    }
                    // Clear bank error if exists
                    var bankError = document.getElementById('overtime_bank_error');
                    if (bankError) bankError.classList.add('d-none');
                } else if (paymentType === 'bank_out') {
                    // For bank_out, bank account is required
                    if (!bankSelect || !bankSelect.value) {
                        e.preventDefault();
                        if (bankSelect) {
                            bankSelect.classList.add('is-invalid');
                            bankSelect.focus();
                        }
                        var bankError = document.getElementById('overtime_bank_error');
                        if (bankError) bankError.classList.remove('d-none');
                        alert('Banka ödemeleri için banka hesabı seçilmelidir.');
                        return false;
                    }
                    // Clear cashbox error if exists
                    var cashboxError = document.getElementById('overtime_cashbox_error');
                    if (cashboxError) cashboxError.classList.add('d-none');
                }
                
                return true;
            });
        }
    }
});
</script>
@endsection
