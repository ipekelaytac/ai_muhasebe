@extends('layouts.admin')

@section('title', 'Bordro Detay')
@section('page-title', $period->period_name)
@section('page-subtitle', $period->company->name . ' - ' . $period->branch->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div></div>
    <div class="btn-group">
        @if($items->isEmpty())
            <form method="POST" action="{{ route('admin.payroll.generate', $period) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-plus-circle me-1"></i>
                    Bordro Oluştur
                </button>
            </form>
        @else
            <a href="{{ route('admin.payroll.add-employee-form', $period) }}" class="btn btn-primary">
                <i class="bi bi-person-plus me-1"></i>
                Personel Ekle
            </a>
        @endif
        <a href="{{ route('admin.payroll.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>
            Geri
        </a>
    </div>
</div>

@if($items->isEmpty())
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>
        Bu dönem için henüz bordro oluşturulmamış. "Bordro Oluştur" butonuna tıklayarak aktif çalışanlar için bordro oluşturabilirsiniz.
    </div>
@else
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Çalışan</th>
                            <th class="text-end">Net Maaş</th>
                            <th class="text-end">Yemek Yardımı</th>
                            <th class="text-end">Net Ödenecek</th>
                            <th class="text-end">Ödenen</th>
                            <th class="text-end">Kalan</th>
                            <th class="text-end">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            <tr>
                                <td class="fw-medium">{{ $item->employee->full_name }}</td>
                                <td class="text-end">{{ number_format($item->base_net_salary, 2) }} ₺</td>
                                <td class="text-end text-info fw-medium">{{ number_format($item->meal_allowance, 2) }} ₺</td>
                                <td class="text-end">{{ number_format($item->net_payable, 2) }} ₺</td>
                                <td class="text-end text-success fw-bold">{{ number_format($item->total_paid, 2) }} ₺</td>
                                <td class="text-end text-danger fw-bold">{{ number_format($item->total_remaining, 2) }} ₺</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.payroll.item', $item) }}" class="btn btn-sm btn-outline-primary">
                                        Detay
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th class="fw-bold">TOPLAM</th>
                            <th class="text-end fw-bold">{{ number_format($items->sum('base_net_salary'), 2) }} ₺</th>
                            <th class="text-end fw-bold text-info">{{ number_format($items->sum('meal_allowance'), 2) }} ₺</th>
                            <th class="text-end fw-bold">{{ number_format($items->sum('net_payable'), 2) }} ₺</th>
                            <th class="text-end fw-bold text-success">{{ number_format($items->sum('total_paid'), 2) }} ₺</th>
                            <th class="text-end fw-bold text-danger">{{ number_format($items->sum('total_remaining'), 2) }} ₺</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endif
@endsection
