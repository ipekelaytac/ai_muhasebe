<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SalaryCalculatorController extends Controller
{
    public function index()
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
        
        return view('admin.salary-calculator.index', compact('employees'));
    }

    public function calculate(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'overtime_hours' => 'nullable|numeric|min:0',
            'late_hours' => 'nullable|numeric|min:0',
            'missing_hours' => 'nullable|numeric|min:0',
        ]);

        $employee = Employee::with(['company', 'branch'])->findOrFail($request->employee_id);
        
        if ($user->company_id && $employee->company_id != $user->company_id) {
            return back()->withErrors(['employee_id' => 'Yetkisiz işlem.']);
        }

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        
        // Calculate days (inclusive of both start and end dates)
        $days = $startDate->diffInDays($endDate) + 1;
        
        // Get active contract for the start date
        $contract = EmployeeContract::where('employee_id', $employee->id)
            ->where('effective_from', '<=', $startDate->toDateString())
            ->where(function ($q) use ($startDate) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $startDate->toDateString());
            })
            ->latest('effective_from')
            ->first();

        if (!$contract) {
            return back()->withErrors(['employee_id' => 'Bu tarih aralığı için aktif sözleşme bulunamadı.']);
        }

        // Calculate daily salary (monthly salary / 30)
        $dailySalary = $contract->monthly_net_salary / 30;
        
        // Calculate total for the period
        $calculatedAmount = $dailySalary * $days;
        
        // Calculate meal allowance (if applicable)
        $dailyMealAllowance = $contract->meal_allowance / 30;
        $calculatedMealAllowance = $dailyMealAllowance * $days;
        
        // Calculate overtime (mesai)
        $overtimeHours = $request->input('overtime_hours', 0);
        $hourlyOvertimeRate = ($contract->monthly_net_salary / 225) * 1.5; // Maaş / 225 * 1.5
        $calculatedOvertime = $hourlyOvertimeRate * $overtimeHours;
        
        // Calculate late arrival deduction (geç gelme kesintisi)
        $lateHours = $request->input('late_hours', 0);
        $hourlySalaryRate = $contract->monthly_net_salary / 225; // Saatlik maaş (225 saat/ay)
        $calculatedLateDeduction = $hourlySalaryRate * $lateHours;
        
        // Calculate missing hours deduction (eksik mesai kesintisi)
        $missingHours = $request->input('missing_hours', 0);
        $calculatedMissingDeduction = $hourlySalaryRate * $missingHours;
        
        // Total (kesintiler çıkarılır)
        $totalAmount = $calculatedAmount + $calculatedMealAllowance + $calculatedOvertime - $calculatedLateDeduction - $calculatedMissingDeduction;

        $result = [
            'employee' => $employee,
            'contract' => $contract,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days' => $days,
            'monthly_salary' => $contract->monthly_net_salary,
            'daily_salary' => $dailySalary,
            'calculated_amount' => $calculatedAmount,
            'monthly_meal_allowance' => $contract->meal_allowance,
            'daily_meal_allowance' => $dailyMealAllowance,
            'calculated_meal_allowance' => $calculatedMealAllowance,
            'overtime_hours' => $overtimeHours,
            'hourly_overtime_rate' => $hourlyOvertimeRate,
            'calculated_overtime' => $calculatedOvertime,
            'late_hours' => $lateHours,
            'hourly_salary_rate' => $hourlySalaryRate,
            'calculated_late_deduction' => $calculatedLateDeduction,
            'missing_hours' => $missingHours,
            'calculated_missing_deduction' => $calculatedMissingDeduction,
            'total_amount' => $totalAmount,
        ];

        $query = Employee::with(['company', 'branch'])->where('status', 1);
        
        if ($user->company_id) {
            $query->where('company_id', $user->company_id);
        }
        if ($user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }
        
        $employees = $query->get();

        return view('admin.salary-calculator.index', compact('employees', 'result'));
    }
}

