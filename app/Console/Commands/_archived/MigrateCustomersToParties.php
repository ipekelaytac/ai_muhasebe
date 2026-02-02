<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
// Legacy model removed - table dropped
// use App\Models\Customer;
use App\Models\Party;
use Illuminate\Support\Facades\DB;

class MigrateCustomersToParties extends Command
{
    protected $signature = 'accounting:migrate-customers-to-parties {--dry-run : Run without making changes}';
    protected $description = 'Migrate customers table to parties table';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        $customers = Customer::all();
        $this->info("Found {$customers->count()} customers to migrate");

        $bar = $this->output->createProgressBar($customers->count());
        $bar->start();

        $created = 0;
        $skipped = 0;

        foreach ($customers as $customer) {
            // Check if party already exists
            $existingParty = Party::where('partyable_type', Customer::class)
                ->where('partyable_id', $customer->id)
                ->first();

            if ($existingParty) {
                $skipped++;
                $bar->advance();
                continue;
            }

            if (!$dryRun) {
                DB::transaction(function () use ($customer, &$created) {
                    Party::create([
                        'company_id' => $customer->company_id,
                        'branch_id' => $customer->branch_id,
                        'type' => $customer->type === 'customer' ? 'customer' : ($customer->type === 'supplier' ? 'supplier' : 'other'),
                        'code' => $customer->code,
                        'name' => $customer->name,
                        'phone' => $customer->phone,
                        'email' => $customer->email,
                        'address' => $customer->address,
                        'tax_number' => $customer->tax_number,
                        'tax_office' => $customer->tax_office,
                        'is_active' => $customer->status ?? true,
                        'notes' => null,
                        'partyable_type' => Customer::class,
                        'partyable_id' => $customer->id,
                    ]);
                    $created++;
                });
            } else {
                $created++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Migration complete!");
        $this->info("Created: {$created}");
        $this->info("Skipped: {$skipped}");

        return 0;
    }
}
