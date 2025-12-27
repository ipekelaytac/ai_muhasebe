<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use App\Models\PayrollItem;
use App\Models\PayrollInstallment;
use App\Models\PayrollPayment;
use App\Models\PayrollPaymentAllocation;
use App\Models\PayrollDeduction;
use App\Models\PayrollDeductionType;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\Advance;
use App\Models\AdvanceSettlement;
use App\Models\Company;
use App\Models\Branch;
use App\Models\FinanceCategory;
use App\Models\FinanceTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = PayrollPeriod::with(['company', 'branch'])->latest();
        
        if ($user->company_id) {
            $query->where('company_id', $user->company_id);
        }
        if ($user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }
        
        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }
        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        $periods = $query->paginate(20);
        return view('admin.payroll.index', compact('periods'));
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
        
        return view('admin.payroll.create', compact('companies', 'branches'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'required|exists:branches,id',
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        if ($user->company_id && $request->company_id != $user->company_id) {
            return back()->withErrors(['company_id' => 'Yetkisiz işlem.']);
        }

        $exists = PayrollPeriod::where('company_id', $request->company_id)
            ->where('branch_id', $request->branch_id)
            ->where('year', $request->year)
            ->where('month', $request->month)
            ->exists();

        if ($exists) {
            return back()->withErrors(['month' => 'Bu dönem zaten mevcut.']);
        }

        PayrollPeriod::create($request->only(['company_id', 'branch_id', 'year', 'month', 'status']));

        return redirect()->route('admin.payroll.index')
            ->with('success', 'Bordro dönemi oluşturuldu.');
    }

    public function show(PayrollPeriod $period)
    {
        $user = Auth::user();
        if ($user->company_id && $period->company_id != $user->company_id) {
            abort(403);
        }

        $items = PayrollItem::with(['employee', 'installments', 'payments'])
            ->where('payroll_period_id', $period->id)
            ->get();

        return view('admin.payroll.show', compact('period', 'items'));
    }

    public function generate(PayrollPeriod $period)
    {
        $user = Auth::user();
        if ($user->company_id && $period->company_id != $user->company_id) {
            abort(403);
        }

        DB::beginTransaction();
        try {
            // Get active employees with active contracts
            $employees = Employee::where('company_id', $period->company_id)
                ->where('branch_id', $period->branch_id)
                ->where('status', 1)
                ->get();

            $targetDate = Carbon::create($period->year, $period->month, 1);
            $lastDayOfMonth = $targetDate->copy()->endOfMonth()->day;

            foreach ($employees as $employee) {
                // Check if item already exists
                $existing = PayrollItem::where('payroll_period_id', $period->id)
                    ->where('employee_id', $employee->id)
                    ->exists();

                if ($existing) {
                    continue;
                }

                // Check if employee started before or during this period
                if ($employee->start_date && $employee->start_date->gt($targetDate->copy()->endOfMonth())) {
                    continue; // Employee started after this period
                }

                // Get active contract for this date
                $contract = EmployeeContract::where('employee_id', $employee->id)
                    ->where('effective_from', '<=', $targetDate->toDateString())
                    ->where(function ($q) use ($targetDate) {
                        $q->whereNull('effective_to')
                          ->orWhere('effective_to', '>=', $targetDate->toDateString());
                    })
                    ->latest('effective_from')
                    ->first();

                if (!$contract) {
                    continue; // Skip employees without active contract
                }

                // Calculate deductions and advances
                $deductionTotal = PayrollDeduction::whereHas('payrollItem', function ($q) use ($period, $employee) {
                    $q->where('payroll_period_id', $period->id)
                      ->where('employee_id', $employee->id);
                })->whereNull('payroll_installment_id')
                  ->sum('amount');

                $advancesDeducted = AdvanceSettlement::whereHas('payrollItem', function ($q) use ($period, $employee) {
                    $q->where('payroll_period_id', $period->id)
                      ->where('employee_id', $employee->id);
                })->whereNull('payroll_installment_id')
                  ->sum('settled_amount');

                $netPayable = $contract->monthly_net_salary 
                    + $contract->meal_allowance 
                    - $deductionTotal 
                    - $advancesDeducted;

                // Create payroll item
                $item = PayrollItem::create([
                    'payroll_period_id' => $period->id,
                    'employee_id' => $employee->id,
                    'base_net_salary' => $contract->monthly_net_salary,
                    'meal_allowance' => $contract->meal_allowance,
                    'bonus_total' => 0,
                    'deduction_total' => $deductionTotal,
                    'advances_deducted_total' => $advancesDeducted,
                    'net_payable' => $netPayable,
                ]);

                // Create installments
                $payDay1 = min($contract->pay_day_1, $lastDayOfMonth);
                $payDay2 = min($contract->pay_day_2, $lastDayOfMonth);

                PayrollInstallment::create([
                    'payroll_item_id' => $item->id,
                    'installment_no' => 1,
                    'due_date' => Carbon::create($period->year, $period->month, $payDay1),
                    'planned_amount' => $contract->pay_amount_1,
                    'title' => 'Ayın 5\'i',
                ]);

                PayrollInstallment::create([
                    'payroll_item_id' => $item->id,
                    'installment_no' => 2,
                    'due_date' => Carbon::create($period->year, $period->month, $payDay2),
                    'planned_amount' => $contract->pay_amount_2,
                    'title' => 'Ayın 20\'si',
                ]);
            }

            DB::commit();
            return redirect()->route('admin.payroll.show', $period)
                ->with('success', 'Bordro başarıyla oluşturuldu.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Bordro oluşturulurken hata oluştu: ' . $e->getMessage()]);
        }
    }

    public function showItem(PayrollItem $item)
    {
        $user = Auth::user();
        if ($user->company_id && $item->payrollPeriod->company_id != $user->company_id) {
            abort(403);
        }

        $item->load([
            'employee', 
            'payrollPeriod', 
            'installments.paymentAllocations.payment',
            'installments.deductions.deductionType',
            'installments.advanceSettlements.advance',
            'payments' => function ($q) {
                $q->with(['allocations' => function ($q2) {
                    $q2->with('installment');
                }]);
            },
            'deductions.deductionType',
            'deductions.installment',
            'advanceSettlements.advance',
            'advanceSettlements.installment',
        ]);

        // Get deduction types for this company
        $deductionTypes = PayrollDeductionType::where('company_id', $item->payrollPeriod->company_id)
            ->where('is_active', 1)
            ->get();

        // Get open advances for this employee
        $openAdvances = Advance::where('employee_id', $item->employee_id)
            ->where('status', 1)
            ->with('settlements')
            ->get()
            ->filter(function ($advance) {
                return $advance->remaining_amount > 0;
            });

        return view('admin.payroll.item', compact('item', 'deductionTypes', 'openAdvances'));
    }

    public function addEmployeeForm(PayrollPeriod $period)
    {
        $user = Auth::user();
        if ($user->company_id && $period->company_id != $user->company_id) {
            abort(403);
        }

        // Get employees already in payroll
        $existingEmployeeIds = PayrollItem::where('payroll_period_id', $period->id)
            ->pluck('employee_id')
            ->toArray();

        // Get available employees (not in payroll, active, same company/branch)
        $availableEmployees = Employee::where('company_id', $period->company_id)
            ->where('branch_id', $period->branch_id)
            ->where('status', 1)
            ->whereNotIn('id', $existingEmployeeIds)
            ->with(['company', 'branch'])
            ->get();

        return view('admin.payroll.add-employee', compact('period', 'availableEmployees'));
    }

    public function addEmployee(Request $request, PayrollPeriod $period)
    {
        $user = Auth::user();
        if ($user->company_id && $period->company_id != $user->company_id) {
            abort(403);
        }

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
        ]);

        $employee = Employee::findOrFail($request->employee_id);

        // Check if employee already in payroll
        $existing = PayrollItem::where('payroll_period_id', $period->id)
            ->where('employee_id', $employee->id)
            ->exists();

        if ($existing) {
            return back()->withErrors(['employee_id' => 'Bu çalışan zaten bordroda mevcut.']);
        }

        // Check company/branch match
        if ($employee->company_id != $period->company_id || $employee->branch_id != $period->branch_id) {
            return back()->withErrors(['employee_id' => 'Çalışan bu şirket/şubeye ait değil.']);
        }

        DB::beginTransaction();
        try {
            $targetDate = Carbon::create($period->year, $period->month, 1);
            $lastDayOfMonth = $targetDate->copy()->endOfMonth()->day;

            // Check if employee started before or during this period
            if ($employee->start_date && $employee->start_date->gt($targetDate->copy()->endOfMonth())) {
                return back()->withErrors(['employee_id' => 'Çalışan bu dönemden sonra işe başlamış.']);
            }

            // Get active contract for this date
            $contract = EmployeeContract::where('employee_id', $employee->id)
                ->where('effective_from', '<=', $targetDate->toDateString())
                ->where(function ($q) use ($targetDate) {
                    $q->whereNull('effective_to')
                      ->orWhere('effective_to', '>=', $targetDate->toDateString());
                })
                ->latest('effective_from')
                ->first();

            if (!$contract) {
                return back()->withErrors(['employee_id' => 'Bu dönem için aktif sözleşme bulunamadı.']);
            }

            // Calculate deductions and advances
            $deductionTotal = PayrollDeduction::whereHas('payrollItem', function ($q) use ($period, $employee) {
                $q->where('payroll_period_id', $period->id)
                  ->where('employee_id', $employee->id);
            })->whereNull('payroll_installment_id')
              ->sum('amount');

            $advancesDeducted = AdvanceSettlement::whereHas('payrollItem', function ($q) use ($period, $employee) {
                $q->where('payroll_period_id', $period->id)
                  ->where('employee_id', $employee->id);
            })->whereNull('payroll_installment_id')
              ->sum('settled_amount');

            $netPayable = $contract->monthly_net_salary 
                + $contract->meal_allowance 
                - $deductionTotal 
                - $advancesDeducted;

            // Create payroll item
            $item = PayrollItem::create([
                'payroll_period_id' => $period->id,
                'employee_id' => $employee->id,
                'base_net_salary' => $contract->monthly_net_salary,
                'meal_allowance' => $contract->meal_allowance,
                'bonus_total' => 0,
                'deduction_total' => $deductionTotal,
                'advances_deducted_total' => $advancesDeducted,
                'net_payable' => $netPayable,
            ]);

            // Create installments
            $payDay1 = min($contract->pay_day_1, $lastDayOfMonth);
            $payDay2 = min($contract->pay_day_2, $lastDayOfMonth);

            PayrollInstallment::create([
                'payroll_item_id' => $item->id,
                'installment_no' => 1,
                'due_date' => Carbon::create($period->year, $period->month, $payDay1),
                'planned_amount' => $contract->pay_amount_1,
                'title' => 'Ayın 5\'i',
            ]);

            PayrollInstallment::create([
                'payroll_item_id' => $item->id,
                'installment_no' => 2,
                'due_date' => Carbon::create($period->year, $period->month, $payDay2),
                'planned_amount' => $contract->pay_amount_2,
                'title' => 'Ayın 20\'si',
            ]);

            DB::commit();
            return redirect()->route('admin.payroll.show', $period)
                ->with('success', 'Personel başarıyla bordroya eklendi.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Personel eklenirken hata oluştu: ' . $e->getMessage()]);
        }
    }

    public function addPayment(Request $request, PayrollItem $item)
    {
        $user = Auth::user();
        if ($user->company_id && $item->payrollPeriod->company_id != $user->company_id) {
            abort(403);
        }

        $request->validate([
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:cash,bank,other',
            'reference_no' => 'nullable|string|max:100',
            'allocation_type' => 'required|in:installment_1,installment_2,both,auto',
            'amount_1' => 'required_if:allocation_type,both|nullable|numeric|min:0',
            'amount_2' => 'required_if:allocation_type,both|nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $installments = $item->installments()->orderBy('installment_no')->get();
            $installment1 = $installments->where('installment_no', 1)->first();
            $installment2 = $installments->where('installment_no', 2)->first();

            $payment = PayrollPayment::create([
                'payroll_item_id' => $item->id,
                'payment_date' => $request->payment_date,
                'amount' => $request->amount,
                'method' => $request->method,
                'reference_no' => $request->reference_no,
                'created_by' => $user->id,
            ]);

            $allocations = [];
            if ($request->allocation_type === 'installment_1') {
                $allocations[] = [
                    'payroll_installment_id' => $installment1->id,
                    'allocated_amount' => $request->amount,
                ];
            } elseif ($request->allocation_type === 'installment_2') {
                $allocations[] = [
                    'payroll_installment_id' => $installment2->id,
                    'allocated_amount' => $request->amount,
                ];
            } elseif ($request->allocation_type === 'both') {
                if (abs(($request->amount_1 + $request->amount_2) - $request->amount) > 0.01) {
                    throw new \Exception('Taksit tutarları toplamı ödeme tutarına eşit olmalıdır.');
                }
                $allocations[] = [
                    'payroll_installment_id' => $installment1->id,
                    'allocated_amount' => $request->amount_1,
                ];
                $allocations[] = [
                    'payroll_installment_id' => $installment2->id,
                    'allocated_amount' => $request->amount_2,
                ];
            } else { // auto
                $remaining1 = $installment1->remaining_amount;
                $remaining2 = $installment2->remaining_amount;
                
                if ($request->amount <= $remaining1) {
                    $allocations[] = [
                        'payroll_installment_id' => $installment1->id,
                        'allocated_amount' => $request->amount,
                    ];
                } else {
                    $allocations[] = [
                        'payroll_installment_id' => $installment1->id,
                        'allocated_amount' => $remaining1,
                    ];
                    $remaining = $request->amount - $remaining1;
                    if ($remaining > 0) {
                        $allocations[] = [
                            'payroll_installment_id' => $installment2->id,
                            'allocated_amount' => min($remaining, $remaining2),
                        ];
                    }
                }
            }

            foreach ($allocations as $allocation) {
                PayrollPaymentAllocation::create([
                    'payroll_payment_id' => $payment->id,
                    'payroll_installment_id' => $allocation['payroll_installment_id'],
                    'allocated_amount' => $allocation['allocated_amount'],
                ]);
            }

            // Create finance transaction
            $category = FinanceCategory::where('company_id', $item->payrollPeriod->company_id)
                ->where('type', 'expense')
                ->where('name', 'Maaş Ödemesi')
                ->first();
            
            if ($category) {
                FinanceTransaction::create([
                    'company_id' => $item->payrollPeriod->company_id,
                    'branch_id' => $item->payrollPeriod->branch_id,
                    'type' => 'expense',
                    'category_id' => $category->id,
                    'transaction_date' => $request->payment_date,
                    'description' => $item->employee->full_name . ' - Maaş Ödemesi',
                    'amount' => $request->amount,
                    'related_table' => 'payroll_payments',
                    'related_id' => $payment->id,
                    'created_by' => $user->id,
                ]);
            }

            DB::commit();
            return redirect()->route('admin.payroll.item', $item)
                ->with('success', 'Ödeme başarıyla eklendi.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function addDeduction(Request $request, PayrollItem $item)
    {
        $user = Auth::user();
        if ($user->company_id && $item->payrollPeriod->company_id != $user->company_id) {
            abort(403);
        }

        $request->validate([
            'deduction_type_id' => 'required|exists:payroll_deduction_types,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'installment_id' => 'nullable|exists:payroll_installments,id',
        ]);

        if ($request->installment_id) {
            $installment = PayrollInstallment::findOrFail($request->installment_id);
            if ($installment->payroll_item_id != $item->id) {
                return back()->withErrors(['installment_id' => 'Geçersiz taksit.']);
            }
        }

        PayrollDeduction::create([
            'payroll_item_id' => $item->id,
            'payroll_installment_id' => $request->installment_id,
            'deduction_type_id' => $request->deduction_type_id,
            'amount' => $request->amount,
            'description' => $request->description,
            'created_by' => $user->id,
        ]);

        // Recalculate item totals
        $item->deduction_total = $item->deductions()->sum('amount');
        $item->net_payable = $item->base_net_salary 
            + $item->meal_allowance 
            + $item->bonus_total 
            - $item->deduction_total 
            - $item->advances_deducted_total;
        $item->save();

        return redirect()->route('admin.payroll.item', $item)
            ->with('success', 'Kesinti başarıyla eklendi.');
    }

    public function addAdvance(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'advance_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:cash,bank,other',
            'note' => 'nullable|string',
        ]);

        $employee = Employee::findOrFail($request->employee_id);
        if ($user->company_id && $employee->company_id != $user->company_id) {
            abort(403);
        }

        DB::beginTransaction();
        try {
            $advance = Advance::create([
                'company_id' => $employee->company_id,
                'branch_id' => $employee->branch_id,
                'employee_id' => $employee->id,
                'advance_date' => $request->advance_date,
                'amount' => $request->amount,
                'method' => $request->method,
                'note' => $request->note,
                'status' => 1,
            ]);

            // Create finance transaction
            $category = FinanceCategory::where('company_id', $employee->company_id)
                ->where('type', 'expense')
                ->where('name', 'Avans')
                ->first();
            
            if ($category) {
                FinanceTransaction::create([
                    'company_id' => $employee->company_id,
                    'branch_id' => $employee->branch_id,
                    'type' => 'expense',
                    'category_id' => $category->id,
                    'transaction_date' => $request->advance_date,
                    'description' => $employee->full_name . ' - Avans',
                    'amount' => $request->amount,
                    'employee_id' => $employee->id,
                    'related_table' => 'advances',
                    'related_id' => $advance->id,
                    'created_by' => $user->id,
                ]);
            }

            DB::commit();
            return redirect()->route('admin.employees.index')
                ->with('success', 'Avans başarıyla oluşturuldu.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function settleAdvance(Request $request, PayrollItem $item)
    {
        $user = Auth::user();
        if ($user->company_id && $item->payrollPeriod->company_id != $user->company_id) {
            abort(403);
        }

        $request->validate([
            'advance_id' => 'required|exists:advances,id',
            'settled_amount' => 'required|numeric|min:0.01',
            'settled_date' => 'required|date',
            'installment_id' => 'nullable|exists:payroll_installments,id',
        ]);

        $advance = Advance::findOrFail($request->advance_id);
        if ($advance->employee_id != $item->employee_id) {
            return back()->withErrors(['advance_id' => 'Avans bu çalışana ait değil.']);
        }

        $remaining = $advance->remaining_amount;
        if ($request->settled_amount > $remaining) {
            return back()->withErrors(['settled_amount' => 'Kalan tutardan fazla ödeme yapılamaz.']);
        }

        if ($request->installment_id) {
            $installment = PayrollInstallment::findOrFail($request->installment_id);
            if ($installment->payroll_item_id != $item->id) {
                return back()->withErrors(['installment_id' => 'Geçersiz taksit.']);
            }
        }

        AdvanceSettlement::create([
            'advance_id' => $advance->id,
            'payroll_item_id' => $item->id,
            'payroll_installment_id' => $request->installment_id,
            'settled_amount' => $request->settled_amount,
            'settled_date' => $request->settled_date,
            'created_by' => $user->id,
        ]);

        // Update advance status if fully settled
        if ($advance->remaining_amount <= 0) {
            $advance->status = 0;
            $advance->save();
        }

        // Recalculate item totals
        $item->advances_deducted_total = $item->advanceSettlements()->sum('settled_amount');
        $item->net_payable = $item->base_net_salary 
            + $item->meal_allowance 
            + $item->bonus_total 
            - $item->deduction_total 
            - $item->advances_deducted_total;
        $item->save();

        return redirect()->route('admin.payroll.item', $item)
            ->with('success', 'Avans mahsuplaşması başarıyla eklendi.');
    }
}

