@extends('layouts.admin')

@section('title', 'Dönem Kilit')
@section('page-title', 'Dönem Kilit')
@section('page-subtitle', 'Muhasebe dönemlerini kilitle/aç')

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('accounting.periods.index') }}" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Yıl</label>
                <input type="number" name="year" class="form-control" value="{{ $year }}" min="2020" max="2100">
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
                        <th>Dönem</th>
                        <th>Durum</th>
                        <th>Kilitlenme Tarihi</th>
                        <th>Kilitleyen</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($periods as $period)
                        <tr>
                            <td>
                                <strong>{{ $period->year }} - {{ str_pad($period->month, 2, '0', STR_PAD_LEFT) }}</strong>
                            </td>
                            <td>
                                @if($period->isLocked())
                                    <span class="badge bg-danger">Kilitli</span>
                                @else
                                    <span class="badge bg-success">Açık</span>
                                @endif
                            </td>
                            <td>
                                @if($period->locked_at)
                                    {{ \Carbon\Carbon::parse($period->locked_at)->format('d.m.Y H:i') }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($period->lockedBy)
                                    {{ $period->lockedBy->name }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($period->isLocked())
                                    <form action="{{ route('accounting.periods.unlock', $period) }}" method="POST" class="d-inline" onsubmit="return confirm('Bu dönemin kilidini açmak istediğinize emin misiniz?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="bi bi-unlock me-1"></i>Kilidi Aç
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('accounting.periods.lock', $period) }}" method="POST" class="d-inline" onsubmit="return confirm('Bu dönemi kilitlemek istediğinize emin misiniz? Kilitli dönemlerde değişiklik yapılamaz.');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-lock me-1"></i>Kilitle
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                                <p class="text-muted mb-0">Henüz dönem bulunmuyor</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="alert alert-info mt-4">
    <i class="bi bi-info-circle me-2"></i>
    <strong>Bilgi:</strong> Kilitli dönemlerde belge, ödeme ve dağıtım işlemleri yapılamaz. Sadece ters kayıt oluşturulabilir.
</div>
@endsection
