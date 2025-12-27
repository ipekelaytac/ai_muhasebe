@extends('layouts.admin')

@section('title', 'Finans Raporları')
@section('page-title', 'Finans Raporları')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-2">Toplam Gelir</h3>
        <p class="text-3xl font-bold text-green-600">{{ number_format($income, 2) }} ₺</p>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-2">Toplam Gider</h3>
        <p class="text-3xl font-bold text-red-600">{{ number_format($expense, 2) }} ₺</p>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-2">Net</h3>
        <p class="text-3xl font-bold {{ $net >= 0 ? 'text-green-600' : 'text-red-600' }}">
            {{ number_format($net, 2) }} ₺
        </p>
    </div>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold mb-4">İşlemler ({{ $year }} - {{ $month }})</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tarih</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tip</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kategori</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tutar</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($transactions as $transaction)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $transaction->transaction_date->format('d.m.Y') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full {{ $transaction->type === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $transaction->type === 'income' ? 'Gelir' : 'Gider' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $transaction->category->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium {{ $transaction->type === 'income' ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($transaction->amount, 2) }} ₺
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">Bu dönem için işlem yok</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

