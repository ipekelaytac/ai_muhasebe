<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollDeduction extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_item_id',
        'payroll_installment_id',
        'deduction_type_id',
        'payment_allocation_id',
        'amount',
        'description',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function payrollItem()
    {
        return $this->belongsTo(PayrollItem::class);
    }

    public function installment()
    {
        return $this->belongsTo(PayrollInstallment::class, 'payroll_installment_id');
    }

    public function deductionType()
    {
        return $this->belongsTo(PayrollDeductionType::class, 'deduction_type_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the accounting payment allocation linked to this deduction
     */
    public function paymentAllocation()
    {
        return $this->belongsTo(\App\Domain\Accounting\Models\PaymentAllocation::class, 'payment_allocation_id');
    }

    public function isGeneral()
    {
        return $this->payroll_installment_id === null;
    }
}

