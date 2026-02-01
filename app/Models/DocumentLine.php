<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'line_number',
        'category_id',
        'description',
        'quantity',
        'unit_price',
        'amount',
        'tax_rate',
        'tax_amount',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    // Relationships
    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function category()
    {
        return $this->belongsTo(FinanceCategory::class);
    }

    // Computed
    public function getTotalAmountAttribute()
    {
        if ($this->quantity && $this->unit_price) {
            return $this->quantity * $this->unit_price;
        }
        return $this->amount;
    }
}
