<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advance;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdvanceController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Advance::with(['company', 'branch', 'employee'])->latest();
        
        if ($user->company_id) {
            $query->where('company_id', $user->company_id);
        }
        if ($user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        $advances = $query->paginate(20);
        return view('admin.advances.index', compact('advances'));
    }

    public function create()
    {
        $user = Auth::user();
        $query = Employee::with(['company', 'branch'])->where('status', 1);
        
        if ($user->company_id) {
            $query->where('company_id', $user->company_id);
        }
        if ($user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }
        
        $employees = $query->get();
        
        return view('admin.advances.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'advance_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:cash,bank,other',
            'note' => 'nullable|string|max:500',
        ]);

        $employee = Employee::findOrFail($request->employee_id);
        
        if ($user->company_id && $employee->company_id != $user->company_id) {
            return back()->withErrors(['employee_id' => 'Yetkisiz işlem.']);
        }

        Advance::create([
            'company_id' => $employee->company_id,
            'branch_id' => $employee->branch_id,
            'employee_id' => $request->employee_id,
            'advance_date' => $request->advance_date,
            'amount' => $request->amount,
            'method' => $request->method,
            'note' => $request->note,
            'status' => 1,
        ]);

        return redirect()->route('admin.advances.index')
            ->with('success', 'Avans başarıyla oluşturuldu.');
    }

    public function edit(Advance $advance)
    {
        $user = Auth::user();
        if ($user->company_id && $advance->company_id != $user->company_id) {
            abort(403);
        }

        $query = Employee::with(['company', 'branch'])->where('status', 1);
        
        if ($user->company_id) {
            $query->where('company_id', $user->company_id);
        }
        if ($user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }
        
        $employees = $query->get();

        return view('admin.advances.edit', compact('advance', 'employees'));
    }

    public function update(Request $request, Advance $advance)
    {
        $user = Auth::user();
        if ($user->company_id && $advance->company_id != $user->company_id) {
            abort(403);
        }

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'advance_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:cash,bank,other',
            'note' => 'nullable|string|max:500',
        ]);

        $employee = Employee::findOrFail($request->employee_id);
        
        if ($user->company_id && $employee->company_id != $user->company_id) {
            return back()->withErrors(['employee_id' => 'Yetkisiz işlem.']);
        }

        $advance->update([
            'company_id' => $employee->company_id,
            'branch_id' => $employee->branch_id,
            'employee_id' => $request->employee_id,
            'advance_date' => $request->advance_date,
            'amount' => $request->amount,
            'method' => $request->method,
            'note' => $request->note,
        ]);

        return redirect()->route('admin.advances.index')
            ->with('success', 'Avans başarıyla güncellendi.');
    }

    public function destroy(Advance $advance)
    {
        $user = Auth::user();
        if ($user->company_id && $advance->company_id != $user->company_id) {
            abort(403);
        }

        $advance->delete();
        return redirect()->route('admin.advances.index')
            ->with('success', 'Avans başarıyla silindi.');
    }
}

