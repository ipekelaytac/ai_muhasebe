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
use App\Domain\Accounting\Services\PayrollDocumentService;
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
                // First try new accounting system (overtime_due documents)
                $overtimeTotal = 0;
                if ($employee->party_id) {
                    $overtimeTotal = \App\Domain\Accounting\Models\Document::where('company_id', $period->company_id)
                        ->where('party_id', $employee->party_id)
                        ->where('type', \App\Domain\Accounting\Enums\DocumentType::OVERTIME_DUE)
                        ->whereBetween('document_date', [
                            $targetDate->toDateString(),
                            $targetDate->copy()->endOfMonth()->toDateString()
                        ])
                        ->sum('total_amount');
                }
                
                // Fallback to legacy overtime records if no documents found
                if ($overtimeTotal == 0) {
                    $overtimeTotal = \App\Models\Overtime::where('employee_id', $employee->id)
                        ->whereBetween('overtime_date', [
                            $targetDate->toDateString(),
                            $targetDate->copy()->endOfMonth()->toDateString()
                        ])
                        ->sum('amount');
                }

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

                $installment1 = PayrollInstallment::create([
                    'payroll_item_id' => $item->id,
                    'installment_no' => 1,
                    'due_date' => Carbon::create($period->year, $period->month, $payDay1),
                    'planned_amount' => $contract->pay_amount_1,
                    'title' => 'Ayın 5\'i',
                ]);

                $installment2 = PayrollInstallment::create([
                    'payroll_item_id' => $item->id,
                    'installment_no' => 2,
                    'due_date' => Carbon::create($period->year, $period->month, $payDay2),
                    'planned_amount' => $contract->pay_amount_2,
                    'title' => 'Ayın 20\'si',
                ]);

                // Create accounting Documents for each installment
                try {
                    $payrollDocumentService = app(PayrollDocumentService::class);
                    $payrollDocumentService->createDocumentsForPayrollItem($item);
                } catch (\Exception $e) {
                    // Log error but don't fail the entire payroll generation
                    \Log::warning("Failed to create accounting documents for PayrollItem {$item->id}: " . $e->getMessage());
                }
            }

            DB::commit();
            return redirect()->route('admin.payroll.show', $period)
                ->with('success', 'Bordro başarıyla oluşturuldu.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Bordro oluşturulurken hata oluştu: ' . $e->getMessage()]);
        }
    }

    /**
     * Create accounting documents for a PayrollItem that doesn't have them
     */
    public function createDocuments(PayrollItem $item)
    {
        $user = Auth::user();
        if ($user->company_id && $item->payrollPeriod->company_id != $user->company_id) {
            abort(403);
        }
        
        // Check if installments exist
        if ($item->installments->count() !== 2) {
            return back()->withErrors(['error' => 'Bu bordro kalemi için 2 taksit bulunmalıdır.']);
        }
        
        // Check if documents already exist
        $hasDocuments = $item->installments->every(function ($installment) {
            return $installment->accounting_document_id !== null;
        });
        
        if ($hasDocuments) {
            return back()->with('info', 'Bu bordro kalemi için muhasebe belgeleri zaten oluşturulmuş.');
        }
        
        // Ensure employee has party_id
        if (!$item->employee->party_id) {
            return back()->withErrors(['error' => 'Personel için cari kaydı bulunamadı. Lütfen personeli düzenleyip kaydedin.']);
        }
        
        try {
            $payrollDocumentService = app(\App\Domain\Accounting\Services\PayrollDocumentService::class);
            $payrollDocumentService->createDocumentsForPayrollItem($item);
            
            return redirect()->route('admin.payroll.item', $item)
                ->with('success', 'Muhasebe belgeleri başarıyla oluşturuldu.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Muhasebe belgeleri oluşturulurken hata oluştu: ' . $e->getMessage()]);
        }
    }

    public function showItem(PayrollItem $item)
    {
        $user = Auth::user();
        if ($user->company_id && $item->payrollPeriod->company_id != $user->company_id) {
            abort(403);
        }
        
        // Load relationships
        $item->load([
            'employee.company', 
            'employee.branch', 
            'payrollPeriod.company', 
            'payrollPeriod.branch',
            'installments.document',
            'installments.deductions.deductionType',
            'deductions.deductionType',
            'deductions.installment',
        ]);
        
        // Get overtime documents from new accounting system for this period
        $overtimeDocuments = collect([]);
        if ($item->employee->party_id) {
            $periodStart = \Carbon\Carbon::create($item->payrollPeriod->year, $item->payrollPeriod->month, 1)->toDateString();
            $periodEnd = \Carbon\Carbon::create($item->payrollPeriod->year, $item->payrollPeriod->month, 1)->endOfMonth()->toDateString();
            
            $overtimeDocuments = \App\Domain\Accounting\Models\Document::where('company_id', $item->payrollPeriod->company_id)
                ->where('party_id', $item->employee->party_id)
                ->where('type', \App\Domain\Accounting\Enums\DocumentType::OVERTIME_DUE)
                ->whereBetween('document_date', [$periodStart, $periodEnd])
                ->orderBy('document_date', 'desc')
                ->get();
        }
        
        // Legacy overtime records (for backward compatibility - deprecated)
        $legacyOvertimes = \App\Models\Overtime::where('employee_id', $item->employee_id)
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

        // Get open debts for this employee
        $openDebts = \App\Models\EmployeeDebt::where('employee_id', $item->employee_id)
            ->where('status', 1)
            ->with('payments')
            ->get()
            ->filter(function ($debt) {
                return $debt->remaining_amount > 0;
            });
        
        // Get accounting payments per installment
        $installmentPayments = [];
        foreach ($item->installments as $installment) {
            if ($installment->accounting_document_id) {
                $installmentPayments[$installment->installment_no] = \App\Domain\Accounting\Models\Payment::whereHas('allocations', function ($q) use ($installment) {
                    $q->where('document_id', $installment->accounting_document_id)
                      ->where('status', 'active');
                })
                ->with(['allocations' => function ($q) use ($installment) {
                    $q->where('document_id', $installment->accounting_document_id)
                      ->where('status', 'active')
                      ->with('document');
                }])
                ->orderBy('payment_date', 'desc')
                ->get();
            } else {
                $installmentPayments[$installment->installment_no] = collect([]);
            }
        }
        
        // Get open advances for this employee (for advance deduction UI)
        if ($item->employee->party_id) {
            $advanceService = app(\App\Domain\Accounting\Services\EmployeeAdvanceService::class);
            $openAdvances = $advanceService->suggestOpenAdvancesForEmployee($item->employee->party_id);
        } else {
            $openAdvances = collect([]);
        }

        return view('admin.payroll.item', compact('item', 'deductionTypes', 'openAdvances', 'overtimeDocuments', 'legacyOvertimes', 'openDebts', 'installmentPayments'));
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
            // First try new accounting system (overtime_due documents)
            $overtimeTotal = 0;
            if ($employee->party_id) {
                $overtimeTotal = \App\Domain\Accounting\Models\Document::where('company_id', $period->company_id)
                    ->where('party_id', $employee->party_id)
                    ->where('type', \App\Domain\Accounting\Enums\DocumentType::OVERTIME_DUE)
                    ->whereBetween('document_date', [
                        $targetDate->toDateString(),
                        $targetDate->copy()->endOfMonth()->toDateString()
                    ])
                    ->sum('total_amount');
            }
            
            // Fallback to legacy overtime records if no documents found
            if ($overtimeTotal == 0) {
                $overtimeTotal = \App\Models\Overtime::where('employee_id', $employee->id)
                    ->whereBetween('overtime_date', [
                        $targetDate->toDateString(),
                        $targetDate->copy()->endOfMonth()->toDateString()
                    ])
                    ->sum('amount');
            }

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

            $installment1 = PayrollInstallment::create([
                'payroll_item_id' => $item->id,
                'installment_no' => 1,
                'due_date' => Carbon::create($period->year, $period->month, $payDay1),
                'planned_amount' => $contract->pay_amount_1,
                'title' => 'Ayın 5\'i',
            ]);

            $installment2 = PayrollInstallment::create([
                'payroll_item_id' => $item->id,
                'installment_no' => 2,
                'due_date' => Carbon::create($period->year, $period->month, $payDay2),
                'planned_amount' => $contract->pay_amount_2,
                'title' => 'Ayın 20\'si',
            ]);

            // Create accounting Documents for each installment
            try {
                $payrollDocumentService = app(PayrollDocumentService::class);
                $payrollDocumentService->createDocumentsForPayrollItem($item);
            } catch (\Exception $e) {
                // Log error but don't fail the entire operation
                \Log::warning("Failed to create accounting documents for PayrollItem {$item->id}: " . $e->getMessage());
            }

            DB::commit();
            return redirect()->route('admin.payroll.show', $period)
                ->with('success', 'Personel başarıyla bordroya eklendi.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Personel eklenirken hata oluştu: ' . $e->getMessage()]);
        }
    }

    /**
     * @deprecated Legacy PayrollPayment method removed
     * Payments must be created via accounting system
     * Redirects to accounting payment create with prefilled data
     */
    public function addPayment(Request $request, PayrollItem $item)
    {
        // Redirect to accounting payment create
        $installmentNo = $request->get('installment_no', 1);
        $installments = $item->installments()->orderBy('installment_no')->get();
        $installment = $installments->where('installment_no', $installmentNo)->first();
        
        if (!$installment || !$installment->accounting_document_id) {
            return back()->withErrors(['error' => 'Bu taksit için muhasebe belgesi bulunamadı.']);
        }
        
        $suggestedAmount = $installment->remaining_amount;
        
        return redirect()->route('accounting.payments.create', [
            'party_id' => $item->employee->party_id,
            'document_id' => $installment->accounting_document_id,
            'suggested_amount' => $suggestedAmount,
            'context' => 'payroll',
            'payroll_item_id' => $item->id,
            'installment_no' => $installmentNo,
        ]);
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

        $installment = null;
        if ($request->installment_id) {
            $installment = PayrollInstallment::findOrFail($request->installment_id);
            if ($installment->payroll_item_id != $item->id) {
                return back()->withErrors(['installment_id' => 'Geçersiz taksit.']);
            }
            if (!$installment->accounting_document_id) {
                return back()->withErrors(['installment_id' => 'Bu taksit için muhasebe belgesi bulunamadı.']);
            }
        }

        DB::beginTransaction();
        try {
            // Create offset payment (no cash movement)
            $paymentService = app(\App\Domain\Accounting\Services\PaymentService::class);
            $allocationService = app(\App\Domain\Accounting\Services\AllocationService::class);
            
            $deductionType = PayrollDeductionType::findOrFail($request->deduction_type_id);
            
            $payment = $paymentService->createPayment([
                'company_id' => $item->payrollPeriod->company_id,
                'branch_id' => $item->payrollPeriod->branch_id,
                'party_id' => $item->employee->party_id,
                'type' => \App\Domain\Accounting\Enums\PaymentType::INTERNAL_OFFSET,
                'direction' => 'internal',
                'amount' => $request->amount,
                'payment_date' => now()->toDateString(),
                'status' => 'confirmed',
                'description' => "Kesinti: {$deductionType->name}" . ($request->description ? " - {$request->description}" : ''),
            ]);
            
            // Allocate to installment document(s)
            $allocations = [];
            if ($installment) {
                // Allocate to specific installment
                $allocations[] = [
                    'document_id' => $installment->accounting_document_id,
                    'amount' => $request->amount,
                    'payroll_installment_id' => $installment->id,
                    'notes' => "Kesinti: {$deductionType->name}",
                ];
            } else {
                // Allocate to both installments proportionally
                $installments = $item->installments()->orderBy('installment_no')->get();
                $totalPlanned = $installments->sum('planned_amount');
                
                foreach ($installments as $inst) {
                    if ($inst->accounting_document_id && $totalPlanned > 0) {
                        $proportionalAmount = ($inst->planned_amount / $totalPlanned) * $request->amount;
                        $allocations[] = [
                            'document_id' => $inst->accounting_document_id,
                            'amount' => $proportionalAmount,
                            'payroll_installment_id' => $inst->id,
                            'notes' => "Kesinti: {$deductionType->name} (Orantılı)",
                        ];
                    }
                }
            }
            
            $createdAllocations = $allocationService->allocate($payment, $allocations);
            
            // Create PayrollDeduction record (for UI display and reporting)
            $deduction = PayrollDeduction::create([
                'payroll_item_id' => $item->id,
                'payroll_installment_id' => $request->installment_id,
                'deduction_type_id' => $request->deduction_type_id,
                'amount' => $request->amount,
                'description' => $request->description,
                'payment_allocation_id' => $createdAllocations[0]->id ?? null, // Link to first allocation for audit trail
                'created_by' => $user->id,
            ]);

            DB::commit();
            return redirect()->route('admin.payroll.item', $item)
                ->with('success', 'Kesinti başarıyla eklendi ve muhasebe sistemine kaydedildi.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Kesinti eklenirken hata oluştu: ' . $e->getMessage()]);
        }
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

    /**
     * @deprecated Legacy PayrollPayment deletion removed
     * Payments must be cancelled/reversed via accounting system
     */
    public function deletePayment(PayrollItem $item, PayrollPayment $payment)
    {
        return back()->withErrors([
            'error' => 'Ödeme silme özelliği kaldırılmıştır. Ödemeleri muhasebe sisteminden iptal edin veya ters kayıt yapın.'
        ]);
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

