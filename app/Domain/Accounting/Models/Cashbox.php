<?php

namespace App\Domain\Accounting\Models;

use App\Domain\Accounting\Traits\BelongsToCompany;
use App\Domain\Accounting\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Cashbox extends Model
{
    use BelongsToCompany, HasAuditFields, SoftDeletes;
    
    protected $table = 'cashboxes';
    
    protected $fillable = [
        'company_id',
        'branch_id',
        'code',
        'name',
        'currency',
        'description',
        'is_active',
        'is_default',
        'opening_balance',
        'opening_balance_date',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'opening_balance' => 'decimal:2',
        'opening_balance_date' => 'date',
    ];
    
    // Relationships
    
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
    
    public function incomingPayments(): HasMany
    {
        return $this->hasMany(Payment::class)->where('direction', 'in');
    }
    
    public function outgoingPayments(): HasMany
    {
        return $this->hasMany(Payment::class)->where('direction', 'out');
    }
    
    // Scopes
    
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
    
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }
    
    // Computed Attributes
    
    /**
     * Get current balance from payments
     * Balance = opening_balance + sum(in) - sum(out)
     */
    public function getBalanceAttribute(): float
    {
        $result = DB::table('payments')
            ->where('cashbox_id', $this->id)
            ->where('status', 'confirmed')
            ->selectRaw("
                SUM(CASE WHEN direction = 'in' THEN net_amount ELSE 0 END) as total_in,
                SUM(CASE WHEN direction = 'out' THEN net_amount ELSE 0 END) as total_out
            ")
            ->first();
        
        $totalIn = (float) ($result->total_in ?? 0);
        $totalOut = (float) ($result->total_out ?? 0);
        
        return $this->opening_balance + $totalIn - $totalOut;
    }
    
    /**
     * Get balance as of a specific date
     */
    public function getBalanceAsOf($date): float
    {
        $result = DB::table('payments')
            ->where('cashbox_id', $this->id)
            ->where('status', 'confirmed')
            ->where('payment_date', '<=', $date)
            ->selectRaw("
                SUM(CASE WHEN direction = 'in' THEN net_amount ELSE 0 END) as total_in,
                SUM(CASE WHEN direction = 'out' THEN net_amount ELSE 0 END) as total_out
            ")
            ->first();
        
        $totalIn = (float) ($result->total_in ?? 0);
        $totalOut = (float) ($result->total_out ?? 0);
        
        // Only include opening balance if it's before or on the date
        $opening = 0;
        if ($this->opening_balance_date === null || $this->opening_balance_date <= $date) {
            $opening = $this->opening_balance;
        }
        
        return $opening + $totalIn - $totalOut;
    }
    
    /**
     * Get total inflows for a period
     */
    public function getTotalInflows($startDate = null, $endDate = null): float
    {
        $query = $this->incomingPayments()->where('status', 'confirmed');
        
        if ($startDate) {
            $query->where('payment_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('payment_date', '<=', $endDate);
        }
        
        return (float) $query->sum('net_amount');
    }
    
    /**
     * Get total outflows for a period
     */
    public function getTotalOutflows($startDate = null, $endDate = null): float
    {
        $query = $this->outgoingPayments()->where('status', 'confirmed');
        
        if ($startDate) {
            $query->where('payment_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('payment_date', '<=', $endDate);
        }
        
        return (float) $query->sum('net_amount');
    }
}
