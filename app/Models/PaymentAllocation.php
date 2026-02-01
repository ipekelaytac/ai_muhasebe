<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentAllocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payment_id',
        'document_id',
        'amount',
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

    // Events
    protected static function booted()
    {
        static::created(function ($allocation) {
            $allocation->payment->recalculateAllocatedAmount();
            $allocation->document->recalculatePaidAmount();
        });

        static::updated(function ($allocation) {
            $allocation->payment->recalculateAllocatedAmount();
            $allocation->document->recalculatePaidAmount();
        });

        static::deleted(function ($allocation) {
            $allocation->payment->recalculateAllocatedAmount();
            $allocation->document->recalculatePaidAmount();
        });
    }
}
