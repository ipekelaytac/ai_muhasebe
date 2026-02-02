<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Production initial setup
        $this->call([
            InitialSetupSeeder::class,
        ]);

        // Development-only seeders (only run in local environment)
        if (app()->environment('local')) {
            $this->call([
                DevOnlySeeder::class,
            ]);
        }

        // Optional: Other seeders if needed
        // $this->call([
        //     PayrollDeductionTypeSeeder::class,
        //     FinanceCategorySeeder::class,
        //     AccountingSeeder::class,
        // ]);
    }
}
