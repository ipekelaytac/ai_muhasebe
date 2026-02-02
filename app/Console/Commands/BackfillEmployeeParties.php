<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Domain\Accounting\Models\Party;
use App\Domain\Accounting\Enums\PartyType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillEmployeeParties extends Command
{
    protected $signature = 'accounting:backfill-employee-parties 
                            {--dry-run : Show what would be done without making changes}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Create Party records for existing employees and link them';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }

        $employees = Employee::whereNull('party_id')->get();
        
        if ($employees->isEmpty()) {
            $this->info('✅ All employees already have party_id assigned.');
            return 0;
        }

        $this->info("Found {$employees->count()} employees without party_id.");

        if (!$dryRun && !$force) {
            if (!$this->confirm('Do you want to create Party records for these employees?', true)) {
                $this->info('Cancelled.');
                return 0;
            }
        }

        $created = 0;
        $linked = 0;
        $skipped = 0;
        $errors = [];

        foreach ($employees as $employee) {
            try {
                DB::beginTransaction();

                // Check if party already exists for this employee (by name + company + branch)
                $existingParty = Party::where('company_id', $employee->company_id)
                    ->where('branch_id', $employee->branch_id)
                    ->where('type', PartyType::EMPLOYEE)
                    ->where('name', $employee->full_name)
                    ->whereNull('linkable_id') // Not linked to another employee
                    ->first();

                if ($existingParty && !$existingParty->linkable_id) {
                    // Link existing party to this employee
                    if (!$dryRun) {
                        $existingParty->update([
                            'linkable_type' => Employee::class,
                            'linkable_id' => $employee->id,
                        ]);
                        $employee->party_id = $existingParty->id;
                        $employee->saveQuietly(); // Bypass observer to avoid recursion
                    }
                    $linked++;
                    $this->line("  ✓ Linked existing party for: {$employee->full_name}");
                } else {
                    // Create new party
                    $code = Party::generateCode($employee->company_id, PartyType::EMPLOYEE);
                    
                    if (!$dryRun) {
                        $party = Party::create([
                            'company_id' => $employee->company_id,
                            'branch_id' => $employee->branch_id,
                            'type' => PartyType::EMPLOYEE,
                            'linkable_type' => Employee::class,
                            'linkable_id' => $employee->id,
                            'code' => $code,
                            'name' => $employee->full_name,
                            'phone' => $employee->phone,
                            'is_active' => (bool) $employee->status,
                        ]);

                        $employee->party_id = $party->id;
                        $employee->saveQuietly(); // Bypass observer to avoid recursion
                    }
                    $created++;
                    $this->line("  ✓ Created party for: {$employee->full_name}");
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $skipped++;
                $errors[] = "{$employee->full_name}: {$e->getMessage()}";
                $this->error("  ✗ Error for {$employee->full_name}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info('📊 Summary:');
        $this->line("  Created: {$created}");
        $this->line("  Linked: {$linked}");
        $this->line("  Skipped: {$skipped}");

        if (!empty($errors)) {
            $this->newLine();
            $this->error('Errors encountered:');
            foreach ($errors as $error) {
                $this->line("  - {$error}");
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->info('💡 Run without --dry-run to apply changes.');
        } else {
            $this->newLine();
            $this->info('✅ Backfill completed!');
        }

        return 0;
    }
}
