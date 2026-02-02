@extends('layouts.admin')

@section('title', 'Cari Ekstre')
@section('page-title', 'Cari Ekstre')
@section('page-subtitle', $party->name)

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('accounting.reports.party-statement', $party) }}" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Başlangıç Tarihi</label>
                <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Bitiş Tarihi</label>
                <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i>Filtrele
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
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
                        $runningBalance = $statement['opening_balance'] ?? 0;
                    @endphp
                    <tr class="table-info">
                        <td colspan="5"><strong>Açılış Bakiyesi</strong></td>
                        <td class="text-end">
                            <strong>{{ number_format($runningBalance, 2) }} ₺</strong>
                        </td>
                    </tr>
                    @forelse($statement['lines'] ?? [] as $line)
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
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                                <p class="text-muted mb-0">Bu dönemde işlem bulunmuyor</p>
                            </td>
                        </tr>
                    @endforelse
                    <tr class="table-warning">
                        <td colspan="5"><strong>Kapanış Bakiyesi</strong></td>
                        <td class="text-end">
                            <strong>{{ number_format($statement['closing_balance'] ?? 0, 2) }} ₺</strong>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
