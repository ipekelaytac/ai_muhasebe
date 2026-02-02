<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollInstallment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_item_id',
        'accounting_document_id',
        'installment_no',
        'due_date',
        'planned_amount',
        'title',
    ];

    protected $casts = [
        'due_date' => 'date',
        'planned_amount' => 'decimal:2',
        'installment_no' => 'integer',
    ];

    public function payrollItem()
    {
        return $this->belongsTo(PayrollItem::class);
    }

    /**
     * Get the accounting Document for this installment
     */
    public function document()
    {
        return $this->belongsTo(\App\Domain\Accounting\Models\Document::class, 'accounting_document_id');
    }

    /**
     * @deprecated Legacy PayrollPaymentAllocation - use accounting allocations instead
     */
    public function paymentAllocations()
    {
        return $this->hasMany(\App\Models\PayrollPaymentAllocation::class);
    }

    /**
     * @deprecated Legacy PayrollPayment - use accountingPayments() instead
     */
    public function payments()
    {
        return $this->belongsToMany(
            \App\Models\PayrollPayment::class,
            'payroll_payment_allocations',
            'payroll_installment_id',
            'payroll_payment_id'
        )->withPivot('allocated_amount');
    }

    /**
     * Get accounting Payments allocated to this installment's document
     */
    public function accountingPayments()
    {
        if (!$this->accounting_document_id) {
            return \App\Domain\Accounting\Models\Payment::whereRaw('1 = 0'); // Empty query
        }
        
        return \App\Domain\Accounting\Models\Payment::whereHas('allocations', function ($q) {
            $q->where('document_id', $this->accounting_document_id)
              ->where('status', 'active');
        });
    }

    /**
     * Get accounting allocations for this installment's document
     */
    public function accountingAllocations()
    {
        if (!$this->accounting_document_id) {
            return \App\Domain\Accounting\Models\PaymentAllocation::whereRaw('1 = 0');
        }
        
        return \App\Domain\Accounting\Models\PaymentAllocation::where('document_id', $this->accounting_document_id)
            ->where('status', 'active');
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

    /**
     * Get paid amount from accounting allocations
     */
    public function getPaidAmountAttribute()
    {
        if (!$this->accounting_document_id) {
            return 0;
        }
        
        return (float) \App\Domain\Accounting\Models\PaymentAllocation::where('document_id', $this->accounting_document_id)
            ->where('status', 'active')
            ->sum('amount');
    }

    /**
     * Get remaining unpaid amount
     * Computed from: planned_amount - paid_amount (from accounting) - deductions (offset allocations)
     */
    public function getRemainingAmountAttribute()
    {
        $paid = $this->paid_amount; // From accounting allocations
        
        // Deductions reduce remaining (they create offset allocations)
        // But we don't subtract them here because they're already accounted for in the document's unpaid_amount
        // The document's unpaid_amount already reflects deductions via offset allocations
        
        // Get document's unpaid amount if document exists
        if ($this->accounting_document_id && $this->document) {
            return (float) $this->document->unpaid_amount;
        }
        
        // Fallback calculation if document doesn't exist yet
        return max(0, $this->planned_amount - $paid);
    }
}

