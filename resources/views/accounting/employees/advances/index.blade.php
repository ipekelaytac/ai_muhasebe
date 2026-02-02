@extends('layouts.admin')

@section('title', 'Personel Avansları')
@section('page-title', 'Personel Avansları')
@section('page-subtitle', $party->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0">{{ $party->name }} - Avanslar</h5>
        <small class="text-muted">Personel avans kayıtlarını görüntüleyin</small>
    </div>
    <div>
        <a href="{{ route('accounting.parties.show', $party) }}" class="btn btn-secondary me-2">
            <i class="bi bi-arrow-left me-1"></i>
            Cariye Dön
        </a>
        <a href="{{ route('accounting.employees.advances.create', $party) }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>
            Avans Ver
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0">Açık Avanslar</h6>
    </div>
    <div class="card-body">
        @if(count($openAdvances) > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Belge No</th>
                            <th>Tarih</th>
                            <th class="text-end">Tutar</th>
                            <th class="text-end">Ödenen</th>
                            <th class="text-end">Kalan</th>
                            <th>Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($openAdvances as $advance)
                            <tr>
                                <td><code>{{ $advance['document_number'] }}</code></td>
                                <td>{{ \Carbon\Carbon::parse($advance['document_date'])->format('d.m.Y') }}</td>
                                <td class="text-end">{{ number_format($advance['total_amount'], 2) }} ₺</td>
                                <td class="text-end">{{ number_format($advance['paid_amount'], 2) }} ₺</td>
                                <td class="text-end fw-bold text-danger">{{ number_format($advance['unpaid_amount'], 2) }} ₺</td>
                                <td>
                                    @if($advance['unpaid_amount'] > 0)
                                        <span class="badge bg-warning">Açık</span>
                                    @else
                                        <span class="badge bg-success">Kapalı</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted mb-0">Açık avans bulunmamaktadır.</p>
        @endif
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0">Tüm Avanslar</h6>
    </div>
    <div class="card-body">
        @if($advances->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Belge No</th>
                            <th>Tarih</th>
                            <th class="text-end">Tutar</th>
                            <th class="text-end">Ödenen</th>
                            <th class="text-end">Kalan</th>
                            <th>Durum</th>
                            <th>Açıklama</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($advances as $advance)
                            <tr>
                                <td><code>{{ $advance->document_number }}</code></td>
                                <td>{{ $advance->document_date->format('d.m.Y') }}</td>
                                <td class="text-end">{{ number_format($advance->total_amount, 2) }} ₺</td>
                                <td class="text-end">{{ number_format($advance->paid_amount, 2) }} ₺</td>
                                <td class="text-end fw-bold {{ $advance->unpaid_amount > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($advance->unpaid_amount, 2) }} ₺
                                </td>
                                <td>
                                    <span class="badge bg-{{ $advance->status === 'settled' ? 'success' : ($advance->status === 'partial' ? 'warning' : 'secondary') }}">
                                        {{ $advance->status_label }}
                                    </span>
                                </td>
                                <td>{{ $advance->description }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                {{ $advances->appends(request()->query())->links('vendor.pagination.bootstrap-5') }}
            </div>
        @else
            <p class="text-muted mb-0">Henüz avans kaydı bulunmamaktadır.</p>
        @endif
    </div>
</div>
@endsection
