<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollPayment extends Model
{
    use HasFactory;

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

