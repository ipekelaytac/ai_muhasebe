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
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $branches = Branch::where('company_id', $user->company_id)->get();
        if ($user->branch_id) {
            $branches = Branch::where('id', $user->branch_id)->get();
        }
        
        return view('admin.employees.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'full_name' => 'required|string|max:190',
            'phone' => 'nullable|string|max:50',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'boolean',
        ]);

        $branch = Branch::findOrFail($request->branch_id);
        if ($branch->company_id != $user->company_id) {
            return back()->withErrors(['branch_id' => 'Yetkisiz işlem.']);
        }

        $employee = Employee::create([
            'company_id' => $user->company_id,
            'branch_id' => $request->branch_id,
            'full_name' => $request->full_name,
            'phone' => $request->phone,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => $request->has('status'),
        ]);

        // Ensure Party is created (EmployeeObserver should handle this automatically)
        // Refresh to get party_id if Observer created it
        $employee->refresh();
        
        if (!$employee->party_id) {
            // Fallback: manually trigger Observer if it didn't fire
            $observer = new \App\Observers\EmployeeObserver();
            $observer->created($employee);
            $employee->refresh();
        }

        return redirect()->route('admin.employees.index')
            ->with('success', 'Çalışan başarıyla oluşturuldu.');
    }

    public function edit(Employee $employee)
    {
        $user = Auth::user();
        if ($user->company_id && $employee->company_id != $user->company_id) {
            abort(403);
        }

        $branches = Branch::where('company_id', $user->company_id)->get();
        if ($user->branch_id) {
            $branches = Branch::where('id', $user->branch_id)->get();
        }

        return view('admin.employees.edit', compact('employee', 'branches'));
    }

    public function update(Request $request, Employee $employee)
    {
        $user = Auth::user();
        if ($user->company_id && $employee->company_id != $user->company_id) {
            abort(403);
        }

        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'full_name' => 'required|string|max:190',
            'phone' => 'nullable|string|max:50',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'boolean',
        ]);

        $branch = Branch::findOrFail($request->branch_id);
        if ($branch->company_id != $user->company_id) {
            return back()->withErrors(['branch_id' => 'Yetkisiz işlem.']);
        }

        $employee->update([
            'branch_id' => $request->branch_id,
            'full_name' => $request->full_name,
            'phone' => $request->phone,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => $request->has('status'),
        ]);

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

