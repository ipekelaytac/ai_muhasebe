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

    public function isGeneral()
    {
        return $this->payroll_installment_id === null;
    }
}

