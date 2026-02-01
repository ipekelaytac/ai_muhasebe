<?php

namespace App\Domain\Accounting\Models;

use App\Domain\Accounting\Traits\BelongsToCompany;
use App\Domain\Accounting\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class BankAccount extends Model
{
    use BelongsToCompany, HasAuditFields, SoftDeletes;
    
    protected $table = 'bank_accounts';
    
    protected $fillable = [
        'company_id',
        'branch_id',
        'code',
        'name',
        'bank_name',
        'branch_name',
        'account_number',
        'iban',
        'currency',
        'account_type',
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
    
    public function cheques(): HasMany
    {
        return $this->hasMany(Cheque::class);
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
    
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('account_type', $type);
    }
    
    // Computed Attributes
    
    /**
     * Get current balance from payments
     */
    public function getBalanceAttribute(): float
    {
        $result = DB::table('payments')
            ->where('bank_account_id', $this->id)
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
            ->where('bank_account_id', $this->id)
            ->where('status', 'confirmed')
            ->where('payment_date', '<=', $date)
            ->selectRaw("
                SUM(CASE WHEN direction = 'in' THEN net_amount ELSE 0 END) as total_in,
                SUM(CASE WHEN direction = 'out' THEN net_amount ELSE 0 END) as total_out
            ")
            ->first();
        
        $totalIn = (float) ($result->total_in ?? 0);
        $totalOut = (float) ($result->total_out ?? 0);
        
        $opening = 0;
        if ($this->opening_balance_date === null || $this->opening_balance_date <= $date) {
            $opening = $this->opening_balance;
        }
        
        return $opening + $totalIn - $totalOut;
    }
    
    /**
     * Get formatted IBAN
     */
    public function getFormattedIbanAttribute(): ?string
    {
        if (!$this->iban) {
            return null;
        }
        
        // Format: TR00 0000 0000 0000 0000 0000 00
        $clean = str_replace(' ', '', $this->iban);
        return implode(' ', str_split($clean, 4));
    }
    
    /**
     * Get account type label
     */
    public function getAccountTypeLabelAttribute(): string
    {
        return match ($this->account_type) {
            'checking' => 'Vadesiz',
            'savings' => 'Vadeli',
            'credit' => 'Kredi',
            'pos' => 'POS',
            default => $this->account_type,
        };
    }
}
