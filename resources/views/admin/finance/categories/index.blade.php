@extends('layouts.admin')

@section('title', 'Finans Kategorileri')
@section('page-title', 'Finans Kategorileri')

@section('content')
<div class="mb-4 flex justify-between items-center">
    <h3 class="text-lg font-semibold">Kategori Listesi</h3>
    <a href="{{ route('admin.finance.categories.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
        Yeni Kategori
    </a>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tip</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ad</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durum</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">İşlemler</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($categories as $category)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-full {{ $category->type === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $category->type === 'income' ? 'Gelir' : 'Gider' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $category->name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-full {{ $category->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $category->is_active ? 'Aktif' : 'Pasif' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="{{ route('admin.finance.categories.edit', $category) }}" class="text-blue-600 hover:text-blue-900 mr-3">Düzenle</a>
                        <form action="{{ route('admin.finance.categories.destroy', $category) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Emin misiniz?')">Sil</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">Henüz kategori yok</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-6 py-4">
        {{ $categories->links() }}
    </div>
</div>
@endsection

