@extends('layouts.admin')

@section('title', 'Şirketler')
@section('page-title', 'Şirketler')
@section('page-subtitle', 'Şirket yönetimi')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0">Tüm Şirketler</h5>
        <small class="text-muted">Şirketleri görüntüleyin ve yönetin</small>
    </div>
    <a href="{{ route('admin.companies.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>
        Yeni Şirket
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Şirket Adı</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($companies as $company)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 rounded p-2 me-2">
                                        <i class="bi bi-building text-primary"></i>
                                    </div>
                                    <span class="fw-medium">{{ $company->name }}</span>
                                </div>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.companies.edit', $company) }}" class="btn btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.companies.destroy', $company) }}" method="POST" class="d-inline" onsubmit="return confirm('Bu şirketi silmek istediğinizden emin misiniz?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center py-5">
                                <i class="bi bi-building fs-1 text-muted d-block mb-2"></i>
                                <p class="text-muted mb-3">Henüz şirket bulunmuyor</p>
                                <a href="{{ route('admin.companies.create') }}" class="btn btn-primary btn-sm">
                                    İlk şirketi oluşturun
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($companies->hasPages())
        <div class="card-footer bg-white">
            {{ $companies->links() }}
        </div>
    @endif
</div>
@endsection
