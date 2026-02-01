<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeDebt extends Model
{
    use HasFactory;

    /**
     * Boot the model - prevent writes to deprecated accounting system
     */
    protected static function booted(): void
    {
        static::creating(function () {
            throw new \Exception(
                'EmployeeDebt is deprecated. Use App\Domain\Accounting\Models\Document ' .
                'with type "expense_due" and App\Domain\Accounting\Services\DocumentService instead.'
            );
        });

        static::updating(function () {
            throw new \Exception(
                'EmployeeDebt is deprecated. Use App\Domain\Accounting\Models\Document ' .
                'and App\Domain\Accounting\Services\DocumentService instead.'
            );
        });

        static::deleting(function () {
            throw new \Exception(
                'EmployeeDebt is deprecated. Use cancellation/reversal in ' .
                'App\Domain\Accounting\Services\DocumentService instead.'
            );
        });
    }

    protected $fillable = [
        'company_id',
        'branch_id',
        'employee_id',
        'debt_date',
        'amount',
        'description',
        'status',
        'created_by',
    ];

    protected $casts = [
        'debt_date' => 'date',
        'amount' => 'decimal:2',
        'status' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments()
    {
        return $this->hasMany(EmployeeDebtPayment::class);
    }

    public function getPaidAmountAttribute()
    {
        return $this->payments->sum('amount');
    }

    public function getRemainingAmountAttribute()
    {
        return $this->amount - $this->paid_amount;
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 1);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 0);
    }
}

