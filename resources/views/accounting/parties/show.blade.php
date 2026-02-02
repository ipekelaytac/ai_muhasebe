@extends('layouts.admin')

@section('title', 'Cari Detay')
@section('page-title', $party->name)
@section('page-subtitle', 'Cari hesap detayı ve ekstre')

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Cari Bilgileri</h6>
                <div>
                    @if($party->type === 'employee')
                        <a href="{{ route('accounting.employees.advances.index', $party) }}" class="btn btn-sm btn-outline-warning me-2">
                            <i class="bi bi-cash-coin me-1"></i>Avanslar
                        </a>
                    @endif
                    <a href="{{ route('accounting.parties.edit', $party) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil me-1"></i>Düzenle
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <strong>Kod:</strong> <code>{{ $party->code }}</code>
                    </div>
                    <div class="col-md-6">
                        <strong>Tip:</strong> <span class="badge bg-secondary">{{ $party->type_label }}</span>
                    </div>
                    @if($party->tax_number)
                        <div class="col-md-6">
                            <strong>Vergi No:</strong> {{ $party->tax_number }}
                        </div>
                    @endif
                    @if($party->phone)
                        <div class="col-md-6">
                            <strong>Telefon:</strong> {{ $party->phone }}
                        </div>
                    @endif
                    @if($party->email)
                        <div class="col-md-6">
                            <strong>E-posta:</strong> {{ $party->email }}
                        </div>
                    @endif
                    @if($party->address)
                        <div class="col-md-12">
                            <strong>Adres:</strong> {{ $party->address }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Cari Ekstre</h6>
                <div>
                    <a href="{{ route('accounting.reports.party-statement', $party) }}" class="btn btn-sm btn-outline-info">
                        <i class="bi bi-file-earmark-text me-1"></i>Detaylı Rapor
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tarih</th>
                                <th>Tip</th>
                                <th>Belge/Ödeme No</th>
                                <th class="text-end">Borç</th>
                                <th class="text-end">Alacak</th>
                                <th class="text-end">Bakiye</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $runningBalance = $statement['opening_balance'];
                            @endphp
                            <tr class="table-info">
                                <td colspan="5"><strong>Açılış Bakiyesi</strong></td>
                                <td class="text-end">
                                    <strong>{{ number_format($runningBalance, 2) }} ₺</strong>
                                </td>
                            </tr>
                            @foreach($statement['lines'] as $line)
                                @php
                                    if ($line['type'] === 'document') {
                                        if ($line['debit'] > 0) {
                                            $runningBalance += $line['debit'];
                                        } else {
                                            $runningBalance -= $line['credit'];
                                        }
                                    } else {
                                        $runningBalance += $line['debit'] - $line['credit'];
                                    }
                                @endphp
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($line['date'])->format('d.m.Y') }}</td>
                                    <td>
                                        @if($line['type'] === 'document')
                                            <span class="badge bg-primary">Belge</span>
                                        @else
                                            <span class="badge bg-success">Ödeme</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($line['type'] === 'document')
                                            <a href="{{ route('accounting.documents.show', $line['document_id']) }}" class="text-decoration-none">
                                                {{ $line['reference'] }}
                                            </a>
                                        @else
                                            <a href="{{ route('accounting.payments.show', $line['payment_id']) }}" class="text-decoration-none">
                                                {{ $line['reference'] }}
                                            </a>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($line['credit'] > 0)
                                            <span class="text-danger">{{ number_format($line['credit'], 2) }} ₺</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($line['debit'] > 0)
                                            <span class="text-success">{{ number_format($line['debit'], 2) }} ₺</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($runningBalance > 0)
                                            <span class="text-success fw-bold">+{{ number_format($runningBalance, 2) }} ₺</span>
                                        @elseif($runningBalance < 0)
                                            <span class="text-danger fw-bold">{{ number_format($runningBalance, 2) }} ₺</span>
                                        @else
                                            <span class="text-muted">0,00 ₺</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="table-warning">
                                <td colspan="5"><strong>Kapanış Bakiyesi</strong></td>
                                <td class="text-end">
                                    <strong>{{ number_format($statement['closing_balance'], 2) }} ₺</strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0">Bakiye Özeti</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Alacak:</span>
                        <strong class="text-success">{{ number_format($party->receivable_balance, 2) }} ₺</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Borç:</span>
                        <strong class="text-danger">{{ number_format($party->payable_balance, 2) }} ₺</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold">Net Bakiye:</span>
                        @php
                            $balance = $party->balance;
                        @endphp
                        @if($balance > 0)
                            <strong class="text-success">+{{ number_format($balance, 2) }} ₺</strong>
                        @elseif($balance < 0)
                            <strong class="text-danger">{{ number_format($balance, 2) }} ₺</strong>
                        @else
                            <strong class="text-muted">0,00 ₺</strong>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0">Hızlı İşlemler</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('accounting.documents.create', ['party_id' => $party->id]) }}" class="btn btn-primary">
                        <i class="bi bi-file-earmark-plus me-1"></i>Yeni Tahakkuk
                    </a>
                    <a href="{{ route('accounting.payments.create', ['party_id' => $party->id]) }}" class="btn btn-success">
                        <i class="bi bi-cash-coin me-1"></i>Yeni Ödeme/Tahsilat
                    </a>
                </div>
            </div>
        </div>
        
        @if($openDocuments->count() > 0)
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0">Açık Belgeler ({{ $openDocuments->count() }})</h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @foreach($openDocuments->take(10) as $doc)
                            <a href="{{ route('accounting.documents.show', $doc) }}" class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>{{ $doc->document_number }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $doc->type_label }}</small>
                                    </div>
                                    <div class="text-end">
                                        <strong>{{ number_format($doc->unpaid_amount, 2) }} ₺</strong>
                                        <br>
                                        <small class="text-muted">{{ $doc->due_date ? $doc->due_date->format('d.m.Y') : '-' }}</small>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
