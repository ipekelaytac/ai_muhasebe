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
        'description',
        'quantity',
        'unit', // Schema has unit column
        'unit_price',
        'discount_percent', // Schema has discount_percent
        'discount_amount', // Schema has discount_amount
        'subtotal', // Schema uses subtotal, not amount
        'tax_rate',
        'tax_amount',
        'total', // Schema has total column
        'category_id',
        // Schema does NOT have metadata column - use tags (json) if needed
    ];

    protected $casts = [
        'quantity' => 'decimal:4', // Schema: decimal(12, 4)
        'unit_price' => 'decimal:4', // Schema: decimal(15, 4)
        'discount_percent' => 'decimal:2', // Schema: decimal(5, 2)
        'discount_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2', // Schema: decimal(5, 2)
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
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

    // Computed (for backward compatibility if code accesses ->amount)
    public function getAmountAttribute()
    {
        // Schema uses subtotal/total, not amount
        return $this->subtotal ?? 0;
    }
}
