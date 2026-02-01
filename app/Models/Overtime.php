<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Overtime extends Model
{
    use HasFactory;

    /**
     * Boot the model - prevent writes to deprecated accounting system
     */
    protected static function booted(): void
    {
        static::creating(function () {
            throw new \Exception(
                'Overtime is deprecated. Use App\Domain\Accounting\Models\Document ' .
                'with type "overtime_due" and App\Domain\Accounting\Services\DocumentService instead.'
            );
        });

        static::updating(function () {
            throw new \Exception(
                'Overtime is deprecated. Use App\Domain\Accounting\Models\Document ' .
                'and App\Domain\Accounting\Services\DocumentService instead.'
            );
        });

        static::deleting(function () {
            throw new \Exception(
                'Overtime is deprecated. Use cancellation/reversal in ' .
                'App\Domain\Accounting\Services\DocumentService instead.'
            );
        });
    }

    protected $fillable = [
        'company_id',
        'branch_id',
        'employee_id',
        'overtime_date',
        'start_time',
        'end_time',
        'hours',
        'rate',
        'amount',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'overtime_date' => 'date',
        'hours' => 'decimal:2',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2',
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

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('overtime_date', [$startDate, $endDate]);
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }
}

