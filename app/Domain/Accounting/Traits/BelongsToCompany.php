<?php

namespace App\Domain\Accounting\Traits;

use App\Models\Company;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait for models scoped to company/branch
 */
trait BelongsToCompany
{
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
    
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
    
    /**
     * Scope to filter by company
     */
    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where($this->getTable() . '.company_id', $companyId);
    }
    
    /**
     * Scope to filter by branch
     */
    public function scopeForBranch(Builder $query, ?int $branchId): Builder
    {
        if ($branchId === null) {
            return $query;
        }
        return $query->where($this->getTable() . '.branch_id', $branchId);
    }
    
    /**
     * Scope to filter by company and optionally branch
     */
    public function scopeScoped(Builder $query, int $companyId, ?int $branchId = null): Builder
    {
        $query->where($this->getTable() . '.company_id', $companyId);
        
        if ($branchId !== null) {
            $query->where($this->getTable() . '.branch_id', $branchId);
        }
        
        return $query;
    }
}
