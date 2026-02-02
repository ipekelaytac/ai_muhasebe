<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollInstallment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_item_id',
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

    public function paymentAllocations()
    {
        return $this->hasMany(PayrollPaymentAllocation::class);
    }

    public function payments()
    {
        return $this->belongsToMany(
            PayrollPayment::class,
            'payroll_payment_allocations',
            'payroll_installment_id',
            'payroll_payment_id'
        )->withPivot('allocated_amount');
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

    public function getPaidAmountAttribute()
    {
        return $this->paymentAllocations()->sum('allocated_amount');
    }

    public function getRemainingAmountAttribute()
    {
        $allocated = $this->paid_amount;
        $deductions = $this->deductions()->sum('amount');
        // Legacy advance settlements removed
        // $settlements = $this->advanceSettlements()->sum('settled_amount');
        $settlements = 0;
        
        return $this->planned_amount - $allocated - $deductions - $settlements;
    }
}

