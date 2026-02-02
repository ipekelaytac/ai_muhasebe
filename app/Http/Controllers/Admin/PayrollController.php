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
// Legacy models removed - Advance and FinanceTransaction tables dropped
// TODO: Migrate advance functionality to use documents (type: advance_given/advance_received)
// TODO: Migrate finance transactions to use documents
use App\Models\Company;
use App\Models\Branch;
use App\Models\FinanceCategory;
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
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $branches = Branch::where('company_id', $user->company_id)->get();
        if ($user->branch_id) {
            $branches = Branch::where('id', $user->branch_id)->get();
        }
        
        return view('admin.payroll.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $branch = Branch::findOrFail($request->branch_id);
        if ($branch->company_id != $user->company_id) {
            return back()->withErrors(['branch_id' => 'Yetkisiz işlem.']);
        }

        $exists = PayrollPeriod::where('company_id', $user->company_id)
            ->where('branch_id', $request->branch_id)
            ->where('year', $request->year)
            ->where('month', $request->month)
            ->exists();

        if ($exists) {
            return back()->withErrors(['month' => 'Bu dönem zaten mevcut.']);
        }

        PayrollPeriod::create([
            'company_id' => $user->company_id,
            'branch_id' => $request->branch_id,
            'year' => $request->year,
            'month' => $request->month,
            'status' => $request->has('status') ? (bool) $request->status : true, // Default: açık (1)
        ]);

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

                // Legacy advance settlements removed - table dropped
                // TODO: Migrate to use payment allocations to advance documents
                $advancesDeducted = 0;

                // Calculate overtime total for the period
                $overtimeTotal = \App\Models\Overtime::where('employee_id', $employee->id)
                    ->whereBetween('overtime_date', [
                        $targetDate->toDateString(),
                        $targetDate->copy()->endOfMonth()->toDateString()
                    ])
                    ->sum('amount');

                $netPayable = $contract->monthly_net_salary 
                    + $contract->meal_allowance 
                    + $overtimeTotal
                    - $deductionTotal 
                    - $advancesDeducted;

                // Create payroll item
                $item = PayrollItem::create([
                    'payroll_period_id' => $period->id,
                    'employee_id' => $employee->id,
                    'base_net_salary' => $contract->monthly_net_salary,
                    'meal_allowance' => $contract->meal_allowance,
                    'overtime_total' => $overtimeTotal,
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
            'employee.company', 
            'employee.branch', 
            'payrollPeriod.company', 
            'payrollPeriod.branch',
            'installments.payments',
            'installments.deductions.deductionType',
            // Legacy advance settlements removed
            // 'installments.advanceSettlements.advance',
            'deductions.deductionType',
            'deductions.installment',
            // 'advanceSettlements.advance',
            // 'advanceSettlements.installment',
            'payments.allocations.installment'
        ]);
        
        // Get overtime records for this period
        $overtimes = \App\Models\Overtime::where('employee_id', $item->employee_id)
            ->whereBetween('overtime_date', [
                $item->payrollPeriod->year . '-' . str_pad($item->payrollPeriod->month, 2, '0', STR_PAD_LEFT) . '-01',
                \Carbon\Carbon::create($item->payrollPeriod->year, $item->payrollPeriod->month, 1)->endOfMonth()->toDateString()
            ])
            ->orderBy('overtime_date')
            ->get();
        
        $item->load([
            'employee', 
            'payrollPeriod', 
            'installments.paymentAllocations.payment',
            'installments.deductions.deductionType',
            // Legacy advance settlements removed
            // 'installments.advanceSettlements.advance',
            'payments' => function ($q) {
                $q->with(['allocations' => function ($q2) {
                    $q2->with('installment');
                }]);
            },
            'deductions.deductionType',
            'deductions.installment',
            // 'advanceSettlements.advance',
            // 'advanceSettlements.installment',
        ]);
        
        // Get overtime records for this period
        $overtimes = \App\Models\Overtime::where('employee_id', $item->employee_id)
            ->whereBetween('overtime_date', [
                \Carbon\Carbon::create($item->payrollPeriod->year, $item->payrollPeriod->month, 1)->toDateString(),
                \Carbon\Carbon::create($item->payrollPeriod->year, $item->payrollPeriod->month, 1)->endOfMonth()->toDateString()
            ])
            ->orderBy('overtime_date')
            ->get();

        // Get deduction types for this company
        $deductionTypes = PayrollDeductionType::where('company_id', $item->payrollPeriod->company_id)
            ->where('is_active', 1)
            ->get();

        // TODO: Migrate to use documents with type advance_given
        // Legacy Advance model removed - table dropped
        $openAdvances = collect([]);

        // Get open debts for this employee
        $openDebts = \App\Models\EmployeeDebt::where('employee_id', $item->employee_id)
            ->where('status', 1)
            ->with('payments')
            ->get()
            ->filter(function ($debt) {
                return $debt->remaining_amount > 0;
            });

        return view('admin.payroll.item', compact('item', 'deductionTypes', 'openAdvances', 'overtimes', 'openDebts'));
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

            // Legacy advance settlements removed - table dropped
            // TODO: Migrate to use payment allocations to advance documents
            $advancesDeducted = 0;

            // Calculate overtime total for the period
            $overtimeTotal = \App\Models\Overtime::where('employee_id', $employee->id)
                ->whereBetween('overtime_date', [
                    $targetDate->toDateString(),
                    $targetDate->copy()->endOfMonth()->toDateString()
                ])
                ->sum('amount');

            $netPayable = $contract->monthly_net_salary 
                + $contract->meal_allowance 
                + $overtimeTotal
                - $deductionTotal 
                - $advancesDeducted;

            // Create payroll item
            $item = PayrollItem::create([
                'payroll_period_id' => $period->id,
                'employee_id' => $employee->id,
                'base_net_salary' => $contract->monthly_net_salary,
                'meal_allowance' => $contract->meal_allowance,
                'overtime_total' => $overtimeTotal,
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

            // TODO: Migrate to create document with type payroll_due instead of FinanceTransaction
            // Legacy FinanceTransaction model removed - table dropped

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
            + ($item->overtime_total ?? 0)
            + $item->bonus_total 
            - $item->deduction_total 
            - $item->advances_deducted_total
            - $item->debtPayments()->sum('amount');
        $item->save();

        return redirect()->route('admin.payroll.item', $item)
            ->with('success', 'Kesinti başarıyla eklendi.');
    }

    /**
     * @deprecated Legacy advance functionality removed
     * TODO: Migrate to use documents with type advance_given + payments
     * This method is disabled - advance tables dropped
     */
    public function addAdvance(Request $request)
    {
        return back()->withErrors(['error' => 'Avans özelliği yeni muhasebe sistemine taşınmıştır. Lütfen Muhasebe > Tahakkuklar menüsünden avans belgesi oluşturun.']);
    }

    /**
     * @deprecated Legacy advance settlement functionality removed
     * TODO: Migrate to use payment allocations to advance documents
     * This method is disabled - advance_settlements table dropped
     */
    public function settleAdvance(Request $request, PayrollItem $item)
    {
        return back()->withErrors(['error' => 'Avans mahsuplaşma özelliği yeni muhasebe sistemine taşınmıştır. Lütfen Muhasebe > Ödeme/Tahsilat menüsünden ödeme yapıp avans belgesine dağıtın.']);
    }

    public function addDebtPayment(Request $request, PayrollItem $item)
    {
        $user = Auth::user();
        if ($user->company_id && $item->payrollPeriod->company_id != $user->company_id) {
            abort(403);
        }

        $request->validate([
            'employee_debt_id' => 'required|exists:employee_debts,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $debt = \App\Models\EmployeeDebt::findOrFail($request->employee_debt_id);
        if ($debt->employee_id != $item->employee_id) {
            return back()->withErrors(['employee_debt_id' => 'Bu borç bu çalışana ait değil.']);
        }

        if ($debt->remaining_amount < $request->amount) {
            return back()->withErrors(['amount' => 'Ödeme tutarı kalan borçtan fazla olamaz.']);
        }

        \App\Models\EmployeeDebtPayment::create([
            'employee_debt_id' => $debt->id,
            'payroll_item_id' => $item->id,
            'amount' => $request->amount,
            'payment_date' => $request->payment_date,
            'notes' => $request->notes,
            'created_by' => $user->id,
        ]);

        // Update debt status if fully paid
        if ($debt->remaining_amount <= 0) {
            $debt->status = 0;
            $debt->save();
        }

        // Recalculate item totals
        $item->advances_deducted_total = $item->advanceSettlements()->sum('settled_amount');
        $item->net_payable = $item->base_net_salary 
            + $item->meal_allowance 
            + ($item->overtime_total ?? 0)
            + $item->bonus_total 
            - $item->deduction_total 
            - $item->advances_deducted_total
            - $item->debtPayments()->sum('amount');
        $item->save();

        return redirect()->route('admin.payroll.item', $item)
            ->with('success', 'Borç ödemesi başarıyla eklendi.');
    }

    public function deleteDebtPayment(PayrollItem $item, \App\Models\EmployeeDebtPayment $debtPayment)
    {
        $user = Auth::user();
        if ($user->company_id && $item->payrollPeriod->company_id != $user->company_id) {
            abort(403);
        }

        if ($debtPayment->payroll_item_id != $item->id) {
            abort(404);
        }

        DB::beginTransaction();
        try {
            $debt = $debtPayment->employeeDebt;
            $debtPayment->delete();

            // Update debt status if needed
            if ($debt->remaining_amount > 0 && $debt->status == 0) {
                $debt->status = 1;
                $debt->save();
            }

            // Recalculate item totals
            // Legacy advance settlements removed - table dropped
            // TODO: Migrate to use payment allocations to advance documents
            $item->advances_deducted_total = 0; // $item->advanceSettlements()->sum('settled_amount');
            $item->net_payable = $item->base_net_salary 
                + $item->meal_allowance 
                + ($item->overtime_total ?? 0)
                + $item->bonus_total 
                - $item->deduction_total 
                - $item->advances_deducted_total
                - $item->debtPayments()->sum('amount');
            $item->save();

            DB::commit();
            return redirect()->route('admin.payroll.item', $item)
                ->with('success', 'Borç ödemesi başarıyla silindi.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function deletePayment(PayrollItem $item, PayrollPayment $payment)
    {
        $user = Auth::user();
        if ($user->company_id && $item->payrollPeriod->company_id != $user->company_id) {
            abort(403);
        }

        if ($payment->payroll_item_id != $item->id) {
            abort(404);
        }

        DB::beginTransaction();
        try {
            // Delete allocations
            $payment->allocations()->delete();
            
            // Delete payment
            $payment->delete();

            DB::commit();
            return redirect()->route('admin.payroll.item', $item)
                ->with('success', 'Ödeme başarıyla silindi.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Ödeme silinirken hata oluştu: ' . $e->getMessage()]);
        }
    }

    public function deleteDeduction(PayrollItem $item, PayrollDeduction $deduction)
    {
        $user = Auth::user();
        if ($user->company_id && $item->payrollPeriod->company_id != $user->company_id) {
            abort(403);
        }

        if ($deduction->payroll_item_id != $item->id) {
            abort(404);
        }

        DB::beginTransaction();
        try {
            $deduction->delete();

            // Recalculate item totals
            $item->deduction_total = $item->deductions()->sum('amount');
            $item->net_payable = $item->base_net_salary 
                + $item->meal_allowance 
                + $item->bonus_total 
                - $item->deduction_total 
                - $item->advances_deducted_total;
            $item->save();

            DB::commit();
            return redirect()->route('admin.payroll.item', $item)
                ->with('success', 'Kesinti başarıyla silindi.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Kesinti silinirken hata oluştu: ' . $e->getMessage()]);
        }
    }

    /**
     * @deprecated Legacy advance settlement functionality removed
     * TODO: Migrate to use payment allocations to advance documents
     */
    public function deleteAdvanceSettlement(PayrollItem $item, $settlement)
    {
        $user = Auth::user();
        if ($user->company_id && $item->payrollPeriod->company_id != $user->company_id) {
            abort(403);
        }

        return back()->withErrors(['error' => 'Avans mahsuplaşma özelliği yeni muhasebe sistemine taşınmıştır. Lütfen Muhasebe > Ödeme/Tahsilat menüsünden dağıtımı iptal edin.']);
    }
}

