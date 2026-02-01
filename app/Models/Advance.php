<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advance extends Model
{
    use HasFactory;

    /**
     * Boot the model - prevent writes to deprecated accounting system
     */
    protected static function booted(): void
    {
        static::creating(function () {
            throw new \Exception(
                'Advance is deprecated. Use App\Domain\Accounting\Models\Document ' .
                'with type "advance_given" and App\Domain\Accounting\Services\DocumentService instead.'
            );
        });

        static::updating(function () {
            throw new \Exception(
                'Advance is deprecated. Use App\Domain\Accounting\Models\Document ' .
                'and App\Domain\Accounting\Services\DocumentService instead.'
            );
        });

        static::deleting(function () {
            throw new \Exception(
                'Advance is deprecated. Use cancellation/reversal in ' .
                'App\Domain\Accounting\Services\DocumentService instead.'
            );
        });
    }

    protected $fillable = [
        'company_id',
        'branch_id',
        'employee_id',
        'advance_date',
        'amount',
        'method',
        'note',
        'status',
    ];

    protected $casts = [
        'advance_date' => 'date',
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

    public function settlements()
    {
        return $this->hasMany(AdvanceSettlement::class);
    }

    public function getRemainingAmountAttribute()
    {
        $settled = $this->settlements()->sum('settled_amount');
        return $this->amount - $settled;
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 1);
    }
}

