@extends('layouts.admin')

@section('title', 'Şubeler')
@section('page-title', 'Şubeler')
@section('page-subtitle', 'Şube yönetimi')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0">Tüm Şubeler</h5>
        <small class="text-muted">Şubeleri görüntüleyin ve yönetin</small>
    </div>
    <a href="{{ route('admin.branches.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>
        Yeni Şube
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Şirket</th>
                        <th>Şube Adı</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($branches as $branch)
                        <tr>
                            <td>{{ $branch->company->name }}</td>
                            <td class="fw-medium">{{ $branch->name }}</td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.branches.edit', $branch) }}" class="btn btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.branches.destroy', $branch) }}" method="POST" class="d-inline" onsubmit="return confirm('Emin misiniz?');">
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
                            <td colspan="3" class="text-center py-5">
                                <i class="bi bi-building fs-1 text-muted d-block mb-2"></i>
                                <p class="text-muted mb-3">Henüz şube bulunmuyor</p>
                                <a href="{{ route('admin.branches.create') }}" class="btn btn-primary btn-sm">
                                    İlk şubeyi oluşturun
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($branches->hasPages())
        <div class="card-footer bg-white">
            {{ $branches->links() }}
        </div>
    @endif
</div>
@endsection
