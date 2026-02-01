<?php

namespace App\Services;

use App\Models\AccountingPeriod;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

            // Log audit (schema uses 'action' not 'event', no branch_id/description, only created_at not updated_at)
            $auditData = [
                'company_id' => $period->company_id,
                'auditable_type' => AccountingPeriod::class,
                'auditable_id' => $period->id,
                'action' => 'lock', // Schema uses 'action' enum, not 'event'
                'new_values' => $period->toArray(),
                'user_id' => Auth::id(),
                'created_at' => now(), // Schema only has created_at, not updated_at
            ];
            // Filter to only existing columns (schema does NOT have branch_id/description/event/updated_at)
            $auditData = $this->filterByExistingColumns('audit_logs', $auditData);
            AuditLog::create($auditData);

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

            // Log audit (schema uses 'action' not 'event', no branch_id/description, only created_at not updated_at)
            $auditData = [
                'company_id' => $period->company_id,
                'auditable_type' => AccountingPeriod::class,
                'auditable_id' => $period->id,
                'action' => 'unlock', // Schema uses 'action' enum, not 'event'
                'old_values' => ['status' => 'locked'],
                'new_values' => ['status' => 'open'],
                'user_id' => Auth::id(),
                'created_at' => now(), // Schema only has created_at, not updated_at
            ];
            // Filter to only existing columns (schema does NOT have branch_id/description/event/updated_at)
            $auditData = $this->filterByExistingColumns('audit_logs', $auditData);
            AuditLog::create($auditData);

            return $period->fresh();
        });
    }

    /**
     * Filter array to only include columns that exist in the table schema
     *
     * @param string $table
     * @param array $data
     * @return array
     */
    private function filterByExistingColumns(string $table, array $data): array
    {
        $filtered = [];
        foreach ($data as $key => $value) {
            if (Schema::hasColumn($table, $key)) {
                $filtered[$key] = $value;
            }
        }
        return $filtered;
    }
}
