<?php

namespace App\Domain\Accounting\Models;

use App\Domain\Accounting\Traits\BelongsToCompany;
use App\Domain\Accounting\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseCategory extends Model
{
    use BelongsToCompany, HasAuditFields, SoftDeletes;
    
    protected $table = 'expense_categories';
    
    protected $fillable = [
        'company_id',
        'parent_id',
        'code',
        'name',
        'type',
        'group',
        'description',
        'is_active',
        'is_system',
        'sort_order',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'sort_order' => 'integer',
    ];
    
    // Relationships
    
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'parent_id');
    }
    
    public function children(): HasMany
    {
        return $this->hasMany(ExpenseCategory::class, 'parent_id');
    }
    
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'category_id');
    }
    
    public function documentLines(): HasMany
    {
        return $this->hasMany(DocumentLine::class, 'category_id');
    }
    
    // Scopes
    
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
    
    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }
    
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where(function ($q) use ($type) {
            $q->where('type', $type)->orWhere('type', 'both');
        });
    }
    
    public function scopeIncome(Builder $query): Builder
    {
        return $query->whereIn('type', ['income', 'both']);
    }
    
    public function scopeExpense(Builder $query): Builder
    {
        return $query->whereIn('type', ['expense', 'both']);
    }
    
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
    
    // Computed Attributes
    
    /**
     * Get full path (parent > child)
     */
    public function getFullPathAttribute(): string
    {
        if ($this->parent) {
            return $this->parent->full_path . ' > ' . $this->name;
        }
        return $this->name;
    }
    
    /**
     * Get all descendant IDs including self
     */
    public function getAllDescendantIds(): array
    {
        $ids = [$this->id];
        
        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->getAllDescendantIds());
        }
        
        return $ids;
    }
    
    // Static Methods
    
    /**
     * Create default categories for a company
     */
    public static function createDefaultsForCompany(int $companyId): void
    {
        $defaults = [
            // Income categories
            ['code' => 'GEL-001', 'name' => 'Satış Gelirleri', 'type' => 'income', 'group' => 'revenue'],
            ['code' => 'GEL-002', 'name' => 'Hizmet Gelirleri', 'type' => 'income', 'group' => 'revenue'],
            ['code' => 'GEL-003', 'name' => 'Diğer Gelirler', 'type' => 'income', 'group' => 'other_income'],
            
            // Expense categories
            ['code' => 'GID-001', 'name' => 'Hammadde ve Malzeme', 'type' => 'expense', 'group' => 'cost_of_goods'],
            ['code' => 'GID-002', 'name' => 'Personel Giderleri', 'type' => 'expense', 'group' => 'operating_expense'],
            ['code' => 'GID-003', 'name' => 'Kira Giderleri', 'type' => 'expense', 'group' => 'operating_expense'],
            ['code' => 'GID-004', 'name' => 'Elektrik/Su/Doğalgaz', 'type' => 'expense', 'group' => 'operating_expense'],
            ['code' => 'GID-005', 'name' => 'İletişim Giderleri', 'type' => 'expense', 'group' => 'operating_expense'],
            ['code' => 'GID-006', 'name' => 'Ulaşım/Akaryakıt', 'type' => 'expense', 'group' => 'operating_expense'],
            ['code' => 'GID-007', 'name' => 'Bakım/Onarım', 'type' => 'expense', 'group' => 'operating_expense'],
            ['code' => 'GID-008', 'name' => 'Vergi ve Harçlar', 'type' => 'expense', 'group' => 'tax_expense'],
            ['code' => 'GID-009', 'name' => 'Banka Masrafları', 'type' => 'expense', 'group' => 'financial_expense'],
            ['code' => 'GID-010', 'name' => 'Diğer Giderler', 'type' => 'expense', 'group' => 'other_expense'],
        ];
        
        foreach ($defaults as $i => $category) {
            static::create([
                'company_id' => $companyId,
                'code' => $category['code'],
                'name' => $category['name'],
                'type' => $category['type'],
                'group' => $category['group'],
                'is_system' => true,
                'sort_order' => $i + 1,
            ]);
        }
    }
}
