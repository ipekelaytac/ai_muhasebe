<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\Party;
use App\Models\Document;
use Illuminate\Support\Facades\DB;

class VerifyMigration extends Command
{
    protected $signature = 'accounting:verify-migration';
    protected $description = 'Verify data migration integrity';

    public function handle()
    {
        $this->info('Verifying migration integrity...');
        $this->newLine();

        // 1. Verify customer balances
        $this->info('1. Verifying customer balances...');
        $customers = Customer::all();
        $discrepancies = 0;

        foreach ($customers as $customer) {
            // Old balance calculation
            $oldBalance = CustomerTransaction::where('customer_id', $customer->id)
                ->selectRaw('SUM(CASE WHEN type = "income" THEN amount ELSE -amount END) as balance')
                ->value('balance') ?? 0;

            // New balance calculation
            $party = Party::where('partyable_type', Customer::class)
                ->where('partyable_id', $customer->id)
                ->first();

            if ($party) {
                $newReceivable = Document::where('party_id', $party->id)
                    ->where('direction', 'receivable')
                    ->posted()
                    ->sum('unpaid_amount') ?? 0;

                $newPayable = Document::where('party_id', $party->id)
                    ->where('direction', 'payable')
                    ->posted()
                    ->sum('unpaid_amount') ?? 0;

                $newBalance = $newReceivable - $newPayable;

                if (abs($oldBalance - $newBalance) > 0.01) {
                    $this->warn("  Customer {$customer->name}: Old={$oldBalance}, New={$newBalance}, Diff=" . abs($oldBalance - $newBalance));
                    $discrepancies++;
                }
            }
        }

        if ($discrepancies === 0) {
            $this->info('  ✓ All customer balances match');
        } else {
            $this->error("  ✗ Found {$discrepancies} discrepancies");
        }

        $this->newLine();

        // 2. Verify transaction counts
        $this->info('2. Verifying transaction counts...');
        $oldCount = CustomerTransaction::count();
        $newDocCount = Document::whereNotNull('metadata->migrated_from')
            ->where('metadata->migrated_from', 'customer_transactions')
            ->count();

        $this->info("  Old transactions: {$oldCount}");
        $this->info("  New documents: {$newDocCount}");

        if ($oldCount === $newDocCount) {
            $this->info('  ✓ Transaction counts match');
        } else {
            $this->warn("  ⚠ Count mismatch: Diff=" . abs($oldCount - $newDocCount));
        }

        $this->newLine();

        // 3. Summary
        $this->info('Verification complete!');
        if ($discrepancies === 0 && $oldCount === $newDocCount) {
            $this->info('✓ All checks passed');
            return 0;
        } else {
            $this->warn('⚠ Some discrepancies found. Review the output above.');
            return 1;
        }
    }
}
