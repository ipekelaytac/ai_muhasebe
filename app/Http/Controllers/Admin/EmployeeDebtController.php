<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeDebt;
use App\Models\Employee;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeDebtController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = EmployeeDebt::with(['company', 'branch', 'employee', 'creator'])->latest('debt_date');
        
        if ($user->company_id) {
            $query->where('company_id', $user->company_id);
        }
        if ($user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }
        
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->where('debt_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('debt_date', '<=', $request->date_to);
        }
        
        $debts = $query->paginate(20);
        
        $employees = Employee::where('company_id', $user->company_id ?? 0)
            ->where('status', 1)
            ->orderBy('full_name')
            ->get();
        
        return view('admin.employee-debts.index', compact('debts', 'employees'));
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
        
        $employees = Employee::where('company_id', $user->company_id)
            ->where('status', 1)
            ->orderBy('full_name')
            ->get();
        
        $selectedEmployeeId = request('employee_id');
        
        return view('admin.employee-debts.create', compact('branches', 'employees', 'selectedEmployeeId'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'employee_id' => 'required|exists:employees,id',
            'debt_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
            'status' => 'boolean',
        ]);

        $branch = Branch::findOrFail($request->branch_id);
        if ($branch->company_id != $user->company_id) {
            return back()->withErrors(['branch_id' => 'Yetkisiz işlem.']);
        }

        $employee = Employee::findOrFail($request->employee_id);
        if ($employee->company_id != $user->company_id) {
            return back()->withErrors(['employee_id' => 'Yetkisiz işlem.']);
        }

        EmployeeDebt::create([
            'company_id' => $user->company_id,
            'branch_id' => $request->branch_id,
            'employee_id' => $request->employee_id,
            'debt_date' => $request->debt_date,
            'amount' => $request->amount,
            'description' => $request->description,
            'status' => $request->has('status') ? 1 : 0,
            'created_by' => $user->id,
        ]);

        return redirect()->route('admin.employee-debts.index')
            ->with('success', 'Borç başarıyla oluşturuldu.');
    }

    public function show(EmployeeDebt $employeeDebt)
    {
        $user = Auth::user();
        if ($user->company_id && $employeeDebt->company_id != $user->company_id) {
            abort(403);
        }
        
        $employeeDebt->load(['employee', 'company', 'branch', 'creator', 'payments.payrollItem', 'payments.creator']);
        
        return view('admin.employee-debts.show', compact('employeeDebt'));
    }

    public function edit(EmployeeDebt $employeeDebt)
    {
        $user = Auth::user();
        if ($user->company_id && $employeeDebt->company_id != $user->company_id) {
            abort(403);
        }
        
        $branches = Branch::where('company_id', $user->company_id)->get();
        if ($user->branch_id) {
            $branches = Branch::where('id', $user->branch_id)->get();
        }
        
        $employees = Employee::where('company_id', $user->company_id)
            ->where('status', 1)
            ->orderBy('full_name')
            ->get();
        
        return view('admin.employee-debts.edit', compact('employeeDebt', 'branches', 'employees'));
    }

    public function update(Request $request, EmployeeDebt $employeeDebt)
    {
        $user = Auth::user();
        if ($user->company_id && $employeeDebt->company_id != $user->company_id) {
            abort(403);
        }

        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'employee_id' => 'required|exists:employees,id',
            'debt_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
            'status' => 'boolean',
        ]);

        $branch = Branch::findOrFail($request->branch_id);
        if ($branch->company_id != $user->company_id) {
            return back()->withErrors(['branch_id' => 'Yetkisiz işlem.']);
        }

        $employee = Employee::findOrFail($request->employee_id);
        if ($employee->company_id != $user->company_id) {
            return back()->withErrors(['employee_id' => 'Yetkisiz işlem.']);
        }

        $employeeDebt->update([
            'branch_id' => $request->branch_id,
            'employee_id' => $request->employee_id,
            'debt_date' => $request->debt_date,
            'amount' => $request->amount,
            'description' => $request->description,
            'status' => $request->has('status') ? 1 : 0,
        ]);

        return redirect()->route('admin.employee-debts.index')
            ->with('success', 'Borç başarıyla güncellendi.');
    }

    public function destroy(EmployeeDebt $employeeDebt)
    {
        $user = Auth::user();
        if ($user->company_id && $employeeDebt->company_id != $user->company_id) {
            abort(403);
        }

        $employeeDebt->delete();

        return redirect()->route('admin.employee-debts.index')
            ->with('success', 'Borç başarıyla silindi.');
    }
}

