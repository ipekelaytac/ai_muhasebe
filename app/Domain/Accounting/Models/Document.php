<?php

namespace App\Domain\Accounting\Models;

use App\Domain\Accounting\Enums\DocumentStatus;
use App\Domain\Accounting\Enums\DocumentType;
use App\Domain\Accounting\Traits\BelongsToCompany;
use App\Domain\Accounting\Traits\HasAuditFields;
use App\Domain\Accounting\Traits\HasPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Document extends Model
{
    use BelongsToCompany, HasAuditFields, HasFactory, HasPeriod, SoftDeletes;

    protected static function newFactory()
    {
        return \Database\Factories\DocumentFactory::new();
    }
    
    protected $table = 'documents';
    
    /**
     * Boot the model - enforce period locking
     */
    protected static function booted(): void
    {
        static::updating(function (Document $document) {
            if (!$document->isInLockedPeriod()) {
                return;
            }

            $dirty = array_keys($document->getDirty());

            $allowedInLockedPeriod = [
                'status',
                'reversed_document_id',
                'reversal_document_id',
                'notes',
                'updated_by',
                'updated_at',
            ];

            $onlyAllowed = empty(array_diff($dirty, $allowedInLockedPeriod));

            if (!$onlyAllowed) {
                throw new \Exception(
                    "Cannot update document in locked period: {$document->document_number}. " .
                    "Use reversal in an open period instead."
                );
            }
        });
        
        static::deleting(function (Document $document) {
            // Prevent hard deletes - use soft delete or cancellation
            if ($document->isInLockedPeriod()) {
                throw new \Exception(
                    "Cannot delete document in locked period: {$document->document_number}. " .
                    "Use cancellation/reversal instead."
                );
            }
        });
    }
    
    protected $fillable = [
        'company_id',
        'branch_id',
        'document_number',
        'reference_number',
        'type',
        'direction',
        'party_id',
        'document_date',
        'due_date',
        'total_amount',
        'currency',
        'exchange_rate',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'category_id',
        'tags',
        'status',
        'reversed_document_id',
        'reversal_document_id',
        'source_type',
        'source_id',
        'cheque_id',
        'period_year',
        'period_month',
        'description',
        'notes',
    ];
    
    protected $casts = [
        'document_date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tags' => 'array',
        'period_year' => 'integer',
        'period_month' => 'integer',
    ];
    
    // Relationships
    
    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }
    
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }
    
    public function lines(): HasMany
    {
        return $this->hasMany(DocumentLine::class);
    }
    
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }
    
    public function activeAllocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class)->where('status', 'active');
    }
    
    public function cheque(): BelongsTo
    {
        return $this->belongsTo(Cheque::class);
    }
    
    public function reversedDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'reversed_document_id');
    }
    
    public function reversalDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'reversal_document_id');
    }
    
    public function source(): MorphTo
    {
        return $this->morphTo();
    }
    
    public function attachments(): MorphMany
    {
        return $this->morphMany(DocumentAttachment::class, 'attachable');
    }
    
    // Scopes
    
    public function scopePayables(Builder $query): Builder
    {
        return $query->where('direction', 'payable');
    }

    /** Alias for scopePayables - used by ReportController */
    public function scopePayable(Builder $query): Builder
    {
        return $query->where('direction', 'payable');
    }
    
    public function scopeReceivables(Builder $query): Builder
    {
        return $query->where('direction', 'receivable');
    }
    
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }
    
    public function scopeOfTypes(Builder $query, array $types): Builder
    {
        return $query->whereIn('type', $types);
    }
    
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', DocumentStatus::OPEN);
    }

    /** Posted = not draft/cancelled/reversed (pending, partial, settled) - used by ReportController */
    public function scopePosted(Builder $query): Builder
    {
        return $query->whereIn('status', [DocumentStatus::PENDING, DocumentStatus::PARTIAL, DocumentStatus::SETTLED]);
    }

    /** Unpaid = has remaining amount (pending or partial status) - used by ReportController */
    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->whereIn('status', [DocumentStatus::PENDING, DocumentStatus::PARTIAL]);
    }
    
    public function scopeClosed(Builder $query): Builder
    {
        return $query->whereIn('status', DocumentStatus::CLOSED);
    }
    
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }
    
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', DocumentStatus::PENDING);
    }
    
    public function scopePartial(Builder $query): Builder
    {
        return $query->where('status', DocumentStatus::PARTIAL);
    }
    
    public function scopeSettled(Builder $query): Builder
    {
        return $query->where('status', DocumentStatus::SETTLED);
    }
    
    public function scopeForParty(Builder $query, int $partyId): Builder
    {
        return $query->where('party_id', $partyId);
    }
    
    public function scopeDueBefore(Builder $query, $date): Builder
    {
        return $query->where('due_date', '<', $date);
    }
    
    public function scopeDueAfter(Builder $query, $date): Builder
    {
        return $query->where('due_date', '>', $date);
    }
    
    public function scopeDueBetween(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('due_date', [$startDate, $endDate]);
    }
    
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->open()
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString());
    }
    
    public function scopeEmployeeDues(Builder $query): Builder
    {
        return $query->whereIn('type', DocumentType::EMPLOYEE_TYPES);
    }
    
    // Computed Attributes
    
    /**
     * Get allocated amount (sum of active allocations)
     */
    public function getAllocatedAmountAttribute(): float
    {
        return (float) $this->activeAllocations()->sum('amount');
    }
    
    /**
     * Get unpaid/remaining amount
     */
    public function getUnpaidAmountAttribute(): float
    {
        return max(0, $this->total_amount - $this->allocated_amount);
    }
    
    /**
     * Check if document is fully settled
     */
    public function getIsSettledAttribute(): bool
    {
        return $this->unpaid_amount <= 0.001; // Small tolerance for rounding
    }
    
    /**
     * Check if document is partially settled
     */
    public function getIsPartialAttribute(): bool
    {
        $allocated = $this->allocated_amount;
        return $allocated > 0 && $allocated < $this->total_amount;
    }
    
    /**
     * Get days overdue (negative if not yet due)
     */
    public function getDaysOverdueAttribute(): ?int
    {
        if (!$this->due_date) {
            return null;
        }
        return now()->startOfDay()->diffInDays($this->due_date, false) * -1;
    }
    
    /**
     * Get document type label
     */
    public function getTypeLabelAttribute(): string
    {
        return DocumentType::getLabel($this->type);
    }
    
    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return DocumentStatus::getLabel($this->status);
    }
    
    // Methods
    
    /**
     * Update status based on allocations
     */
    public function updateStatus(): void
    {
        if ($this->status === DocumentStatus::CANCELLED || $this->status === DocumentStatus::REVERSED) {
            return;
        }
        
        $allocated = $this->fresh()->allocated_amount;
        
        if ($allocated >= $this->total_amount - 0.001) {
            $this->status = DocumentStatus::SETTLED;
        } elseif ($allocated > 0) {
            $this->status = DocumentStatus::PARTIAL;
        } else {
            $this->status = DocumentStatus::PENDING;
        }
        
        $this->save();
    }
    
    /**
     * Check if document can be modified (not in locked period)
     */
    public function canModify(): bool
    {
        if ($this->isInLockedPeriod()) {
            return false;
        }
        
        return in_array($this->status, [DocumentStatus::DRAFT, DocumentStatus::PENDING]);
    }
    
    /**
     * Generate next document number (thread-safe with collision detection)
     */
    public static function generateNumber(int $companyId, ?int $branchId, string $type): string
    {
        $year = now()->year;
        $prefix = match ($type) {
            DocumentType::SUPPLIER_INVOICE => 'AF',
            DocumentType::CUSTOMER_INVOICE => 'SF',
            DocumentType::EXPENSE_DUE => 'GT',
            DocumentType::INCOME_DUE => 'GL',
            DocumentType::PAYROLL_DUE => 'MT',
            DocumentType::OVERTIME_DUE => 'MZ',
            DocumentType::MEAL_DUE => 'YM',
            DocumentType::ADVANCE_GIVEN => 'AV',
            DocumentType::ADVANCE_RECEIVED => 'AA',
            DocumentType::CHEQUE_RECEIVABLE => 'CA',
            DocumentType::CHEQUE_PAYABLE => 'CV',
            default => 'BL',
        };
        
        // Get sequence number
        $sequence = NumberSequence::getNext($companyId, $branchId, 'document', $type, $year);
        
        // Generate base document number
        $baseNumber = $prefix . $year . '-' . str_pad($sequence, 6, '0', STR_PAD_LEFT);
        
        // Check if this number already exists (race condition protection)
        $maxRetries = 5;
        $attempt = 0;
        $documentNumber = $baseNumber;
        
        while ($attempt < $maxRetries) {
            // Check if document number already exists
            $exists = static::where('company_id', $companyId)
                ->where('document_number', $documentNumber)
                ->exists();
            
            if (!$exists) {
                // Number is available, return it
                return $documentNumber;
            }
            
            // Number exists, generate a new one with suffix
            $attempt++;
            $suffix = str_pad($attempt, 2, '0', STR_PAD_LEFT);
            $documentNumber = $baseNumber . '-' . $suffix;
        }
        
        // If all retries failed, use timestamp-based suffix as fallback
        $timestampSuffix = substr(md5(microtime(true) . $companyId . $branchId), 0, 4);
        return $baseNumber . '-' . strtoupper($timestampSuffix);
    }
}
