<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class MealAllowanceController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        $query = PayrollItem::with(['employee', 'payrollPeriod'])
            ->whereHas('payrollPeriod', function ($q) use ($year, $month, $user) {
                $q->where('year', $year)
                  ->where('month', $month);
                
                if ($user->company_id) {
                    $q->where('company_id', $user->company_id);
                }
                if ($user->branch_id) {
                    $q->where('branch_id', $user->branch_id);
                }
            })
            ->where('meal_allowance', '>', 0);

        $items = $query->get();

        // Group by company and branch
        $summary = [
            'total_meal_allowance' => $items->sum('meal_allowance'),
            'total_employees' => $items->count(),
            'by_company' => $items->groupBy(function ($item) {
                return $item->payrollPeriod->company->name;
            })->map(function ($group) {
                return [
                    'total' => $group->sum('meal_allowance'),
                    'count' => $group->count(),
                ];
            }),
            'by_branch' => $items->groupBy(function ($item) {
                return $item->payrollPeriod->branch->name;
            })->map(function ($group) {
                return [
                    'total' => $group->sum('meal_allowance'),
                    'count' => $group->count(),
                ];
            }),
        ];

        return view('admin.meal-allowance.index', compact('items', 'summary', 'year', 'month'));
    }

    public function report(Request $request)
    {
        $user = Auth::user();
        $year = $request->input('year', now()->year);
        $startMonth = $request->input('start_month', 1);
        $endMonth = $request->input('end_month', 12);

        $query = PayrollItem::with(['employee', 'payrollPeriod.company', 'payrollPeriod.branch'])
            ->whereHas('payrollPeriod', function ($q) use ($year, $startMonth, $endMonth, $user) {
                $q->where('year', $year)
                  ->whereBetween('month', [$startMonth, $endMonth]);
                
                if ($user->company_id) {
                    $q->where('company_id', $user->company_id);
                }
                if ($user->branch_id) {
                    $q->where('branch_id', $user->branch_id);
                }
            })
            ->where('meal_allowance', '>', 0);

        $items = $query->get();

        // Monthly summary
        $monthlySummary = [];
        for ($m = $startMonth; $m <= $endMonth; $m++) {
            $monthItems = $items->filter(function ($item) use ($m) {
                return $item->payrollPeriod->month == $m;
            });
            
            $monthlySummary[$m] = [
                'month_name' => Carbon::create($year, $m, 1)->locale('tr')->monthName,
                'total' => $monthItems->sum('meal_allowance'),
                'count' => $monthItems->count(),
            ];
        }

        $total = $items->sum('meal_allowance');
        $totalEmployees = $items->unique('employee_id')->count();

        return view('admin.meal-allowance.report', compact('items', 'monthlySummary', 'year', 'startMonth', 'endMonth', 'total', 'totalEmployees'));
    }
}

