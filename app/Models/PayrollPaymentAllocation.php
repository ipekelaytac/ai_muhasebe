<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollPaymentAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_payment_id',
        'payroll_installment_id',
        'allocated_amount',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
    ];

    public function payment()
    {
        return $this->belongsTo(PayrollPayment::class, 'payroll_payment_id');
    }

    public function installment()
    {
        return $this->belongsTo(PayrollInstallment::class);
    }
}

