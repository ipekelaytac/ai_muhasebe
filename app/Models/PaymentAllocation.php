<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentAllocation extends Model
{
    use HasFactory;
    // Schema does NOT have deleted_at - uses status='active'/'cancelled' instead

    protected $fillable = [
        'payment_id',
        'document_id',
        'amount',
        'allocation_date', // Schema has allocation_date
        'status', // Schema uses status enum ('active', 'cancelled')
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // Relationships
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Note: Amounts are calculated via accessors (Payment::getAllocatedAmountAttribute, Document::getPaidAmountAttribute)
    // No need to recalculate on save - accessors calculate on-demand from allocations with status='active'
}
