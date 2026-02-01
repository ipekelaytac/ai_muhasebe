<?php

namespace App\Domain\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentLine extends Model
{
    protected $table = 'document_lines';
    
    protected $fillable = [
        'document_id',
        'line_number',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'discount_percent',
        'discount_amount',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total',
        'category_id',
        'tags',
    ];
    
    protected $casts = [
        'line_number' => 'integer',
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'tags' => 'array',
    ];
    
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
    
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }
    
    /**
     * Calculate line totals
     */
    public function calculateTotals(): void
    {
        $gross = $this->quantity * $this->unit_price;
        
        // Apply discount
        if ($this->discount_percent > 0) {
            $this->discount_amount = $gross * ($this->discount_percent / 100);
        }
        
        $this->subtotal = $gross - $this->discount_amount;
        
        // Apply tax
        if ($this->tax_rate > 0) {
            $this->tax_amount = $this->subtotal * ($this->tax_rate / 100);
        }
        
        $this->total = $this->subtotal + $this->tax_amount;
    }
}
