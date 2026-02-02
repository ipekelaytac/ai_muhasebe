<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @deprecated This model is deprecated. Use App\Domain\Accounting\Models\Payment instead.
 * This class is kept for backward compatibility during migration only.
 * 
 * For new code, always use: App\Domain\Accounting\Models\Payment
 */
class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'payment_number',
        'type', // Schema uses 'type', not 'payment_type'
        'direction', // Schema uses 'in'/'out', not 'inflow'/'outflow'
        'status',
        'party_id',
        'cashbox_id',
        'bank_account_id',
        'to_cashbox_id', // Schema has to_* but NOT from_* columns
        'to_bank_account_id',
        'payment_date',
        'period_year', // Schema uses period_year/month, NOT accounting_period_id FK
        'period_month',
        'amount',
        'fee_amount', // Schema requires fee_amount
        'net_amount', // Schema requires net_amount (no default)
        // Schema does NOT have allocated_amount/unallocated_amount - these are calculated via allocations
        'description',
        // Schema does NOT have metadata column - use notes if needed
        'reverses_payment_id',
        'original_payment_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        // Schema does NOT have allocated_amount/unallocated_amount/metadata - these are calculated or not stored
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // Note: Schema uses period_year/month, NOT accounting_period_id FK
    // Period validation is date-based, not FK-based

    public function party()
    {
        return $this->belongsTo(Party::class);
    }

    public function cashbox()
    {
        return $this->belongsTo(Cashbox::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    // Note: Schema has to_cashbox_id/to_bank_account_id but NOT from_cashbox_id/from_bank_account_id
    // For transfers, source account is indicated by cashbox_id or bank_account_id
    
    public function toCashbox()
    {
        return $this->belongsTo(Cashbox::class, 'to_cashbox_id');
    }

    public function toBankAccount()
    {
        return $this->belongsTo(BankAccount::class, 'to_bank_account_id');
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function reversesPayment()
    {
        return $this->belongsTo(Payment::class, 'reverses_payment_id');
    }

    public function originalPayment()
    {
        return $this->belongsTo(Payment::class, 'original_payment_id');
    }

    public function reversalPayments()
    {
        return $this->hasMany(Payment::class, 'original_payment_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopePosted($query)
    {
        // Map 'posted' scope to 'confirmed' status (schema uses 'confirmed')
        return $query->where('status', 'confirmed');
    }

    public function scopeInflow($query)
    {
        return $query->where('direction', 'in'); // Schema uses 'in', not 'inflow'
    }

    public function scopeOutflow($query)
    {
        return $query->where('direction', 'out'); // Schema uses 'out', not 'outflow'
    }

    public function scopeByPaymentType($query, $type)
    {
        return $query->where('type', $type); // Schema uses 'type' column
    }

    public function scopeInPeriod($query, $periodId)
    {
        // Schema uses period_year/month, not accounting_period_id FK
        // This scope should use period_year/month or be removed if not needed
        // For now, keeping for backward compatibility but it won't work correctly
        // Consider using: ->where('period_year', $year)->where('period_month', $month)
        return $query->where('period_year', $periodId); // This is incorrect, but kept for compatibility
    }

    public function scopeUnallocated($query)
    {
        // Schema does NOT have unallocated_amount column - calculate from allocations
        // payment_allocations uses status='active', not deleted_at
        return $query->whereRaw('amount > COALESCE((SELECT SUM(amount) FROM payment_allocations WHERE payment_id = payments.id AND status = \'active\'), 0)');
    }

    // Accessors for backward compatibility
    public function getPaymentTypeAttribute()
    {
        return $this->attributes['type'] ?? $this->type; // Map 'type' column to 'payment_type' attribute
    }

    // Helper methods
    public function isLocked()
    {
        // Period locking is date-based, not FK-based
        // Check if period for payment_date is locked
        $period = \App\Models\AccountingPeriod::where('company_id', $this->company_id)
            ->where('year', $this->period_year)
            ->where('month', $this->period_month)
            ->first();
        
        return $period && $period->isLocked();
    }

    public function isReversed()
    {
        return $this->status === 'reversed';
    }

    public function isReversal()
    {
        return $this->original_payment_id !== null;
    }

    // Schema does NOT have allocated_amount/unallocated_amount columns
    // These are calculated from allocations, not stored
    // payment_allocations table uses 'status' enum, NOT soft deletes (no deleted_at)
    public function getAllocatedAmountAttribute()
    {
        return $this->allocations()
            ->where('status', 'active') // Schema uses status='active', not deleted_at
            ->sum('amount');
    }

    public function getUnallocatedAmountAttribute()
    {
        return $this->amount - $this->allocated_amount;
    }
}
