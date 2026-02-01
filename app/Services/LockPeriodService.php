<?php

namespace App\Services;

use App\Models\AccountingPeriod;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class LockPeriodService
{
    /**
     * Lock an accounting period
     *
     * @param AccountingPeriod $period
     * @param string|null $notes
     * @return AccountingPeriod
     * @throws \Exception
     */
    public function lock(AccountingPeriod $period, ?string $notes = null): AccountingPeriod
    {
        return DB::transaction(function () use ($period, $notes) {
            if ($period->isLocked()) {
                throw new \Exception('Period is already locked');
            }

            $period->status = 'locked';
            $period->locked_by = Auth::id();
            $period->locked_at = now();
            $period->lock_notes = $notes;
            $period->save();

            // Log audit
            AuditLog::create([
                'company_id' => $period->company_id,
                'branch_id' => $period->branch_id,
                'auditable_type' => AccountingPeriod::class,
                'auditable_id' => $period->id,
                'user_id' => Auth::id(),
                'event' => 'locked',
                'new_values' => $period->toArray(),
                'description' => "Period {$period->year}-{$period->month} locked",
            ]);

            return $period->fresh();
        });
    }

    /**
     * Unlock an accounting period (admin only)
     *
     * @param AccountingPeriod $period
     * @param string|null $reason
     * @return AccountingPeriod
     * @throws \Exception
     */
    public function unlock(AccountingPeriod $period, ?string $reason = null): AccountingPeriod
    {
        return DB::transaction(function () use ($period, $reason) {
            if ($period->isOpen()) {
                throw new \Exception('Period is already open');
            }

            $period->status = 'open';
            $period->locked_by = null;
            $period->locked_at = null;
            $period->lock_notes = $reason ? ($period->lock_notes . "\nUnlocked: " . $reason) : $period->lock_notes;
            $period->save();

            // Log audit
            AuditLog::create([
                'company_id' => $period->company_id,
                'branch_id' => $period->branch_id,
                'auditable_type' => AccountingPeriod::class,
                'auditable_id' => $period->id,
                'user_id' => Auth::id(),
                'event' => 'unlocked',
                'old_values' => ['status' => 'locked'],
                'new_values' => ['status' => 'open'],
                'description' => "Period {$period->year}-{$period->month} unlocked" . ($reason ? ": {$reason}" : ''),
            ]);

            return $period->fresh();
        });
    }
}
