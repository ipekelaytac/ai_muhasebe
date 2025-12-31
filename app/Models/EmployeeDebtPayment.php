<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeDebtPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_debt_id',
        'payroll_item_id',
        'amount',
        'payment_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function employeeDebt()
    {
        return $this->belongsTo(EmployeeDebt::class);
    }

    public function payrollItem()
    {
        return $this->belongsTo(PayrollItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

