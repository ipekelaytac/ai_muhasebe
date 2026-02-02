<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VerifyLegacyTablesEmpty extends Command
{
    protected $signature = 'accounting:verify-legacy-empty';
    protected $description = 'Verify that legacy accounting tables are empty before dropping them';

    protected $legacyTables = [
        // Drop order: child tables first (FK dependencies)
        'advance_settlements',    // FK to advances
        'transaction_attachments', // FK to finance_transactions
        'customer_transactions',  // FK to customers
        'advances',
        'finance_transactions',
        'checks',
        'customers',
    ];

    public function handle()
    {
        $this->info('Verifying legacy accounting tables are empty...');
        $this->newLine();

        $hasData = false;
        $emptyTables = [];
        $nonEmptyTables = [];
        $missingTables = [];

        foreach ($this->legacyTables as $table) {
            if (!Schema::hasTable($table)) {
                $missingTables[] = $table;
                $this->warn("  ⚠️  Table '{$table}' does not exist (OK to skip)");
                continue;
            }

            try {
                $count = DB::table($table)->count();
                
                if ($count > 0) {
                    $hasData = true;
                    $nonEmptyTables[] = ['table' => $table, 'count' => $count];
                    $this->error("  ❌ Table '{$table}' has {$count} records - CANNOT DROP!");
                } else {
                    $emptyTables[] = $table;
                    $this->info("  ✅ Table '{$table}' is empty (OK to drop)");
                }
            } catch (\Exception $e) {
                $this->error("  ❌ Error checking table '{$table}': " . $e->getMessage());
                $hasData = true;
            }
        }

        $this->newLine();
        $this->line('Summary:');
        $this->info("  Empty tables: " . count($emptyTables));
        $this->warn("  Missing tables: " . count($missingTables));
        
        if (count($nonEmptyTables) > 0) {
            $this->error("  Non-empty tables: " . count($nonEmptyTables));
            $this->newLine();
            $this->error('STOPPING: Cannot drop tables with data!');
            $this->newLine();
            $this->table(['Table', 'Record Count'], $nonEmptyTables);
            return 1;
        }

        $this->newLine();
        $this->info('✅ All legacy tables are empty. Safe to proceed with dropping them.');
        return 0;
    }
}
