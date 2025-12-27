@extends('layouts.admin')

@section('title', 'Raporlar')
@section('page-title', 'Raporlar')
@section('page-subtitle', 'Detaylı analiz ve raporlar')

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reports.index') }}" class="row g-3">
            <div class="col-md-3">
                <label for="year" class="form-label">Yıl</label>
                <select name="year" id="year" class="form-select">
                    @for($y = now()->year; $y >= now()->year - 5; $y--)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-3">
                <label for="month" class="form-label">Ay (Tümü için boş bırakın)</label>
                <select name="month" id="month" class="form-select">
                    <option value="">Tüm Aylar</option>
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                            {{ ['', 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'][$m] }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="col-md-4">
                <label for="type" class="form-label">Rapor Tipi</label>
                <select name="type" id="type" class="form-select">
                    <option value="all" {{ $reportType == 'all' ? 'selected' : '' }}>Tüm Raporlar</option>
                    <option value="payroll" {{ $reportType == 'payroll' ? 'selected' : '' }}>Bordro Raporu</option>
                    <option value="employee" {{ $reportType == 'employee' ? 'selected' : '' }}>Çalışan Raporu</option>
                    <option value="advance" {{ $reportType == 'advance' ? 'selected' : '' }}>Avans Raporu</option>
                    <option value="deduction" {{ $reportType == 'deduction' ? 'selected' : '' }}>Kesinti Raporu</option>
                    <option value="finance" {{ $reportType == 'finance' ? 'selected' : '' }}>Finans Raporu</option>
                    <option value="meal" {{ $reportType == 'meal' ? 'selected' : '' }}>Yemek Yardımı Raporu</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i>
                    Filtrele
                </button>
            </div>
        </form>
    </div>
</div>

@if($reportType == 'all' || $reportType == 'payroll')
    @if(isset($data['payroll']))
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-cash-coin me-2"></i>Bordro Raporu</h5>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <div class="text-muted small mb-1">Toplam Net Maaş</div>
                                <div class="h4 text-primary mb-0">{{ number_format($data['payroll']['total_net_salary'], 2) }} ₺</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <div class="text-muted small mb-1">Yemek Yardımı</div>
                                <div class="h4 text-success mb-0">{{ number_format($data['payroll']['total_meal_allowance'], 2) }} ₺</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <div class="text-muted small mb-1">Toplam Ödenebilir</div>
                                <div class="h4 text-info mb-0">{{ number_format($data['payroll']['total_net_payable'], 2) }} ₺</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <div class="text-muted small mb-1">Kalan Ödeme</div>
                                <div class="h4 {{ $data['payroll']['total_remaining'] > 0 ? 'text-warning' : 'text-success' }} mb-0">
                                    {{ number_format($data['payroll']['total_remaining'], 2) }} ₺
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <div class="text-muted small mb-1">Toplam Bonus</div>
                                <div class="h5 text-success mb-0">{{ number_format($data['payroll']['total_bonus'], 2) }} ₺</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <div class="text-muted small mb-1">Toplam Kesinti</div>
                                <div class="h5 text-danger mb-0">{{ number_format($data['payroll']['total_deduction'], 2) }} ₺</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <div class="text-muted small mb-1">Avans Kesintileri</div>
                                <div class="h5 text-warning mb-0">{{ number_format($data['payroll']['total_advances_deducted'], 2) }} ₺</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <div class="text-muted small mb-1">Çalışan Sayısı</div>
                                <div class="h5 text-primary mb-0">{{ $data['payroll']['employee_count'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endif

@if($reportType == 'all' || $reportType == 'employee')
    @if(isset($data['employee']))
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-people me-2"></i>Çalışan Raporu</h5>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <div class="text-muted small mb-1">Toplam Çalışan</div>
                                <div class="h4 text-primary mb-0">{{ $data['employee']['total'] }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <div class="text-muted small mb-1">Aktif Çalışan</div>
                                <div class="h4 text-success mb-0">{{ $data['employee']['active'] }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <div class="text-muted small mb-1">Pasif Çalışan</div>
                                <div class="h4 text-secondary mb-0">{{ $data['employee']['inactive'] }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <div class="text-muted small mb-1">Sözleşmeli</div>
                                <div class="h4 text-info mb-0">{{ $data['employee']['with_contract'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endif

@if($reportType == 'all' || $reportType == 'advance')
    @if(isset($data['advance']))
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bi bi-cash-stack me-2"></i>Avans Raporu</h5>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <div class="text-muted small mb-1">Toplam Avans</div>
                                <div class="h4 text-primary mb-0">{{ number_format($data['advance']['total_amount'], 2) }} ₺</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <div class="text-muted small mb-1">Ödenen</div>
                                <div class="h4 text-success mb-0">{{ number_format($data['advance']['total_settled'], 2) }} ₺</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <div class="text-muted small mb-1">Kalan</div>
                                <div class="h4 text-warning mb-0">{{ number_format($data['advance']['total_remaining'], 2) }} ₺</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <div class="text-muted small mb-1">Açık Avans</div>
                                <div class="h4 text-danger mb-0">{{ $data['advance']['open_count'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endif

@if($reportType == 'all' || $reportType == 'deduction')
    @if(isset($data['deduction']))
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-tag me-2"></i>Kesinti Raporu</h5>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <div class="text-muted small mb-1">Toplam Kesinti</div>
                                <div class="h4 text-danger mb-0">{{ number_format($data['deduction']['total_amount'], 2) }} ₺</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <div class="text-muted small mb-1">Kesinti Sayısı</div>
                                <div class="h4 text-primary mb-0">{{ $data['deduction']['count'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                @if($data['deduction']['by_type']->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Kesinti Tipi</th>
                                    <th class="text-end">Toplam Tutar</th>
                                    <th class="text-end">Adet</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data['deduction']['by_type'] as $type => $info)
                                    <tr>
                                        <td>{{ $type }}</td>
                                        <td class="text-end">{{ number_format($info['total'], 2) }} ₺</td>
                                        <td class="text-end">{{ $info['count'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endif
@endif

@if($reportType == 'all' || $reportType == 'finance')
    @if(isset($data['finance']))
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Finans Raporu</h5>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <div class="text-muted small mb-1">Toplam Gelir</div>
                                <div class="h4 text-success mb-0">{{ number_format($data['finance']['total_income'], 2) }} ₺</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <div class="text-muted small mb-1">Toplam Gider</div>
                                <div class="h4 text-danger mb-0">{{ number_format($data['finance']['total_expense'], 2) }} ₺</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <div class="text-muted small mb-1">Net</div>
                                <div class="h4 {{ $data['finance']['net'] >= 0 ? 'text-success' : 'text-danger' }} mb-0">
                                    {{ number_format($data['finance']['net'], 2) }} ₺
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @if($data['finance']['by_category']->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Kategori</th>
                                    <th class="text-end">Gelir</th>
                                    <th class="text-end">Gider</th>
                                    <th class="text-end">Net</th>
                                    <th class="text-end">İşlem Sayısı</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data['finance']['by_category'] as $category => $info)
                                    <tr>
                                        <td>{{ $category }}</td>
                                        <td class="text-end text-success">{{ number_format($info['income'], 2) }} ₺</td>
                                        <td class="text-end text-danger">{{ number_format($info['expense'], 2) }} ₺</td>
                                        <td class="text-end {{ ($info['income'] - $info['expense']) >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ number_format($info['income'] - $info['expense'], 2) }} ₺
                                        </td>
                                        <td class="text-end">{{ $info['count'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endif
@endif

@if($reportType == 'all' || $reportType == 'meal')
    @if(isset($data['meal']))
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bi bi-egg-fried me-2"></i>Yemek Yardımı Raporu</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <div class="text-muted small mb-1">Toplam Yemek Yardımı</div>
                                <div class="h4 text-warning mb-0">{{ number_format($data['meal']['total'], 2) }} ₺</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <div class="text-muted small mb-1">Yararlanan Çalışan</div>
                                <div class="h4 text-primary mb-0">{{ $data['meal']['employee_count'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endif

@if($reportType == 'all' && (!isset($data) || empty($data)))
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-inbox display-4 text-muted d-block mb-3"></i>
            <p class="text-muted mb-0">Rapor verisi bulunamadı. Filtreleri değiştirip tekrar deneyin.</p>
        </div>
    </div>
@endif
@endsection

