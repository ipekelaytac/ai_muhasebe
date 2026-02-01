<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'accounting_period_id',
        'document_number',
        'document_type',
        'direction',
        'status',
        'party_id',
        'document_date',
        'due_date',
        'total_amount',
        'paid_amount',
        'unpaid_amount',
        'reverses_document_id',
        'original_document_id',
        'category_id',
        'description',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'document_date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'unpaid_amount' => 'decimal:2',
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
        return $query->where('status', 'posted');
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
        return $query->whereColumn('unpaid_amount', '>', 0);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereColumn('unpaid_amount', '>', 0);
    }

    public function scopeByDocumentType($query, $type)
    {
        return $query->where('document_type', $type);
    }

    public function scopeInPeriod($query, $periodId)
    {
        return $query->where('accounting_period_id', $periodId);
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
        return $this->document_type === 'reversal';
    }

    public function recalculatePaidAmount()
    {
        $paidAmount = $this->allocations()
            ->whereNull('deleted_at')
            ->sum('amount');

        $this->paid_amount = $paidAmount;
        $this->unpaid_amount = $this->total_amount - $paidAmount;
        $this->save();
    }

    // Note: unpaid_amount is stored in DB but should be recalculated when needed
    // Use recalculatePaidAmount() method to ensure accuracy
}
