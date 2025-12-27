<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'address',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
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

    public function financeTransactions()
    {
        return $this->hasMany(FinanceTransaction::class);
    }

    public function advances()
    {
        return $this->hasMany(Advance::class);
    }
}

