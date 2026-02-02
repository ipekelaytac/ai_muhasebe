<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use App\Models\PayrollItem;
use App\Models\Employee;
use App\Models\PayrollDeduction;
// Legacy models removed - Advance and FinanceTransaction tables dropped
// TODO: Migrate advance reports to use documents (type: advance_given/advance_received)
// TODO: Migrate finance transaction reports to use documents
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $year = $request->input('year', now()->year);
        $month = $request->input('month');
        $reportType = $request->input('type', 'payroll'); // payroll, employee, advance, deduction, finance, meal

        $data = [];

        // Bordro Raporu
        if ($reportType === 'payroll' || $reportType === 'all') {
            $payrollQuery = PayrollPeriod::with(['company', 'branch', 'payrollItems'])
                ->where('year', $year);
            
            if ($month) {
                $payrollQuery->where('month', $month);
            }
            
            if ($user->company_id) {
                $payrollQuery->where('company_id', $user->company_id);
            }
            if ($user->branch_id) {
                $payrollQuery->where('branch_id', $user->branch_id);
            }

            $periods = $payrollQuery->get();
            
            $payrollItems = PayrollItem::whereHas('payrollPeriod', function ($q) use ($year, $month, $user) {
                $q->where('year', $year);
                if ($month) {
                    $q->where('month', $month);
                }
                if ($user->company_id) {
                    $q->where('company_id', $user->company_id);
                }
                if ($user->branch_id) {
                    $q->where('branch_id', $user->branch_id);
                }
            })->with(['employee', 'payrollPeriod', 'payments'])->get();

            $data['payroll'] = [
                'total_net_salary' => $payrollItems->sum('base_net_salary'),
                'total_meal_allowance' => $payrollItems->sum('meal_allowance'),
                'total_bonus' => $payrollItems->sum('bonus_total'),
                'total_deduction' => $payrollItems->sum('deduction_total'),
                'total_advances_deducted' => $payrollItems->sum('advances_deducted_total'),
                'total_net_payable' => $payrollItems->sum('net_payable'),
                'total_paid' => $payrollItems->sum(function ($item) {
                    return $item->payments->sum('amount');
                }),
                'total_remaining' => $payrollItems->sum('net_payable') - $payrollItems->sum(function ($item) {
                    return $item->payments->sum('amount');
                }),
                'employee_count' => $payrollItems->unique('employee_id')->count(),
                'periods' => $periods,
                'items' => $payrollItems,
            ];
        }

        // Çalışan Raporu
        if ($reportType === 'employee' || $reportType === 'all') {
            $employeeQuery = Employee::with(['company', 'branch', 'contracts', 'payrollItems']);
            
            if ($user->company_id) {
                $employeeQuery->where('company_id', $user->company_id);
            }
            if ($user->branch_id) {
                $employeeQuery->where('branch_id', $user->branch_id);
            }

            $employees = $employeeQuery->get();
            
            $data['employee'] = [
                'total' => $employees->count(),
                'active' => $employees->where('status', 1)->count(),
                'inactive' => $employees->where('status', 0)->count(),
                'with_contract' => $employees->filter(function ($emp) {
                    return $emp->activeContract !== null;
                })->count(),
                'employees' => $employees,
            ];
        }

        // Avans Raporu - TODO: Migrate to use documents with type advance_given/advance_received
        if ($reportType === 'advance' || $reportType === 'all') {
            // Legacy Advance model removed - table dropped
            $data['advance'] = [
                'total_amount' => 0,
                'total_settled' => 0,
                'total_remaining' => 0,
                'open_count' => 0,
                'closed_count' => 0,
                'advances' => collect([]),
                'note' => 'Avans raporu yeni muhasebe sistemine taşınmıştır. Lütfen Muhasebe > Raporlar menüsünü kullanın.',
            ];
        }

        // Kesinti Raporu
        if ($reportType === 'deduction' || $reportType === 'all') {
            $deductionQuery = PayrollDeduction::with(['payrollItem.employee', 'deductionType', 'payrollItem.payrollPeriod'])
                ->whereHas('payrollItem.payrollPeriod', function ($q) use ($year, $month, $user) {
                    $q->where('year', $year);
                    if ($month) {
                        $q->where('month', $month);
                    }
                    if ($user->company_id) {
                        $q->where('company_id', $user->company_id);
                    }
                    if ($user->branch_id) {
                        $q->where('branch_id', $user->branch_id);
                    }
                });
            
            $deductions = $deductionQuery->get();
            
            $data['deduction'] = [
                'total_amount' => $deductions->sum('amount'),
                'count' => $deductions->count(),
                'by_type' => $deductions->groupBy(function ($deduction) {
                    return $deduction->deductionType->name ?? 'Tanımsız';
                })->map(function ($group) {
                    return [
                        'total' => $group->sum('amount'),
                        'count' => $group->count(),
                    ];
                }),
                'deductions' => $deductions,
            ];
        }

        // Finans Raporu - TODO: Migrate to use documents
        if ($reportType === 'finance' || $reportType === 'all') {
            // Legacy FinanceTransaction model removed - table dropped
            $data['finance'] = [
                'total_income' => 0,
                'total_expense' => 0,
                'net' => 0,
                'income_count' => 0,
                'expense_count' => 0,
                'by_category' => collect([]),
                'transactions' => collect([]),
                'note' => 'Finans raporu yeni muhasebe sistemine taşınmıştır. Lütfen Muhasebe > Raporlar menüsünü kullanın.',
            ];
        }

        // Yemek Yardımı Raporu
        if ($reportType === 'meal' || $reportType === 'all') {
            $mealQuery = PayrollItem::with(['employee', 'payrollPeriod'])
                ->whereHas('payrollPeriod', function ($q) use ($year, $month, $user) {
                    $q->where('year', $year);
                    if ($month) {
                        $q->where('month', $month);
                    }
                    if ($user->company_id) {
                        $q->where('company_id', $user->company_id);
                    }
                    if ($user->branch_id) {
                        $q->where('branch_id', $user->branch_id);
                    }
                })
                ->where('meal_allowance', '>', 0);
            
            $mealItems = $mealQuery->get();
            
            $data['meal'] = [
                'total' => $mealItems->sum('meal_allowance'),
                'employee_count' => $mealItems->unique('employee_id')->count(),
                'items' => $mealItems,
            ];
        }

        return view('admin.reports.index', compact('data', 'year', 'month', 'reportType', 'user'));
    }
}

