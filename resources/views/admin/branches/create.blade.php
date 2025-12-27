@extends('layouts.admin')

@section('title', 'Yeni Şube')
@section('page-title', 'Yeni Şube')

@section('content')
<div class="bg-white rounded-lg shadow p-6">
    <form method="POST" action="{{ route('admin.branches.store') }}">
        @csrf
        <div class="mb-4">
            <label for="company_id" class="block text-gray-700 text-sm font-bold mb-2">Şirket</label>
            <select name="company_id" id="company_id" required
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <option value="">Seçiniz</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                        {{ $company->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="mb-4">
            <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Şube Adı</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
        </div>
        <div class="mb-4">
            <label for="address" class="block text-gray-700 text-sm font-bold mb-2">Adres</label>
            <textarea name="address" id="address" rows="3"
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">{{ old('address') }}</textarea>
        </div>
        <div class="flex items-center justify-between">
            <a href="{{ route('admin.branches.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                İptal
            </a>
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Kaydet
            </button>
        </div>
    </form>
</div>
@endsection

