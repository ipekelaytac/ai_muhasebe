<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeeContractController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $query = EmployeeContract::with(['employee.company', 'employee.branch'])->latest();
        
        if ($user->company_id) {
            $query->whereHas('employee', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        }
        
        $contracts = $query->paginate(20);
        return view('admin.contracts.index', compact('contracts'));
    }

    public function create()
    {
        $user = Auth::user();
        $query = Employee::with(['company', 'branch'])->latest();
        
        if ($user->company_id) {
            $query->where('company_id', $user->company_id);
        }
        if ($user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }
        
        $employees = $query->get();
        return view('admin.contracts.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'monthly_net_salary' => 'required|numeric|min:0',
            'pay_day_1' => 'required|integer|min:1|max:31',
            'pay_amount_1' => 'required|numeric|min:0',
            'pay_day_2' => 'required|integer|min:1|max:31',
            'pay_amount_2' => 'required|numeric|min:0',
            'meal_allowance' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $employee = Employee::findOrFail($request->employee_id);
        if ($user->company_id && $employee->company_id != $user->company_id) {
            abort(403);
        }

        // Check for overlapping contracts
        $overlapping = EmployeeContract::where('employee_id', $request->employee_id)
            ->where(function ($q) use ($request) {
                $q->whereBetween('effective_from', [$request->effective_from, $request->effective_to ?? '9999-12-31'])
                  ->orWhereBetween('effective_to', [$request->effective_from, $request->effective_to ?? '9999-12-31'])
                  ->orWhere(function ($q2) use ($request) {
                      $q2->where('effective_from', '<=', $request->effective_from)
                         ->where(function ($q3) use ($request) {
                             $q3->whereNull('effective_to')
                                ->orWhere('effective_to', '>=', $request->effective_from);
                         });
                  });
            })
            ->exists();

        if ($overlapping) {
            return back()->withErrors(['effective_from' => 'Bu tarih aralığında başka bir sözleşme mevcut.']);
        }

        EmployeeContract::create($request->only([
            'employee_id', 'effective_from', 'effective_to', 'monthly_net_salary',
            'pay_day_1', 'pay_amount_1', 'pay_day_2', 'pay_amount_2',
            'meal_allowance', 'notes'
        ]));

        return redirect()->route('admin.contracts.index')
            ->with('success', 'Sözleşme başarıyla oluşturuldu.');
    }

    public function edit(EmployeeContract $contract)
    {
        $user = Auth::user();
        if ($user->company_id && $contract->employee->company_id != $user->company_id) {
            abort(403);
        }

        $query = Employee::with(['company', 'branch']);
        if ($user->company_id) {
            $query->where('company_id', $user->company_id);
        }
        $employees = $query->get();

        return view('admin.contracts.edit', compact('contract', 'employees'));
    }

    public function update(Request $request, EmployeeContract $contract)
    {
        $user = Auth::user();
        if ($user->company_id && $contract->employee->company_id != $user->company_id) {
            abort(403);
        }

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'monthly_net_salary' => 'required|numeric|min:0',
            'pay_day_1' => 'required|integer|min:1|max:31',
            'pay_amount_1' => 'required|numeric|min:0',
            'pay_day_2' => 'required|integer|min:1|max:31',
            'pay_amount_2' => 'required|numeric|min:0',
            'meal_allowance' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $contract->update($request->only([
            'employee_id', 'effective_from', 'effective_to', 'monthly_net_salary',
            'pay_day_1', 'pay_amount_1', 'pay_day_2', 'pay_amount_2',
            'meal_allowance', 'notes'
        ]));

        return redirect()->route('admin.contracts.index')
            ->with('success', 'Sözleşme başarıyla güncellendi.');
    }
}

