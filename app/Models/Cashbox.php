<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @deprecated This model is deprecated. Use App\Domain\Accounting\Models\Cashbox instead.
 * This class is kept for backward compatibility during migration only.
 * 
 * For new code, always use: App\Domain\Accounting\Models\Cashbox
 */
class Cashbox extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'code',
        'name',
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

    // Schema has NO from_cashbox_id - source account is indicated by cashbox_id
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function paymentsTo()
    {
        return $this->hasMany(Payment::class, 'to_cashbox_id');
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
    // Schema: cashbox_id for source account, to_cashbox_id for destination (NO from_cashbox_id)
    public function getBalanceAttribute()
    {
        // Regular payments: IN minus OUT where this cashbox is the source/destination
        $inflows = $this->payments()
            ->where('direction', 'in') // Schema uses 'in', not 'inflow'
            ->where('status', 'confirmed') // Schema uses 'confirmed', not 'posted'
            ->sum('amount');

        $outflows = $this->payments()
            ->where('direction', 'out') // Schema uses 'out', not 'outflow'
            ->where('status', 'confirmed') // Schema uses 'confirmed', not 'posted'
            ->sum('amount');

        // Transfers: add transfers TO this cashbox (where to_cashbox_id = this)
        // Transfers OUT are already counted above (where cashbox_id = this and direction = 'out')
        $transfersIn = $this->paymentsTo()
            ->where('type', 'transfer') // Schema uses 'type', not 'payment_type'
            ->where('status', 'confirmed') // Schema uses 'confirmed', not 'posted'
            ->sum('amount');

        return ($inflows + $transfersIn) - $outflows;
    }
}
