<?php

namespace App\Domain\Accounting\Models;

use App\Domain\Accounting\Enums\ChequeStatus;
use App\Domain\Accounting\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChequeEvent extends Model
{
    use HasAuditFields;
    
    protected $table = 'cheque_events';
    
    protected $fillable = [
        'cheque_id',
        'from_status',
        'to_status',
        'event_date',
        'related_party_id',
        'related_payment_id',
        'notes',
    ];
    
    protected $casts = [
        'event_date' => 'date',
    ];
    
    // Relationships
    
    public function cheque(): BelongsTo
    {
        return $this->belongsTo(Cheque::class);
    }
    
    public function relatedParty(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'related_party_id');
    }
    
    public function relatedPayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'related_payment_id');
    }
    
    // Computed Attributes
    
    public function getFromStatusLabelAttribute(): ?string
    {
        return $this->from_status ? ChequeStatus::getLabel($this->from_status) : null;
    }
    
    public function getToStatusLabelAttribute(): string
    {
        return ChequeStatus::getLabel($this->to_status);
    }
    
    public function getEventDescriptionAttribute(): string
    {
        $from = $this->from_status_label ?? 'Yeni';
        $to = $this->to_status_label;
        
        return "{$from} → {$to}";
    }
}
