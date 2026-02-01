<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'accounting_period_id',
        'payment_number',
        'payment_type',
        'direction',
        'status',
        'party_id',
        'cashbox_id',
        'bank_account_id',
        'from_cashbox_id',
        'to_cashbox_id',
        'from_bank_account_id',
        'to_bank_account_id',
        'payment_date',
        'amount',
        'allocated_amount',
        'unallocated_amount',
        'description',
        'metadata',
        'reverses_payment_id',
        'original_payment_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'allocated_amount' => 'decimal:2',
        'unallocated_amount' => 'decimal:2',
        'metadata' => 'array',
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

    public function accountingPeriod()
    {
        return $this->belongsTo(AccountingPeriod::class);
    }

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

    public function fromCashbox()
    {
        return $this->belongsTo(Cashbox::class, 'from_cashbox_id');
    }

    public function toCashbox()
    {
        return $this->belongsTo(Cashbox::class, 'to_cashbox_id');
    }

    public function fromBankAccount()
    {
        return $this->belongsTo(BankAccount::class, 'from_bank_account_id');
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
        return $query->where('status', 'posted');
    }

    public function scopeInflow($query)
    {
        return $query->where('direction', 'inflow');
    }

    public function scopeOutflow($query)
    {
        return $query->where('direction', 'outflow');
    }

    public function scopeByPaymentType($query, $type)
    {
        return $query->where('payment_type', $type);
    }

    public function scopeInPeriod($query, $periodId)
    {
        return $query->where('accounting_period_id', $periodId);
    }

    public function scopeUnallocated($query)
    {
        return $query->whereColumn('unallocated_amount', '>', 0);
    }

    // Helper methods
    public function isLocked()
    {
        return $this->accountingPeriod && $this->accountingPeriod->isLocked();
    }

    public function isReversed()
    {
        return $this->status === 'reversed';
    }

    public function isReversal()
    {
        return $this->original_payment_id !== null;
    }

    public function recalculateAllocatedAmount()
    {
        $allocatedAmount = $this->allocations()
            ->whereNull('deleted_at')
            ->sum('amount');

        $this->allocated_amount = $allocatedAmount;
        $this->unallocated_amount = $this->amount - $allocatedAmount;
        $this->save();
    }

    // Note: unallocated_amount is stored in DB but should be recalculated when needed
    // Use recalculateAllocatedAmount() method to ensure accuracy
}
