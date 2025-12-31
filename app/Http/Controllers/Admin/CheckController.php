<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Check;
use App\Models\Customer;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Check::with(['company', 'branch', 'customer', 'creator'])->latest('received_date');
        
        if ($user->company_id) {
            $query->where('company_id', $user->company_id);
        }
        if ($user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('date_from')) {
            $query->where('received_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('received_date', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('check_number', 'like', "%{$search}%")
                  ->orWhere('bank_name', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        $checks = $query->paginate(20);
        
        $customers = Customer::where('company_id', $user->company_id ?? 0)
            ->where('status', 1)
            ->orderBy('name')
            ->get();
        
        return view('admin.checks.index', compact('checks', 'customers'));
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
        
        $customers = Customer::where('company_id', $user->company_id)
            ->where('status', 1)
            ->orderBy('name')
            ->get();
        
        $selectedCustomerId = request('customer_id');
        
        return view('admin.checks.create', compact('branches', 'customers', 'selectedCustomerId'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'customer_id' => 'required|exists:customers,id',
            'check_number' => 'required|string|max:100',
            'bank_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'received_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:received_date',
            'cashed_date' => 'nullable|date',
            'status' => 'required|in:pending,cashed,cancelled',
            'notes' => 'nullable|string',
        ]);

        $branch = Branch::findOrFail($request->branch_id);
        if ($branch->company_id != $user->company_id) {
            return back()->withErrors(['branch_id' => 'Yetkisiz işlem.']);
        }

        $customer = Customer::findOrFail($request->customer_id);
        if ($customer->company_id != $user->company_id) {
            return back()->withErrors(['customer_id' => 'Yetkisiz işlem.']);
        }

        Check::create([
            'company_id' => $user->company_id,
            'branch_id' => $request->branch_id,
            'customer_id' => $request->customer_id,
            'check_number' => $request->check_number,
            'bank_name' => $request->bank_name,
            'amount' => $request->amount,
            'received_date' => $request->received_date,
            'due_date' => $request->due_date,
            'cashed_date' => $request->cashed_date,
            'status' => $request->status,
            'notes' => $request->notes,
            'created_by' => $user->id,
        ]);

        return redirect()->route('admin.checks.index')
            ->with('success', 'Çek başarıyla oluşturuldu.');
    }

    public function show(Check $check)
    {
        $user = Auth::user();
        if ($user->company_id && $check->company_id != $user->company_id) {
            abort(403);
        }
        
        $check->load(['customer', 'company', 'branch', 'creator']);
        
        return view('admin.checks.show', compact('check'));
    }

    public function edit(Check $check)
    {
        $user = Auth::user();
        if ($user->company_id && $check->company_id != $user->company_id) {
            abort(403);
        }
        
        $branches = Branch::where('company_id', $user->company_id)->get();
        if ($user->branch_id) {
            $branches = Branch::where('id', $user->branch_id)->get();
        }
        
        $customers = Customer::where('company_id', $user->company_id)
            ->where('status', 1)
            ->orderBy('name')
            ->get();
        
        return view('admin.checks.edit', compact('check', 'branches', 'customers'));
    }

    public function update(Request $request, Check $check)
    {
        $user = Auth::user();
        if ($user->company_id && $check->company_id != $user->company_id) {
            abort(403);
        }

        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'customer_id' => 'required|exists:customers,id',
            'check_number' => 'required|string|max:100',
            'bank_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'received_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:received_date',
            'cashed_date' => 'nullable|date',
            'status' => 'required|in:pending,cashed,cancelled',
            'notes' => 'nullable|string',
        ]);

        $branch = Branch::findOrFail($request->branch_id);
        if ($branch->company_id != $user->company_id) {
            return back()->withErrors(['branch_id' => 'Yetkisiz işlem.']);
        }

        $customer = Customer::findOrFail($request->customer_id);
        if ($customer->company_id != $user->company_id) {
            return back()->withErrors(['customer_id' => 'Yetkisiz işlem.']);
        }

        $check->update([
            'branch_id' => $request->branch_id,
            'customer_id' => $request->customer_id,
            'check_number' => $request->check_number,
            'bank_name' => $request->bank_name,
            'amount' => $request->amount,
            'received_date' => $request->received_date,
            'due_date' => $request->due_date,
            'cashed_date' => $request->cashed_date,
            'status' => $request->status,
            'notes' => $request->notes,
        ]);

        return redirect()->route('admin.checks.index')
            ->with('success', 'Çek başarıyla güncellendi.');
    }

    public function destroy(Check $check)
    {
        $user = Auth::user();
        if ($user->company_id && $check->company_id != $user->company_id) {
            abort(403);
        }

        $check->delete();

        return redirect()->route('admin.checks.index')
            ->with('success', 'Çek başarıyla silindi.');
    }
}

