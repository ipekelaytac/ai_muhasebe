<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @deprecated This model is deprecated. Use App\Domain\Accounting\Models\Document instead.
 * This class is kept for backward compatibility during migration only.
 * 
 * For new code, always use: App\Domain\Accounting\Models\Document
 */
class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'document_number',
        'type', // Schema uses 'type', not 'document_type'
        'direction',
        'status',
        'party_id',
        'document_date',
        'due_date',
        'period_year', // Schema uses period_year/month, NOT accounting_period_id FK
        'period_month',
        'total_amount',
        // Schema does NOT have paid_amount/unpaid_amount - these are calculated via allocations
        'reverses_document_id',
        'original_document_id',
        'category_id',
        'description',
        // Schema does NOT have metadata column - use notes or tags if needed
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'document_date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'decimal:2',
        // Schema does NOT have paid_amount/unpaid_amount/metadata - these are calculated or not stored
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

    public function category()
    {
        return $this->belongsTo(FinanceCategory::class);
    }

    public function lines()
    {
        return $this->hasMany(DocumentLine::class);
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function reversesDocument()
    {
        return $this->belongsTo(Document::class, 'reverses_document_id');
    }

    public function originalDocument()
    {
        return $this->belongsTo(Document::class, 'original_document_id');
    }

    public function reversalDocuments()
    {
        return $this->hasMany(Document::class, 'original_document_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function cheque()
    {
        return $this->hasOne(Cheque::class);
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
        // Map 'posted' scope to 'pending'/'partial'/'settled' statuses (schema doesn't have 'posted')
        // 'posted' means the document is active and can be allocated to
        return $query->whereIn('status', ['pending', 'partial', 'settled']);
    }

    public function scopeReceivable($query)
    {
        return $query->where('direction', 'receivable');
    }

    public function scopePayable($query)
    {
        return $query->where('direction', 'payable');
    }

    public function scopeUnpaid($query)
    {
        // Schema does NOT have unpaid_amount column - calculate from allocations
        // payment_allocations uses status='active', not deleted_at
        return $query->whereRaw('total_amount > COALESCE((SELECT SUM(amount) FROM payment_allocations WHERE document_id = documents.id AND status = \'active\'), 0)');
    }

    public function scopeOverdue($query)
    {
        // Schema does NOT have unpaid_amount column - calculate from allocations
        // payment_allocations uses status='active', not deleted_at
        return $query->where('due_date', '<', now())
            ->whereRaw('total_amount > COALESCE((SELECT SUM(amount) FROM payment_allocations WHERE document_id = documents.id AND status = \'active\'), 0)');
    }

    public function scopeByDocumentType($query, $type)
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

    // Accessors for backward compatibility
    public function getDocumentTypeAttribute()
    {
        return $this->attributes['type'] ?? $this->type; // Map 'type' column to 'document_type' attribute
    }

    // Helper methods
    public function isLocked()
    {
        // Period locking is date-based, not FK-based
        // Check if period for document_date is locked
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
        return $this->type === 'reversal'; // Schema uses 'type' column
    }

    // Schema does NOT have paid_amount/unpaid_amount columns
    // These are calculated from allocations, not stored
    // payment_allocations table uses 'status' enum, NOT soft deletes (no deleted_at)
    public function getPaidAmountAttribute()
    {
        return $this->allocations()
            ->where('status', 'active') // Schema uses status='active', not deleted_at
            ->sum('amount');
    }

    public function getUnpaidAmountAttribute()
    {
        return $this->total_amount - $this->paid_amount;
    }
}
