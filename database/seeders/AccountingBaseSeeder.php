<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Cashbox;
use App\Models\BankAccount;
use App\Models\AccountingPeriod;

class AccountingBaseSeeder extends Seeder
{
    public function run()
    {
        // Create default cashbox for each company/branch
        $companies = Company::all();
        foreach ($companies as $company) {
            foreach ($company->branches as $branch) {
                // Default cashbox
                Cashbox::firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'branch_id' => $branch->id,
                        'code' => 'CASH01',
                    ],
                    [
                        'name' => 'Ana Kasa',
                        'description' => 'Default cashbox',
                        'is_active' => true,
                    ]
                );

                // Create accounting periods for current year
                $currentYear = now()->year;
                for ($month = 1; $month <= 12; $month++) {
                    AccountingPeriod::firstOrCreate(
                        [
                            'company_id' => $company->id,
                            'branch_id' => $branch->id,
                            'year' => $currentYear,
                            'month' => $month,
                        ],
                        [
                            'start_date' => now()->setYear($currentYear)->setMonth($month)->startOfMonth(),
                            'end_date' => now()->setYear($currentYear)->setMonth($month)->endOfMonth(),
                            'status' => 'open',
                        ]
                    );
                }
            }
        }

        $this->command->info('Accounting base data seeded successfully!');
    }
}
