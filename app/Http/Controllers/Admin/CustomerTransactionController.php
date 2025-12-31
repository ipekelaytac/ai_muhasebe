<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerTransactionController extends Controller
{
    public function create(Customer $customer)
    {
        $user = Auth::user();
        if ($user->company_id && $customer->company_id != $user->company_id) {
            abort(403);
        }
        
        return view('admin.customers.transactions.create', compact('customer'));
    }

    public function store(Request $request, Customer $customer)
    {
        $user = Auth::user();
        if ($user->company_id && $customer->company_id != $user->company_id) {
            abort(403);
        }
        
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $request->validate([
            'type' => 'required|in:income,expense',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01',
        ]);

        CustomerTransaction::create([
            'customer_id' => $customer->id,
            'company_id' => $user->company_id,
            'branch_id' => $customer->branch_id,
            'type' => $request->type,
            'transaction_date' => $request->transaction_date,
            'description' => $request->description,
            'amount' => $request->amount,
            'created_by' => $user->id,
        ]);

        return redirect()->route('admin.customers.show', $customer)
            ->with('success', 'Hareket başarıyla eklendi.');
    }

    public function edit(Customer $customer, CustomerTransaction $transaction)
    {
        $user = Auth::user();
        if ($user->company_id && $customer->company_id != $user->company_id) {
            abort(403);
        }
        
        if ($transaction->customer_id != $customer->id) {
            abort(404);
        }
        
        return view('admin.customers.transactions.edit', compact('customer', 'transaction'));
    }

    public function update(Request $request, Customer $customer, CustomerTransaction $transaction)
    {
        $user = Auth::user();
        if ($user->company_id && $customer->company_id != $user->company_id) {
            abort(403);
        }
        
        if ($transaction->customer_id != $customer->id) {
            abort(404);
        }

        $request->validate([
            'type' => 'required|in:income,expense',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $transaction->update([
            'type' => $request->type,
            'transaction_date' => $request->transaction_date,
            'description' => $request->description,
            'amount' => $request->amount,
        ]);

        return redirect()->route('admin.customers.show', $customer)
            ->with('success', 'Hareket başarıyla güncellendi.');
    }

    public function destroy(Customer $customer, CustomerTransaction $transaction)
    {
        $user = Auth::user();
        if ($user->company_id && $customer->company_id != $user->company_id) {
            abort(403);
        }
        
        if ($transaction->customer_id != $customer->id) {
            abort(404);
        }

        $transaction->delete();

        return redirect()->route('admin.customers.show', $customer)
            ->with('success', 'Hareket başarıyla silindi.');
    }
}

