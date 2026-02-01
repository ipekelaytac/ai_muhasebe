<?php

namespace App\Domain\Accounting\Models;

use App\Domain\Accounting\Enums\PartyType;
use App\Domain\Accounting\Traits\BelongsToCompany;
use App\Domain\Accounting\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Party extends Model
{
    use BelongsToCompany, HasAuditFields, HasFactory, SoftDeletes;
    
    protected $table = 'parties';
    
    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\PartyFactory::new();
    }
    
    protected $fillable = [
        'company_id',
        'branch_id',
        'type',
        'linkable_type',
        'linkable_id',
        'code',
        'name',
        'tax_number',
        'tax_office',
        'phone',
        'email',
        'address',
        'city',
        'country',
        'payment_terms_days',
        'credit_limit',
        'is_active',
        'notes',
    ];
    
    protected $casts = [
        'payment_terms_days' => 'integer',
        'credit_limit' => 'decimal:2',
        'is_active' => 'boolean',
    ];
    
    // Relationships
    
    /**
     * Link to original entity (Customer, Employee, etc.)
     */
    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }
    
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
    
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
    
    public function cheques(): HasMany
    {
        return $this->hasMany(Cheque::class);
    }
    
    // Scopes
    
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
    
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }
    
    public function scopeCustomers(Builder $query): Builder
    {
        return $query->where('type', PartyType::CUSTOMER);
    }
    
    public function scopeSuppliers(Builder $query): Builder
    {
        return $query->where('type', PartyType::SUPPLIER);
    }
    
    public function scopeEmployees(Builder $query): Builder
    {
        return $query->where('type', PartyType::EMPLOYEE);
    }
    
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('code', 'like', "%{$term}%")
              ->orWhere('tax_number', 'like', "%{$term}%");
        });
    }
    
    // Computed Attributes
    
    /**
     * Get total receivable balance (what they owe us)
     * Computed from documents - allocations
     */
    public function getReceivableBalanceAttribute(): float
    {
        return $this->documents()
            ->where('direction', 'receivable')
            ->whereIn('status', ['pending', 'partial'])
            ->get()
            ->sum(fn($doc) => $doc->unpaid_amount);
    }
    
    /**
     * Get total payable balance (what we owe them)
     * Computed from documents - allocations
     */
    public function getPayableBalanceAttribute(): float
    {
        return $this->documents()
            ->where('direction', 'payable')
            ->whereIn('status', ['pending', 'partial'])
            ->get()
            ->sum(fn($doc) => $doc->unpaid_amount);
    }
    
    /**
     * Get net balance (positive = they owe us, negative = we owe them)
     */
    public function getBalanceAttribute(): float
    {
        return $this->receivable_balance - $this->payable_balance;
    }
    
    /**
     * Get type label in Turkish
     */
    public function getTypeLabelAttribute(): string
    {
        return PartyType::getLabel($this->type);
    }
    
    // Helper Methods
    
    /**
     * Generate next code for party type
     */
    public static function generateCode(int $companyId, string $type): string
    {
        $prefix = match ($type) {
            PartyType::CUSTOMER => 'MUS',
            PartyType::SUPPLIER => 'TED',
            PartyType::EMPLOYEE => 'CAL',
            default => 'DIG',
        };
        
        $lastCode = static::where('company_id', $companyId)
            ->where('type', $type)
            ->where('code', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(code, 4) AS UNSIGNED) DESC')
            ->value('code');
        
        if ($lastCode) {
            $number = (int) substr($lastCode, 3) + 1;
        } else {
            $number = 1;
        }
        
        return $prefix . str_pad($number, 5, '0', STR_PAD_LEFT);
    }
}
