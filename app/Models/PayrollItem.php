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

    public function getTotalPaidAttribute()
    {
        return $this->payments->sum('amount');
    }

    public function getDebtPaymentsTotalAttribute()
    {
        return $this->debtPayments->sum('amount');
    }

    public function getTotalRemainingAttribute()
    {
        return $this->net_payable - $this->total_paid;
    }
}

