<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollPayment extends Model
{
    use HasFactory;

    /**
     * Boot the model - prevent writes to deprecated accounting system
     */
    protected static function booted(): void
    {
        static::creating(function () {
            throw new \Exception(
                'PayrollPayment is deprecated. Use App\Domain\Accounting\Models\Payment ' .
                'and App\Domain\Accounting\Services\PaymentService instead.'
            );
        });

        static::updating(function () {
            throw new \Exception(
                'PayrollPayment is deprecated. Use App\Domain\Accounting\Models\Payment ' .
                'and App\Domain\Accounting\Services\PaymentService instead.'
            );
        });

        static::deleting(function () {
            throw new \Exception(
                'PayrollPayment is deprecated. Use cancellation/reversal in ' .
                'App\Domain\Accounting\Services\PaymentService instead.'
            );
        });
    }

    protected $fillable = [
        'payroll_item_id',
        'payment_date',
        'amount',
        'method',
        'reference_no',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function payrollItem()
    {
        return $this->belongsTo(PayrollItem::class);
    }

    public function allocations()
    {
        return $this->hasMany(PayrollPaymentAllocation::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

