<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'document_id',
        'base_net_salary',
        'meal_allowance',
        'overtime_total',
        'bonus_total',
        'deduction_total',
        'advances_deducted_total',
        'net_payable',
        'note',
    ];

    protected $casts = [
        'base_net_salary' => 'decimal:2',
        'meal_allowance' => 'decimal:2',
        'overtime_total' => 'decimal:2',
        'bonus_total' => 'decimal:2',
        'deduction_total' => 'decimal:2',
        'advances_deducted_total' => 'decimal:2',
        'net_payable' => 'decimal:2',
    ];

    public function payrollPeriod()
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function installments()
    {
        return $this->hasMany(PayrollInstallment::class);
    }

    public function payments()
    {
        return $this->hasMany(PayrollPayment::class);
    }

    public function deductions()
    {
        return $this->hasMany(PayrollDeduction::class);
    }

    /**
     * @deprecated Legacy advance settlements removed - table dropped
     * TODO: Migrate to use payment allocations to advance documents
     */
    public function advanceSettlements()
    {
        // Legacy relationship removed - AdvanceSettlement model deleted
        // Return empty relationship to prevent errors
        return $this->hasMany(\App\Models\PayrollDeduction::class)->whereRaw('1 = 0');
    }

    public function debtPayments()
    {
        return $this->hasMany(EmployeeDebtPayment::class);
    }

    /**
     * Get the accounting Document for this PayrollItem
     */
    public function document()
    {
        return $this->belongsTo(\App\Domain\Accounting\Models\Document::class);
    }

    /**
     * Get accounting Payments allocated to this PayrollItem's document
     */
    public function accountingPayments()
    {
        if (!$this->document_id) {
            return \App\Domain\Accounting\Models\Payment::whereRaw('1 = 0'); // Empty query
        }
        
        return \App\Domain\Accounting\Models\Payment::whereHas('allocations', function ($q) {
            $q->where('document_id', $this->document_id)
              ->where('status', 'active');
        });
    }

    /**
     * Get total paid amount from accounting Payments across all installments
     */
    public function getTotalPaidAttribute()
    {
        // Sum payments from all installment documents
        $documentIds = $this->installments()
            ->whereNotNull('accounting_document_id')
            ->pluck('accounting_document_id');
        
        if ($documentIds->isEmpty()) {
            return 0;
        }
        
        return \App\Domain\Accounting\Models\PaymentAllocation::whereIn('document_id', $documentIds)
            ->where('status', 'active')
            ->sum('amount');
    }

    /**
     * Get total advances deducted from accounting allocations
     */
    public function getAdvancesDeductedTotalAttribute()
    {
        // Sum internal_offset payments allocated to installment documents
        $documentIds = $this->installments()
            ->whereNotNull('accounting_document_id')
            ->pluck('accounting_document_id');
        
        if ($documentIds->isEmpty()) {
            return $this->attributes['advances_deducted_total'] ?? 0;
        }
        
        // Get internal_offset payments that are allocated to installment documents
        $advanceDeductionAmount = \App\Domain\Accounting\Models\PaymentAllocation::whereIn('document_id', $documentIds)
            ->where('status', 'active')
            ->whereHas('payment', function ($q) {
                $q->where('type', \App\Domain\Accounting\Enums\PaymentType::INTERNAL_OFFSET);
            })
            ->sum('amount');
        
        // Return calculated value if available, otherwise fallback to stored value
        return $advanceDeductionAmount > 0 ? $advanceDeductionAmount : ($this->attributes['advances_deducted_total'] ?? 0);
    }

    /**
     * Get total deductions from PayrollDeduction records
     */
    public function getDeductionTotalAttribute()
    {
        // Sum deductions from PayrollDeduction records
        $total = $this->deductions()->sum('amount');
        
        // Return calculated value if available, otherwise fallback to stored value
        return $total > 0 ? $total : ($this->attributes['deduction_total'] ?? 0);
    }

    /**
     * @deprecated Use accountingPayments() instead
     */
    public function getLegacyPaymentsAttribute()
    {
        return $this->payments;
    }

    public function getDebtPaymentsTotalAttribute()
    {
        return $this->debtPayments->sum('amount');
    }

    public function getTotalRemainingAttribute()
    {
        // Calculate remaining from all installments
        $totalPlanned = $this->installments->sum('planned_amount');
        $totalPaid = $this->total_paid;
        return $totalPlanned - $totalPaid;
    }
}

