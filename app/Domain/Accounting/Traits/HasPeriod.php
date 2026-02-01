<?php

namespace App\Domain\Accounting\Traits;

use App\Domain\Accounting\Models\AccountingPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Trait for models that belong to an accounting period
 */
trait HasPeriod
{
    /**
     * Boot the trait
     */
    public static function bootHasPeriod(): void
    {
        static::creating(function ($model) {
            // Auto-set period from document_date or payment_date
            if (empty($model->period_year) || empty($model->period_month)) {
                $dateField = $model->document_date ?? $model->payment_date ?? now();
                $date = Carbon::parse($dateField);
                $model->period_year = $date->year;
                $model->period_month = $date->month;
            }
        });
    }
    
    /**
     * Check if record is in a locked period
     */
    public function isInLockedPeriod(): bool
    {
        $period = AccountingPeriod::where('company_id', $this->company_id)
            ->where('year', $this->period_year)
            ->where('month', $this->period_month)
            ->first();
        
        return $period && $period->status !== 'open';
    }
    
    /**
     * Scope to filter by period
     */
    public function scopeForPeriod(Builder $query, int $year, int $month): Builder
    {
        return $query->where('period_year', $year)->where('period_month', $month);
    }
    
    /**
     * Scope to filter by year
     */
    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->where('period_year', $year);
    }
    
    /**
     * Scope for date range
     */
    public function scopeInDateRange(Builder $query, $startDate, $endDate, string $dateColumn = null): Builder
    {
        $column = $dateColumn ?? ($this->document_date ? 'document_date' : 'payment_date');
        
        if ($startDate) {
            $query->where($column, '>=', $startDate);
        }
        if ($endDate) {
            $query->where($column, '<=', $endDate);
        }
        
        return $query;
    }
}
