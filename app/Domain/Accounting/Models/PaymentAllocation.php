<?php

namespace App\Domain\Accounting\Models;

use App\Domain\Accounting\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAllocation extends Model
{
    use HasAuditFields;
    
    protected $table = 'payment_allocations';
    
    /**
     * Boot the model - prevent modifications if payment/document is in locked period
     */
    protected static function booted(): void
    {
        static::updating(function (PaymentAllocation $allocation) {
            $payment = $allocation->payment;
            $document = $allocation->document;
            
            if ($payment->isInLockedPeriod() || $document->isInLockedPeriod()) {
                throw new \Exception(
                    "Cannot update allocation in locked period. " .
                    "Payment: {$payment->payment_number}, Document: {$document->document_number}"
                );
            }
        });
        
        static::deleting(function (PaymentAllocation $allocation) {
            $payment = $allocation->payment;
            $document = $allocation->document;
            
            if ($payment->isInLockedPeriod() || $document->isInLockedPeriod()) {
                throw new \Exception(
                    "Cannot delete allocation in locked period. " .
                    "Use cancellation instead. Payment: {$payment->payment_number}, Document: {$document->document_number}"
                );
            }
        });
    }
    
    protected $fillable = [
        'payment_id',
        'document_id',
        'payroll_installment_id',
        'amount',
        'allocation_date',
        'notes',
        'status',
    ];
    
    protected $casts = [
        'amount' => 'decimal:2',
        'allocation_date' => 'date',
    ];
    
    // Relationships
    
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
    
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
    
    /**
     * Get the payroll installment this allocation is linked to (optional)
     */
    public function installment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\PayrollInstallment::class, 'payroll_installment_id');
    }
    
    // Scopes
    
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
    
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }
    
    public function scopeForDocument(Builder $query, int $documentId): Builder
    {
        return $query->where('document_id', $documentId);
    }
    
    public function scopeForPayment(Builder $query, int $paymentId): Builder
    {
        return $query->where('payment_id', $paymentId);
    }
    
    // Methods
    
    /**
     * Cancel this allocation
     */
    public function cancel(): void
    {
        $this->status = 'cancelled';
        $this->save();
        
        // Update document status
        $this->document->updateStatus();
    }
}
