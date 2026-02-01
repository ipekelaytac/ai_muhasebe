<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerTransaction extends Model
{
    use HasFactory;

    /**
     * Boot the model - prevent writes to deprecated accounting system
     */
    protected static function booted(): void
    {
        static::creating(function () {
            throw new \Exception(
                'CustomerTransaction is deprecated. Use App\Domain\Accounting\Models\Document ' .
                'and App\Domain\Accounting\Services\DocumentService instead.'
            );
        });

        static::updating(function () {
            throw new \Exception(
                'CustomerTransaction is deprecated. Use App\Domain\Accounting\Models\Document ' .
                'and App\Domain\Accounting\Services\DocumentService instead.'
            );
        });

        static::deleting(function () {
            throw new \Exception(
                'CustomerTransaction is deprecated. Use cancellation/reversal in ' .
                'App\Domain\Accounting\Services\DocumentService instead.'
            );
        });
    }

    protected $fillable = [
        'customer_id',
        'company_id',
        'branch_id',
        'type',
        'transaction_date',
        'description',
        'amount',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }
}

