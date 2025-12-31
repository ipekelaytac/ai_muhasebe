<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CostCalculatorController extends Controller
{
    public function index()
    {
        return view('admin.cost-calculator.index');
    }

    public function calculate(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.unit' => 'required|string|in:adet,metre,cm,kg,gram,litre,ml,m2,cm2',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|numeric|min:0',
        ]);

        $items = [];
        $totalCost = 0;

        foreach ($request->items as $item) {
            $unitPrice = floatval($item['unit_price']);
            $quantity = floatval($item['quantity']);
            
            // Birim fiyatı zaten seçilen birim için girildiği için dönüşüm yapmıyoruz
            // Sadece çarpma yapıyoruz: birim fiyatı × miktar = maliyet
            $cost = $unitPrice * $quantity;
            $totalCost += $cost;

            $items[] = [
                'name' => $item['name'],
                'unit' => $item['unit'],
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'cost' => $cost,
            ];
        }

        return view('admin.cost-calculator.index', [
            'items' => $items,
            'total_cost' => $totalCost,
        ]);
    }
}

