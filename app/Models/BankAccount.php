<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @deprecated This model is deprecated. Use App\Domain\Accounting\Models\BankAccount instead.
 * This class is kept for backward compatibility during migration only.
 * 
 * For new code, always use: App\Domain\Accounting\Models\BankAccount
 */
class BankAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'code',
        'name',
        'bank_name',
        'account_number',
        'iban',
        'currency',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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

    // Schema has NO from_bank_account_id - source account is indicated by bank_account_id
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function paymentsTo()
    {
        return $this->hasMany(Payment::class, 'to_bank_account_id');
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Computed balance (from payments, not stored)
    // Schema: bank_account_id for source account, to_bank_account_id for destination (NO from_bank_account_id)
    public function getBalanceAttribute()
    {
        // Regular payments: IN minus OUT where this bank account is the source/destination
        $inflows = $this->payments()
            ->where('direction', 'in') // Schema uses 'in', not 'inflow'
            ->where('status', 'confirmed') // Schema uses 'confirmed', not 'posted'
            ->sum('amount');

        $outflows = $this->payments()
            ->where('direction', 'out') // Schema uses 'out', not 'outflow'
            ->where('status', 'confirmed') // Schema uses 'confirmed', not 'posted'
            ->sum('amount');

        // Transfers: add transfers TO this bank account (where to_bank_account_id = this)
        // Transfers OUT are already counted above (where bank_account_id = this and direction = 'out')
        $transfersIn = $this->paymentsTo()
            ->where('type', 'transfer') // Schema uses 'type', not 'payment_type'
            ->where('status', 'confirmed') // Schema uses 'confirmed', not 'posted'
            ->sum('amount');

        return ($inflows + $transfersIn) - $outflows;
    }
}
