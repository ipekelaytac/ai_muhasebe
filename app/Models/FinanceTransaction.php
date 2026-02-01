<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceTransaction extends Model
{
    use HasFactory;

    /**
     * Boot the model - prevent writes to deprecated accounting system
     */
    protected static function booted(): void
    {
        static::creating(function () {
            throw new \Exception(
                'FinanceTransaction is deprecated. Use App\Domain\Accounting\Models\Document ' .
                'and App\Domain\Accounting\Services\DocumentService instead.'
            );
        });

        static::updating(function () {
            throw new \Exception(
                'FinanceTransaction is deprecated. Use App\Domain\Accounting\Models\Document ' .
                'and App\Domain\Accounting\Services\DocumentService instead.'
            );
        });

        static::deleting(function () {
            throw new \Exception(
                'FinanceTransaction is deprecated. Use cancellation/reversal in ' .
                'App\Domain\Accounting\Services\DocumentService instead.'
            );
        });
    }

    protected $fillable = [
        'company_id',
        'branch_id',
        'type',
        'category_id',
        'transaction_date',
        'description',
        'amount',
        'employee_id',
        'related_table',
        'related_id',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
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

    public function category()
    {
        return $this->belongsTo(FinanceCategory::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attachments()
    {
        return $this->hasMany(TransactionAttachment::class, 'transaction_id');
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

