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
        'base_net_salary',
        'meal_allowance',
        'bonus_total',
        'deduction_total',
        'advances_deducted_total',
        'net_payable',
        'note',
    ];

    protected $casts = [
        'base_net_salary' => 'decimal:2',
        'meal_allowance' => 'decimal:2',
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

    public function advanceSettlements()
    {
        return $this->hasMany(AdvanceSettlement::class);
    }

    public function getTotalPaidAttribute()
    {
        return $this->payments->sum('amount');
    }

    public function getTotalRemainingAttribute()
    {
        return $this->net_payable - $this->total_paid;
    }
}

