<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Models\AccountingPeriod;
use App\Domain\Accounting\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PeriodService
{
    /**
     * Get or create period for a date
     */
    public function getOrCreatePeriod(int $companyId, $date): AccountingPeriod
    {
        return AccountingPeriod::getOrCreateForDate($companyId, $date);
    }
    
    /**
     * Check if a period is open
     */
    public function isPeriodOpen(int $companyId, int $year, int $month): bool
    {
        $period = AccountingPeriod::where('company_id', $companyId)
            ->where('year', $year)
            ->where('month', $month)
            ->first();
        
        // If period doesn't exist, it's considered open
        if (!$period) {
            return true;
        }
        
        return $period->status === 'open';
    }
    
    /**
     * Check if a date is in an open period
     */
    public function isDateInOpenPeriod(int $companyId, $date): bool
    {
        $date = \Carbon\Carbon::parse($date);
        return $this->isPeriodOpen($companyId, $date->year, $date->month);
    }
    
    /**
     * Lock a period
     */
    public function lockPeriod(int $companyId, int $year, int $month, ?string $notes = null): AccountingPeriod
    {
        return DB::transaction(function () use ($companyId, $year, $month, $notes) {
            $period = $this->getOrCreatePeriod(
                $companyId,
                \Carbon\Carbon::createFromDate($year, $month, 1)
            );
            
            if ($period->status !== 'open') {
                throw new \Exception("Dönem zaten kilitli: {$period->getPeriodLabel()}");
            }
            
            $oldValues = $period->toArray();
            
            $period->update([
                'status' => 'locked',
                'locked_at' => now(),
                'locked_by' => Auth::id(),
                'lock_notes' => $notes,
            ]);
            
            AuditLog::log($period, 'lock', $oldValues, $period->toArray());
            
            return $period;
        });
    }
    
    /**
     * Unlock a period (requires special permission)
     */
    public function unlockPeriod(int $companyId, int $year, int $month, ?string $notes = null): AccountingPeriod
    {
        return DB::transaction(function () use ($companyId, $year, $month, $notes) {
            $period = AccountingPeriod::where('company_id', $companyId)
                ->where('year', $year)
                ->where('month', $month)
                ->firstOrFail();
            
            if ($period->status === 'open') {
                throw new \Exception("Dönem zaten açık: {$period->getPeriodLabel()}");
            }
            
            if ($period->status === 'closed') {
                throw new \Exception("Kapatılmış dönem açılamaz: {$period->getPeriodLabel()}");
            }
            
            $oldValues = $period->toArray();
            
            $period->update([
                'status' => 'open',
                'locked_at' => null,
                'locked_by' => null,
                'lock_notes' => $notes ? "Kilit açıldı: {$notes}" : null,
            ]);
            
            AuditLog::log($period, 'unlock', $oldValues, $period->toArray());
            
            return $period;
        });
    }
    
    /**
     * Close a period permanently
     */
    public function closePeriod(int $companyId, int $year, int $month): AccountingPeriod
    {
        return DB::transaction(function () use ($companyId, $year, $month) {
            $period = AccountingPeriod::where('company_id', $companyId)
                ->where('year', $year)
                ->where('month', $month)
                ->firstOrFail();
            
            if ($period->status !== 'locked') {
                throw new \Exception("Sadece kilitli dönemler kapatılabilir: {$period->getPeriodLabel()}");
            }
            
            $oldValues = $period->toArray();
            
            $period->update([
                'status' => 'closed',
            ]);
            
            AuditLog::log($period, 'status_change', $oldValues, $period->toArray());
            
            return $period;
        });
    }
    
    /**
     * Get all periods for a company
     */
    public function getPeriods(int $companyId, ?int $year = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = AccountingPeriod::where('company_id', $companyId)
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc');
        
        if ($year) {
            $query->where('year', $year);
        }
        
        return $query->get();
    }
    
    /**
     * Get open periods
     */
    public function getOpenPeriods(int $companyId): \Illuminate\Database\Eloquent\Collection
    {
        return AccountingPeriod::where('company_id', $companyId)
            ->where('status', 'open')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();
    }
    
    /**
     * Validate that operation is allowed for period
     */
    public function validatePeriodOpen(int $companyId, $date): void
    {
        if (!$this->isDateInOpenPeriod($companyId, $date)) {
            $date = \Carbon\Carbon::parse($date);
            throw new \Exception("Bu tarih kilitli bir dönemde: {$date->format('F Y')}");
        }
    }
}
