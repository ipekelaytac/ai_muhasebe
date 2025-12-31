<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Customer::with(['company', 'branch'])->latest();
        
        if ($user->company_id) {
            $query->where('company_id', $user->company_id);
        }
        if ($user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }
        
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        $customers = $query->paginate(20);
        return view('admin.customers.index', compact('customers'));
    }

    public function create()
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $branches = Branch::where('company_id', $user->company_id)->get();
        if ($user->branch_id) {
            $branches = Branch::where('id', $user->branch_id)->get();
        }
        
        return view('admin.customers.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'code' => [
                'nullable',
                'string',
                'max:50',
                function ($attribute, $value, $fail) use ($user) {
                    if ($value) {
                        $exists = \App\Models\Customer::where('company_id', $user->company_id)
                            ->where('code', $value)
                            ->exists();
                        if ($exists) {
                            $fail('Bu cari kodu zaten kullanılıyor.');
                        }
                    }
                },
            ],
            'name' => 'required|string|max:255',
            'type' => 'required|in:customer,supplier',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'tax_number' => 'nullable|string|max:50',
            'tax_office' => 'nullable|string|max:255',
            'status' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $branch = Branch::findOrFail($request->branch_id);
        if ($branch->company_id != $user->company_id) {
            return back()->withErrors(['branch_id' => 'Yetkisiz işlem.']);
        }

        Customer::create([
            'company_id' => $user->company_id,
            'branch_id' => $request->branch_id,
            'code' => $request->code,
            'name' => $request->name,
            'type' => $request->type,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'tax_number' => $request->tax_number,
            'tax_office' => $request->tax_office,
            'status' => $request->has('status') ? 1 : 0,
            'notes' => $request->notes,
        ]);

        return redirect()->route('admin.customers.index')
            ->with('success', 'Cari başarıyla oluşturuldu.');
    }

    public function show(Customer $customer)
    {
        $user = Auth::user();
        if ($user->company_id && $customer->company_id != $user->company_id) {
            abort(403);
        }
        
        $customer->load(['transactions' => function($query) {
            $query->latest('transaction_date')->latest('id');
        }, 'transactions.creator', 'checks' => function($query) {
            $query->latest('received_date')->latest('id');
        }]);
        
        $balance = $customer->balance;
        
        return view('admin.customers.show', compact('customer', 'balance'));
    }

    public function edit(Customer $customer)
    {
        $user = Auth::user();
        if ($user->company_id && $customer->company_id != $user->company_id) {
            abort(403);
        }
        
        $branches = Branch::where('company_id', $user->company_id)->get();
        if ($user->branch_id) {
            $branches = Branch::where('id', $user->branch_id)->get();
        }
        
        return view('admin.customers.edit', compact('customer', 'branches'));
    }

    public function update(Request $request, Customer $customer)
    {
        $user = Auth::user();
        if ($user->company_id && $customer->company_id != $user->company_id) {
            abort(403);
        }

        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'code' => [
                'nullable',
                'string',
                'max:50',
                function ($attribute, $value, $fail) use ($user, $customer) {
                    if ($value) {
                        $exists = \App\Models\Customer::where('company_id', $user->company_id)
                            ->where('code', $value)
                            ->where('id', '!=', $customer->id)
                            ->exists();
                        if ($exists) {
                            $fail('Bu cari kodu zaten kullanılıyor.');
                        }
                    }
                },
            ],
            'name' => 'required|string|max:255',
            'type' => 'required|in:customer,supplier',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'tax_number' => 'nullable|string|max:50',
            'tax_office' => 'nullable|string|max:255',
            'status' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $branch = Branch::findOrFail($request->branch_id);
        if ($branch->company_id != $user->company_id) {
            return back()->withErrors(['branch_id' => 'Yetkisiz işlem.']);
        }

        $customer->update([
            'branch_id' => $request->branch_id,
            'code' => $request->code,
            'name' => $request->name,
            'type' => $request->type,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'tax_number' => $request->tax_number,
            'tax_office' => $request->tax_office,
            'status' => $request->has('status') ? 1 : 0,
            'notes' => $request->notes,
        ]);

        return redirect()->route('admin.customers.index')
            ->with('success', 'Cari başarıyla güncellendi.');
    }

    public function destroy(Customer $customer)
    {
        $user = Auth::user();
        if ($user->company_id && $customer->company_id != $user->company_id) {
            abort(403);
        }

        $customer->delete();

        return redirect()->route('admin.customers.index')
            ->with('success', 'Cari başarıyla silindi.');
    }
}

