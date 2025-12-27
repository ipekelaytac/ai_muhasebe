<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $query = Employee::with(['company', 'branch'])->latest();
        
        if ($user->company_id) {
            $query->where('company_id', $user->company_id);
        }
        if ($user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }
        
        $employees = $query->paginate(20);
        return view('admin.employees.index', compact('employees'));
    }

    public function create()
    {
        $user = Auth::user();
        $companies = Company::all();
        $branches = Branch::all();
        
        if ($user->company_id) {
            $companies = Company::where('id', $user->company_id)->get();
            $branches = Branch::where('company_id', $user->company_id)->get();
        }
        if ($user->branch_id) {
            $branches = Branch::where('id', $user->branch_id)->get();
        }
        
        return view('admin.employees.create', compact('companies', 'branches'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'required|exists:branches,id',
            'full_name' => 'required|string|max:190',
            'phone' => 'nullable|string|max:50',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'boolean',
        ]);

        if ($user->company_id && $request->company_id != $user->company_id) {
            return back()->withErrors(['company_id' => 'Yetkisiz işlem.']);
        }

        Employee::create($request->only([
            'company_id', 'branch_id', 'full_name', 'phone', 
            'start_date', 'end_date', 'status'
        ]));

        return redirect()->route('admin.employees.index')
            ->with('success', 'Çalışan başarıyla oluşturuldu.');
    }

    public function edit(Employee $employee)
    {
        $user = Auth::user();
        if ($user->company_id && $employee->company_id != $user->company_id) {
            abort(403);
        }

        $companies = Company::all();
        $branches = Branch::all();
        
        if ($user->company_id) {
            $companies = Company::where('id', $user->company_id)->get();
            $branches = Branch::where('company_id', $user->company_id)->get();
        }
        if ($user->branch_id) {
            $branches = Branch::where('id', $user->branch_id)->get();
        }

        return view('admin.employees.edit', compact('employee', 'companies', 'branches'));
    }

    public function update(Request $request, Employee $employee)
    {
        $user = Auth::user();
        if ($user->company_id && $employee->company_id != $user->company_id) {
            abort(403);
        }

        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'required|exists:branches,id',
            'full_name' => 'required|string|max:190',
            'phone' => 'nullable|string|max:50',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'boolean',
        ]);

        if ($user->company_id && $request->company_id != $user->company_id) {
            return back()->withErrors(['company_id' => 'Yetkisiz işlem.']);
        }

        $employee->update($request->only([
            'company_id', 'branch_id', 'full_name', 'phone', 
            'start_date', 'end_date', 'status'
        ]));

        return redirect()->route('admin.employees.index')
            ->with('success', 'Çalışan başarıyla güncellendi.');
    }

    public function toggleStatus(Employee $employee)
    {
        $user = Auth::user();
        if ($user->company_id && $employee->company_id != $user->company_id) {
            abort(403);
        }

        $employee->status = !$employee->status;
        $employee->save();

        return back()->with('success', 'Çalışan durumu güncellendi.');
    }
}

