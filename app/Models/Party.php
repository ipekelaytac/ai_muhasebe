<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @deprecated This model is deprecated. Use App\Domain\Accounting\Models\Party instead.
 * This class is kept for backward compatibility during migration only.
 * 
 * For new code, always use: App\Domain\Accounting\Models\Party
 */
class Party extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'type',
        'code',
        'name',
        'phone',
        'email',
        'address',
        'tax_number',
        'tax_office',
        'is_active',
        'notes',
        'partyable_type',
        'partyable_id',
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

    public function partyable()
    {
        return $this->morphTo();
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function cheques()
    {
        return $this->hasMany(Cheque::class);
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

    public function scopeCustomer($query)
    {
        return $query->where('type', 'customer');
    }

    public function scopeSupplier($query)
    {
        return $query->where('type', 'supplier');
    }

    public function scopeEmployee($query)
    {
        return $query->where('type', 'employee');
    }

    // Computed attributes
    public function getReceivableBalanceAttribute()
    {
        return $this->documents()
            ->where('direction', 'receivable')
            ->where('status', 'posted')
            ->sum('unpaid_amount');
    }

    public function getPayableBalanceAttribute()
    {
        return $this->documents()
            ->where('direction', 'payable')
            ->where('status', 'posted')
            ->sum('unpaid_amount');
    }
}
