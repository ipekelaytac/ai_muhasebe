<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Development-only seeder
 * Only runs in local environment
 */
class DevOnlySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Add development/test data here
     */
    public function run(): void
    {
        if (!app()->environment('local')) {
            $this->command->warn('DevOnlySeeder skipped - not in local environment');
            return;
        }

        $this->command->info('Running development-only seeders...');

        // Add development/test data here
        // Example:
        // - Test users
        // - Sample transactions
        // - Test payroll periods
        // etc.

        $this->command->info('✓ Development seeders completed');
    }
}
