<?php

namespace App\Domain\Accounting\Models;

use App\Domain\Accounting\Traits\BelongsToCompany;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingPeriod extends Model
{
    use BelongsToCompany;
    
    protected $table = 'accounting_periods';
    
    protected $fillable = [
        'company_id',
        'year',
        'month',
        'start_date',
        'end_date',
        'status',
        'locked_at',
        'locked_by',
        'lock_notes',
    ];
    
    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'locked_at' => 'datetime',
    ];
    
    public function lockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }
    
    // Scopes
    
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }
    
    public function scopeLocked(Builder $query): Builder
    {
        return $query->where('status', 'locked');
    }
    
    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', 'closed');
    }
    
    // Helper Methods
    
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
    
    public function isLocked(): bool
    {
        return in_array($this->status, ['locked', 'closed']);
    }
    
    public function getPeriodLabel(): string
    {
        $months = [
            1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
            5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
            9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık',
        ];
        
        return $months[$this->month] . ' ' . $this->year;
    }
    
    /**
     * Get or create period for a given date
     */
    public static function getOrCreateForDate(int $companyId, $date): self
    {
        $date = \Carbon\Carbon::parse($date);
        
        return static::firstOrCreate(
            [
                'company_id' => $companyId,
                'year' => $date->year,
                'month' => $date->month,
            ],
            [
                'start_date' => $date->copy()->startOfMonth(),
                'end_date' => $date->copy()->endOfMonth(),
                'status' => 'open',
            ]
        );
    }
}
