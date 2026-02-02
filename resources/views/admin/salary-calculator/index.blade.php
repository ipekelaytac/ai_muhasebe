@extends('layouts.admin')

@section('title', 'Maaş Hesaplama')
@section('page-title', 'Maaş Hesaplama')
@section('page-subtitle', 'Tarih aralığına göre maaş hesaplama')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Hesaplama Formu</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.salary-calculator.calculate') }}" id="calculatorForm">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="employee_id" class="form-label">Personel</label>
                            <select name="employee_id" id="employee_id" required class="form-select">
                                <option value="">Seçiniz</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}" 
                                        data-company="{{ $employee->company->name }}"
                                        data-branch="{{ $employee->branch->name }}"
                                        {{ old('employee_id', isset($result) ? $result['employee']->id : '') == $employee->id ? 'selected' : '' }}>
                                        {{ $employee->full_name }} - {{ $employee->company->name }} / {{ $employee->branch->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div id="employeeInfo" class="mt-2 text-muted small" style="display: none;">
                                <span id="companyInfo"></span> / <span id="branchInfo"></span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Başlangıç Tarihi</label>
                            <input type="date" name="start_date" id="start_date" 
                                value="{{ old('start_date', isset($result) ? $result['start_date']->format('Y-m-d') : '') }}" 
                                required class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">Bitiş Tarihi</label>
                            <input type="date" name="end_date" id="end_date" 
                                value="{{ old('end_date', isset($result) ? $result['end_date']->format('Y-m-d') : '') }}" 
                                required class="form-control">
                        </div>
                    </div>
                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <label for="overtime_hours" class="form-label">
                                <i class="bi bi-clock-history me-1"></i>Mesai Saati
                            </label>
                            <input type="number" name="overtime_hours" id="overtime_hours" 
                                value="{{ old('overtime_hours', isset($result) ? $result['overtime_hours'] : '') }}" 
                                step="0.5" min="0" class="form-control" placeholder="0">
                            <small class="text-muted">Saat cinsinden mesai süresi (örn: 2.5)</small>
                        </div>
                        <div class="col-md-6">
                            <label for="missing_hours" class="form-label">
                                <i class="bi bi-dash-circle me-1 text-danger"></i>Eksik Mesai Saati
                            </label>
                            <input type="number" name="missing_hours" id="missing_hours" 
                                value="{{ old('missing_hours', isset($result) ? $result['missing_hours'] : '') }}" 
                                step="0.5" min="0" class="form-control" placeholder="0">
                            <small class="text-muted">Eksik çalışılan toplam saat (örn: 2)</small>
                            <div id="missingDeductionPreview" class="mt-2 small text-danger" style="display: none;">
                                <strong>Kesinti: <span id="missingDeductionAmount">0.00</span> ₺</strong>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-calculator me-2"></i>Hesapla
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('calculatorForm').reset(); document.getElementById('resultCard').style.display='none';">
                            <i class="bi bi-arrow-clockwise me-2"></i>Temizle
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if(isset($result))
        <div class="card border-0 shadow-sm" id="resultCard">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="bi bi-check-circle me-2"></i>Hesaplama Sonucu
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Personel Bilgileri</h6>
                                <p class="mb-1"><strong>Ad:</strong> {{ $result['employee']->full_name }}</p>
                                <p class="mb-1"><strong>Şirket:</strong> {{ $result['employee']->company->name }}</p>
                                <p class="mb-0"><strong>Şube:</strong> {{ $result['employee']->branch->name }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Tarih Aralığı</h6>
                                <p class="mb-1"><strong>Başlangıç:</strong> {{ $result['start_date']->format('d.m.Y') }}</p>
                                <p class="mb-1"><strong>Bitiş:</strong> {{ $result['end_date']->format('d.m.Y') }}</p>
                                <p class="mb-0"><strong>Gün Sayısı:</strong> <span class="badge bg-primary">{{ $result['days'] }} gün</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Kalem</th>
                                <th class="text-end">Aylık Tutar</th>
                                <th class="text-end">Günlük Tutar</th>
                                <th class="text-end">Gün Sayısı</th>
                                <th class="text-end">Hesaplanan Tutar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-bold">Net Maaş</td>
                                <td class="text-end">{{ number_format($result['monthly_salary'], 2) }} ₺</td>
                                <td class="text-end">{{ number_format($result['daily_salary'], 2) }} ₺</td>
                                <td class="text-end">{{ $result['days'] }} gün</td>
                                <td class="text-end fw-bold text-primary">{{ number_format($result['calculated_amount'], 2) }} ₺</td>
                            </tr>
                            @if($result['monthly_meal_allowance'] > 0)
                            <tr>
                                <td class="fw-bold">Yemek Yardımı</td>
                                <td class="text-end">{{ number_format($result['monthly_meal_allowance'], 2) }} ₺</td>
                                <td class="text-end">{{ number_format($result['daily_meal_allowance'], 2) }} ₺</td>
                                <td class="text-end">{{ $result['days'] }} gün</td>
                                <td class="text-end fw-bold text-info">{{ number_format($result['calculated_meal_allowance'], 2) }} ₺</td>
                            </tr>
                            @endif
                            @if($result['overtime_hours'] > 0)
                            <tr>
                                <td class="fw-bold">Mesai Ücreti</td>
                                <td class="text-end">-</td>
                                <td class="text-end">{{ number_format($result['hourly_overtime_rate'], 2) }} ₺/saat</td>
                                <td class="text-end">{{ number_format($result['overtime_hours'], 1) }} saat</td>
                                <td class="text-end fw-bold text-success">{{ number_format($result['calculated_overtime'], 2) }} ₺</td>
                            </tr>
                            @endif
                            @if($result['missing_hours'] > 0)
                            <tr>
                                <td class="fw-bold text-danger">Eksik Mesai Kesintisi</td>
                                <td class="text-end">-</td>
                                <td class="text-end">{{ number_format($result['hourly_salary_rate'], 2) }} ₺/saat</td>
                                <td class="text-end">{{ number_format($result['missing_hours'], 1) }} saat</td>
                                <td class="text-end fw-bold text-danger">-{{ number_format($result['calculated_missing_deduction'], 2) }} ₺</td>
                            </tr>
                            @endif
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="4" class="text-end">TOPLAM</th>
                                <th class="text-end fs-5 fw-bold text-success">{{ number_format($result['total_amount'], 2) }} ₺</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="alert alert-info mt-4">
                    <h6 class="alert-heading"><i class="bi bi-info-circle me-2"></i>Hesaplama Detayları</h6>
                    <p class="mb-2"><strong>Günlük Maaş:</strong> {{ number_format($result['monthly_salary'], 2) }} ₺ ÷ 30 = {{ number_format($result['daily_salary'], 2) }} ₺</p>
                    <p class="mb-2"><strong>Hesaplanan Maaş:</strong> {{ number_format($result['daily_salary'], 2) }} ₺ × {{ $result['days'] }} gün = {{ number_format($result['calculated_amount'], 2) }} ₺</p>
                    @if($result['monthly_meal_allowance'] > 0)
                    <p class="mb-2"><strong>Günlük Yemek Yardımı:</strong> {{ number_format($result['monthly_meal_allowance'], 2) }} ₺ ÷ 30 = {{ number_format($result['daily_meal_allowance'], 2) }} ₺</p>
                    <p class="mb-2"><strong>Hesaplanan Yemek Yardımı:</strong> {{ number_format($result['daily_meal_allowance'], 2) }} ₺ × {{ $result['days'] }} gün = {{ number_format($result['calculated_meal_allowance'], 2) }} ₺</p>
                    @endif
                    @if($result['overtime_hours'] > 0)
                    <p class="mb-2"><strong>Saatlik Mesai Ücreti:</strong> ({{ number_format($result['monthly_salary'], 2) }} ₺ ÷ 225) × 1.5 = {{ number_format($result['hourly_overtime_rate'], 2) }} ₺/saat</p>
                    <p class="mb-2"><strong>Hesaplanan Mesai Ücreti:</strong> {{ number_format($result['hourly_overtime_rate'], 2) }} ₺ × {{ number_format($result['overtime_hours'], 1) }} saat = {{ number_format($result['calculated_overtime'], 2) }} ₺</p>
                    @endif
                    @if($result['missing_hours'] > 0)
                    <p class="mb-2"><strong>Saatlik Maaş:</strong> {{ number_format($result['monthly_salary'], 2) }} ₺ ÷ 225 = {{ number_format($result['hourly_salary_rate'], 2) }} ₺/saat</p>
                    <p class="mb-0"><strong>Eksik Mesai Kesintisi:</strong> {{ number_format($result['hourly_salary_rate'], 2) }} ₺ × {{ number_format($result['missing_hours'], 1) }} saat = {{ number_format($result['calculated_missing_deduction'], 2) }} ₺</p>
                    @endif
                </div>
            </div>
        </div>
        @else
        <div class="card border-0 shadow-sm" id="resultCard" style="display: none;">
            <div class="card-body text-center py-5">
                <i class="bi bi-calculator display-4 text-muted mb-3"></i>
                <p class="text-muted">Hesaplama yapmak için formu doldurun ve "Hesapla" butonuna tıklayın.</p>
            </div>
        </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const employeeSelect = document.getElementById('employee_id');
    const employeeInfo = document.getElementById('employeeInfo');
    const companyInfo = document.getElementById('companyInfo');
    const branchInfo = document.getElementById('branchInfo');

    employeeSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            companyInfo.textContent = selectedOption.getAttribute('data-company');
            branchInfo.textContent = selectedOption.getAttribute('data-branch');
            employeeInfo.style.display = 'block';
        } else {
            employeeInfo.style.display = 'none';
        }
    });

    // Trigger on page load if there's a selected value
    if (employeeSelect.value) {
        employeeSelect.dispatchEvent(new Event('change'));
    }

    // Set end_date min to start_date
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    
    startDateInput.addEventListener('change', function() {
        endDateInput.min = this.value;
        if (endDateInput.value && endDateInput.value < this.value) {
            endDateInput.value = this.value;
        }
    });

    // Eksik mesai kesintisi önizleme
    @if(isset($result) && isset($result['hourly_salary_rate']))
    const hourlyRate = {{ $result['hourly_salary_rate'] }};
    const missingHoursInput = document.getElementById('missing_hours');
    const missingDeductionPreview = document.getElementById('missingDeductionPreview');
    const missingDeductionAmount = document.getElementById('missingDeductionAmount');
    
    function updateMissingDeductionPreview() {
        const missingHours = parseFloat(missingHoursInput.value) || 0;
        if (missingHours > 0) {
            const deduction = hourlyRate * missingHours;
            missingDeductionAmount.textContent = deduction.toFixed(2);
            missingDeductionPreview.style.display = 'block';
        } else {
            missingDeductionPreview.style.display = 'none';
        }
    }
    
    missingHoursInput.addEventListener('input', updateMissingDeductionPreview);
    @endif
});
</script>
@endsection

