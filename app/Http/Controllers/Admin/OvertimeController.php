<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Overtime;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class OvertimeController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Overtime::with(['company', 'branch', 'employee', 'creator'])->latest('overtime_date');
        
        if ($user->company_id) {
            $query->where('company_id', $user->company_id);
        }
        if ($user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }
        
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('date_from')) {
            $query->where('overtime_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('overtime_date', '<=', $request->date_to);
        }
        
        $overtimes = $query->paginate(20);
        
        $employees = Employee::where('company_id', $user->company_id ?? 0)
            ->where('status', 1)
            ->orderBy('full_name')
            ->get();
        
        return view('admin.overtimes.index', compact('overtimes', 'employees'));
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
        
        return view('admin.overtimes.create', compact('branches', 'employees'));
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
            'overtime_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'notes' => 'nullable|string',
        ]);

        $branch = Branch::findOrFail($request->branch_id);
        if ($branch->company_id != $user->company_id) {
            return back()->withErrors(['branch_id' => 'Yetkisiz işlem.']);
        }

        $employee = Employee::findOrFail($request->employee_id);
        if ($employee->company_id != $user->company_id) {
            return back()->withErrors(['employee_id' => 'Yetkisiz işlem.']);
        }

        // Get active contract for the overtime date
        $contract = EmployeeContract::where('employee_id', $employee->id)
            ->where('effective_from', '<=', $request->overtime_date)
            ->where(function ($q) use ($request) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $request->overtime_date);
            })
            ->latest('effective_from')
            ->first();

        if (!$contract) {
            return back()->withErrors(['employee_id' => 'Bu tarih için aktif sözleşme bulunamadı.']);
        }

        // Calculate hours
        $startTime = Carbon::parse($request->overtime_date . ' ' . $request->start_time);
        $endTime = Carbon::parse($request->overtime_date . ' ' . $request->end_time);
        
        // If end time is before start time, assume it's next day
        if ($endTime->lt($startTime)) {
            $endTime->addDay();
        }
        
        $totalMinutes = $startTime->diffInMinutes($endTime);
        $hours = $totalMinutes / 60.0;
        
        // Calculate rate (1.5x of hourly salary)
        $hourlySalary = $contract->monthly_net_salary / 30 / 8; // Daily salary / 8 hours
        $rate = $hourlySalary * 1.5; // 1.5x multiplier
        $amount = $hours * $rate;

        Overtime::create([
            'company_id' => $user->company_id,
            'branch_id' => $request->branch_id,
            'employee_id' => $request->employee_id,
            'overtime_date' => $request->overtime_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'hours' => round($hours, 2),
            'rate' => round($rate, 2),
            'amount' => round($amount, 2),
            'notes' => $request->notes,
            'created_by' => $user->id,
        ]);

        return redirect()->route('admin.overtimes.index')
            ->with('success', 'Mesai başarıyla oluşturuldu.');
    }

    public function edit(Overtime $overtime)
    {
        $user = Auth::user();
        if ($user->company_id && $overtime->company_id != $user->company_id) {
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
        
        return view('admin.overtimes.edit', compact('overtime', 'branches', 'employees'));
    }

    public function update(Request $request, Overtime $overtime)
    {
        $user = Auth::user();
        if ($user->company_id && $overtime->company_id != $user->company_id) {
            abort(403);
        }

        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'employee_id' => 'required|exists:employees,id',
            'overtime_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'notes' => 'nullable|string',
        ]);

        $branch = Branch::findOrFail($request->branch_id);
        if ($branch->company_id != $user->company_id) {
            return back()->withErrors(['branch_id' => 'Yetkisiz işlem.']);
        }

        $employee = Employee::findOrFail($request->employee_id);
        if ($employee->company_id != $user->company_id) {
            return back()->withErrors(['employee_id' => 'Yetkisiz işlem.']);
        }

        // Get active contract for the overtime date
        $contract = EmployeeContract::where('employee_id', $employee->id)
            ->where('effective_from', '<=', $request->overtime_date)
            ->where(function ($q) use ($request) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $request->overtime_date);
            })
            ->latest('effective_from')
            ->first();

        if (!$contract) {
            return back()->withErrors(['employee_id' => 'Bu tarih için aktif sözleşme bulunamadı.']);
        }

        // Calculate hours
        $startTime = Carbon::parse($request->overtime_date . ' ' . $request->start_time);
        $endTime = Carbon::parse($request->overtime_date . ' ' . $request->end_time);
        
        // If end time is before start time, assume it's next day
        if ($endTime->lt($startTime)) {
            $endTime->addDay();
        }
        
        $totalMinutes = $startTime->diffInMinutes($endTime);
        $hours = $totalMinutes / 60.0;
        
        // Calculate rate (1.5x of hourly salary)
        $hourlySalary = $contract->monthly_net_salary / 30 / 8; // Daily salary / 8 hours
        $rate = $hourlySalary * 1.5; // 1.5x multiplier
        $amount = $hours * $rate;

        $overtime->update([
            'branch_id' => $request->branch_id,
            'employee_id' => $request->employee_id,
            'overtime_date' => $request->overtime_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'hours' => round($hours, 2),
            'rate' => round($rate, 2),
            'amount' => round($amount, 2),
            'notes' => $request->notes,
        ]);

        return redirect()->route('admin.overtimes.index')
            ->with('success', 'Mesai başarıyla güncellendi.');
    }

    public function destroy(Overtime $overtime)
    {
        $user = Auth::user();
        if ($user->company_id && $overtime->company_id != $user->company_id) {
            abort(403);
        }

        $overtime->delete();

        return redirect()->route('admin.overtimes.index')
            ->with('success', 'Mesai başarıyla silindi.');
    }
}

