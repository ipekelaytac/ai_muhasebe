<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function paymentsFrom()
    {
        return $this->hasMany(Payment::class, 'from_cashbox_id');
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
    public function getBalanceAttribute()
    {
        $inflows = $this->payments()
            ->where('direction', 'inflow')
            ->where('status', 'posted')
            ->sum('amount');

        $outflows = $this->payments()
            ->where('direction', 'outflow')
            ->where('status', 'posted')
            ->sum('amount');

        // Transfers: add from transfers, subtract to transfers
        $transfersIn = $this->paymentsTo()
            ->where('payment_type', 'transfer')
            ->where('status', 'posted')
            ->sum('amount');

        $transfersOut = $this->paymentsFrom()
            ->where('payment_type', 'transfer')
            ->where('status', 'posted')
            ->sum('amount');

        return ($inflows + $transfersIn) - ($outflows + $transfersOut);
    }
}
