<?php

namespace App\Domain\Accounting\Models;

use App\Domain\Accounting\Enums\PaymentType;
use App\Domain\Accounting\Traits\BelongsToCompany;
use App\Domain\Accounting\Traits\HasAuditFields;
use App\Domain\Accounting\Traits\HasPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use BelongsToCompany, HasAuditFields, HasPeriod, SoftDeletes;
    
    protected $table = 'payments';
    
    protected $fillable = [
        'company_id',
        'branch_id',
        'payment_number',
        'reference_number',
        'type',
        'direction',
        'party_id',
        'reference_type',
        'reference_id',
        'cashbox_id',
        'bank_account_id',
        'to_cashbox_id',
        'to_bank_account_id',
        'payment_date',
        'amount',
        'currency',
        'exchange_rate',
        'fee_amount',
        'net_amount',
        'status',
        'reversed_payment_id',
        'reversal_payment_id',
        'cheque_id',
        'period_year',
        'period_month',
        'description',
        'notes',
    ];
    
    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'fee_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'period_year' => 'integer',
        'period_month' => 'integer',
    ];
    
    /**
     * Boot the model - enforce period locking and calculate net amount
     */
    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            // Calculate net amount if not set
            if (empty($payment->net_amount)) {
                $payment->net_amount = $payment->amount - ($payment->fee_amount ?? 0);
            }
        });
        
        static::updating(function (Payment $payment) {
            // Prevent updates if period is locked (unless it's a status change to cancelled/reversed)
            if ($payment->isInLockedPeriod() && !in_array($payment->status, ['cancelled', 'reversed'])) {
                throw new \Exception(
                    "Cannot update payment in locked period: {$payment->payment_number}. " .
                    "Use reversal in an open period instead."
                );
            }
        });
        
        static::deleting(function (Payment $payment) {
            // Prevent hard deletes - use soft delete or cancellation
            if ($payment->isInLockedPeriod()) {
                throw new \Exception(
                    "Cannot delete payment in locked period: {$payment->payment_number}. " .
                    "Use cancellation/reversal instead."
                );
            }
        });
    }
    
    // Relationships
    
    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }
    
    public function cashbox(): BelongsTo
    {
        return $this->belongsTo(Cashbox::class);
    }
    
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }
    
    public function toCashbox(): BelongsTo
    {
        return $this->belongsTo(Cashbox::class, 'to_cashbox_id');
    }
    
    public function toBankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'to_bank_account_id');
    }
    
    public function cheque(): BelongsTo
    {
        return $this->belongsTo(Cheque::class);
    }
    
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }
    
    public function activeAllocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class)->where('status', 'active');
    }
    
    public function reversedPayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'reversed_payment_id');
    }
    
    public function reversalPayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'reversal_payment_id');
    }
    
    public function attachments(): MorphMany
    {
        return $this->morphMany(DocumentAttachment::class, 'attachable');
    }
    
    // Scopes
    
    public function scopeIncoming(Builder $query): Builder
    {
        return $query->where('direction', 'in');
    }
    
    public function scopeOutgoing(Builder $query): Builder
    {
        return $query->where('direction', 'out');
    }
    
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }
    
    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', 'confirmed');
    }
    
    public function scopeForCashbox(Builder $query, int $cashboxId): Builder
    {
        return $query->where('cashbox_id', $cashboxId);
    }
    
    public function scopeForBankAccount(Builder $query, int $bankAccountId): Builder
    {
        return $query->where('bank_account_id', $bankAccountId);
    }
    
    public function scopeForParty(Builder $query, int $partyId): Builder
    {
        return $query->where('party_id', $partyId);
    }
    
    public function scopeCash(Builder $query): Builder
    {
        return $query->whereIn('type', PaymentType::CASH_TYPES);
    }
    
    public function scopeBank(Builder $query): Builder
    {
        return $query->whereIn('type', PaymentType::BANK_TYPES);
    }
    
    // Computed Attributes
    
    /**
     * Get allocated amount
     */
    public function getAllocatedAmountAttribute(): float
    {
        return (float) $this->activeAllocations()->sum('amount');
    }
    
    /**
     * Get unallocated amount (available for allocation)
     */
    public function getUnallocatedAmountAttribute(): float
    {
        return max(0, $this->amount - $this->allocated_amount);
    }
    
    /**
     * Check if fully allocated
     */
    public function getIsFullyAllocatedAttribute(): bool
    {
        return $this->unallocated_amount <= 0.001;
    }
    
    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return PaymentType::getLabel($this->type);
    }
    
    /**
     * Get account name (cashbox or bank)
     */
    public function getAccountNameAttribute(): string
    {
        if ($this->cashbox_id) {
            return $this->cashbox->name ?? '';
        }
        if ($this->bank_account_id) {
            return $this->bankAccount->name ?? '';
        }
        return '';
    }
    
    // Methods
    
    /**
     * Check if payment can be modified
     */
    public function canModify(): bool
    {
        if ($this->isInLockedPeriod()) {
            return false;
        }
        
        if ($this->status === 'cancelled' || $this->status === 'reversed') {
            return false;
        }
        
        // Can't modify if has active allocations
        if ($this->activeAllocations()->exists()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Generate next payment number
     */
    public static function generateNumber(int $companyId, ?int $branchId, string $type): string
    {
        $year = now()->year;
        $prefix = match ($type) {
            PaymentType::CASH_IN => 'KG',
            PaymentType::CASH_OUT => 'KC',
            PaymentType::BANK_IN => 'BG',
            PaymentType::BANK_OUT => 'BC',
            PaymentType::BANK_TRANSFER => 'HV',
            PaymentType::POS_IN => 'PS',
            PaymentType::CHEQUE_IN => 'CT',
            PaymentType::CHEQUE_OUT => 'CO',
            PaymentType::TRANSFER => 'VR',
            default => 'OD',
        };
        
        $sequence = NumberSequence::getNext($companyId, $branchId, 'payment', $type, $year);
        
        return $prefix . $year . '-' . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }
}
