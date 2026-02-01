<?php

namespace App\Domain\Accounting\Models;

use App\Domain\Accounting\Enums\ChequeStatus;
use App\Domain\Accounting\Traits\BelongsToCompany;
use App\Domain\Accounting\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cheque extends Model
{
    use BelongsToCompany, HasAuditFields, SoftDeletes;
    
    protected $table = 'cheques';
    
    protected $fillable = [
        'company_id',
        'branch_id',
        'cheque_number',
        'serial_number',
        'type',
        'party_id',
        'drawer_name',
        'drawer_tax_number',
        'bank_name',
        'bank_branch',
        'account_number',
        'bank_account_id',
        'issue_date',
        'due_date',
        'receive_date',
        'amount',
        'currency',
        'status',
        'endorsed_to_party_id',
        'endorsement_date',
        'document_id',
        'cleared_payment_id',
        'bounce_date',
        'bounce_reason',
        'bounce_fee',
        'notes',
    ];
    
    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'receive_date' => 'date',
        'endorsement_date' => 'date',
        'bounce_date' => 'date',
        'amount' => 'decimal:2',
        'bounce_fee' => 'decimal:2',
    ];
    
    // Relationships
    
    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }
    
    public function endorsedToParty(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'endorsed_to_party_id');
    }
    
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }
    
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
    
    public function clearedPayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'cleared_payment_id');
    }
    
    public function events(): HasMany
    {
        return $this->hasMany(ChequeEvent::class)->orderBy('created_at');
    }
    
    public function attachments(): MorphMany
    {
        return $this->morphMany(DocumentAttachment::class, 'attachable');
    }
    
    // Scopes
    
    public function scopeReceived(Builder $query): Builder
    {
        return $query->where('type', 'received');
    }
    
    public function scopeIssued(Builder $query): Builder
    {
        return $query->where('type', 'issued');
    }
    
    public function scopeInPortfolio(Builder $query): Builder
    {
        return $query->where('status', ChequeStatus::IN_PORTFOLIO);
    }
    
    public function scopeDeposited(Builder $query): Builder
    {
        return $query->where('status', ChequeStatus::DEPOSITED);
    }
    
    public function scopeCollected(Builder $query): Builder
    {
        return $query->where('status', ChequeStatus::COLLECTED);
    }
    
    public function scopeBounced(Builder $query): Builder
    {
        return $query->where('status', ChequeStatus::BOUNCED);
    }
    
    public function scopeEndorsed(Builder $query): Builder
    {
        return $query->where('status', ChequeStatus::ENDORSED);
    }
    
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
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
    
    /**
     * Cheques that affect cashflow forecast
     */
    public function scopeForForecast(Builder $query): Builder
    {
        return $query->whereIn('status', ChequeStatus::FORECAST_STATUSES);
    }
    
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->forForecast()
            ->where('due_date', '<', now()->toDateString());
    }
    
    // Computed Attributes
    
    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return ChequeStatus::getLabel($this->status);
    }
    
    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'received' ? 'Alınan Çek' : 'Verilen Çek';
    }
    
    /**
     * Check if cheque is still pending (affects forecast)
     */
    public function getIsPendingAttribute(): bool
    {
        return in_array($this->status, ChequeStatus::FORECAST_STATUSES);
    }
    
    /**
     * Days until due
     */
    public function getDaysUntilDueAttribute(): int
    {
        return now()->startOfDay()->diffInDays($this->due_date, false);
    }
    
    /**
     * Check if overdue
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->is_pending && $this->due_date < now()->toDateString();
    }
    
    // Methods
    
    /**
     * Record a status change event
     */
    public function recordEvent(
        string $toStatus,
        ?int $relatedPartyId = null,
        ?int $relatedPaymentId = null,
        ?string $notes = null
    ): ChequeEvent {
        $fromStatus = $this->status;
        
        return $this->events()->create([
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'event_date' => now()->toDateString(),
            'related_party_id' => $relatedPartyId,
            'related_payment_id' => $relatedPaymentId,
            'notes' => $notes,
        ]);
    }
    
    /**
     * Deposit cheque to bank
     */
    public function deposit(int $bankAccountId, ?string $notes = null): void
    {
        $this->bank_account_id = $bankAccountId;
        $this->status = ChequeStatus::DEPOSITED;
        $this->save();
        
        $this->recordEvent(ChequeStatus::DEPOSITED, null, null, $notes);
    }
    
    /**
     * Mark cheque as collected
     */
    public function markCollected(Payment $payment, ?string $notes = null): void
    {
        $this->cleared_payment_id = $payment->id;
        $this->status = ChequeStatus::COLLECTED;
        $this->save();
        
        $this->recordEvent(ChequeStatus::COLLECTED, null, $payment->id, $notes);
    }
    
    /**
     * Mark cheque as bounced
     */
    public function markBounced(string $reason, float $fee = 0, ?string $notes = null): void
    {
        $this->status = ChequeStatus::BOUNCED;
        $this->bounce_date = now()->toDateString();
        $this->bounce_reason = $reason;
        $this->bounce_fee = $fee;
        $this->save();
        
        $this->recordEvent(ChequeStatus::BOUNCED, null, null, $notes ?? $reason);
    }
    
    /**
     * Endorse cheque to another party
     */
    public function endorse(int $toPartyId, ?string $notes = null): void
    {
        $this->endorsed_to_party_id = $toPartyId;
        $this->endorsement_date = now()->toDateString();
        $this->status = ChequeStatus::ENDORSED;
        $this->save();
        
        $this->recordEvent(ChequeStatus::ENDORSED, $toPartyId, null, $notes);
    }
    
    /**
     * Generate cheque number
     */
    public static function generateNumber(int $companyId, string $type): string
    {
        $year = now()->year;
        $prefix = $type === 'received' ? 'CA' : 'CV';
        
        $sequence = NumberSequence::getNext($companyId, null, 'cheque', $type, $year);
        
        return $prefix . $year . '-' . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }
}
