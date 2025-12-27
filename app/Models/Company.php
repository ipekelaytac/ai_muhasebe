<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function payrollPeriods()
    {
        return $this->hasMany(PayrollPeriod::class);
    }

    public function deductionTypes()
    {
        return $this->hasMany(PayrollDeductionType::class);
    }

    public function financeCategories()
    {
        return $this->hasMany(FinanceCategory::class);
    }

    public function financeTransactions()
    {
        return $this->hasMany(FinanceTransaction::class);
    }

    public function advances()
    {
        return $this->hasMany(Advance::class);
    }
}

