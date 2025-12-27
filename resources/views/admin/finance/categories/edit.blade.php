@extends('layouts.admin')

@section('title', 'Kategori Düzenle')
@section('page-title', 'Kategori Düzenle')

@section('content')
<div class="bg-white rounded-lg shadow p-6">
    <form method="POST" action="{{ route('admin.finance.categories.update', $category) }}">
        @csrf
        @method('PUT')
        <div class="mb-4">
            <label for="type" class="block text-gray-700 text-sm font-bold mb-2">Tip</label>
            <select name="type" id="type" required
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <option value="expense" {{ old('type', $category->type) == 'expense' ? 'selected' : '' }}>Gider</option>
                <option value="income" {{ old('type', $category->type) == 'income' ? 'selected' : '' }}>Gelir</option>
            </select>
        </div>
        <div class="mb-4">
            <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Kategori Adı</label>
            <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}" required
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
        </div>
        <div class="mb-4">
            <label class="flex items-center">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $category->is_active) ? 'checked' : '' }} class="form-checkbox">
                <span class="ml-2 text-sm text-gray-600">Aktif</span>
            </label>
        </div>
        <div class="flex items-center justify-between">
            <a href="{{ route('admin.finance.categories.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                İptal
            </a>
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Güncelle
            </button>
        </div>
    </form>
</div>
@endsection

