<?php

namespace Database\Seeders;

use App\Domain\Accounting\Enums\PartyType;
use App\Domain\Accounting\Models\BankAccount;
use App\Domain\Accounting\Models\Cashbox;
use App\Domain\Accounting\Models\Party;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\PayrollDeductionType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InitialSetupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates initial production data: Company, Branch, User, BankAccount, Cashbox, Employees, Contracts
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Starting initial setup seeding...');

            // 1. Company
            $company = Company::firstOrCreate(
                ['name' => 'Ana Şirket'],
                ['created_at' => '2026-02-02 12:32:54', 'updated_at' => '2026-02-02 12:32:54']
            );
            $this->command->info("✓ Company created/updated: {$company->name} (ID: {$company->id})");

            // 2. Branch
            $branch = Branch::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'name' => 'Ana Şube',
                ],
                [
                    'address' => 'Fevzi Çakmak Mahallesi 1120 Sokak 25/A Esenler/İstanbul',
                    'created_at' => '2026-02-02 12:32:54',
                    'updated_at' => '2026-02-02 12:32:54',
                ]
            );
            $this->command->info("✓ Branch created/updated: {$branch->name} (ID: {$branch->id})");

            // 3. User (Admin)
            $user = User::updateOrCreate(
                ['email' => 'admin@muhasebe.com'],
                [
                    'name' => 'Admin User',
                    'password' => '$2y$10$bgEJ7LETA470bCmJ1vqIjeyL5MTu3D55WS8ARozwHrlBoEro22F92',
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'is_admin' => 1,
                ]
            );
            $this->command->info("✓ User created/updated: {$user->email} (ID: {$user->id})");

            // 4. Bank Account
            $bankAccount = BankAccount::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'code' => 'AKBANK',
                ],
                [
                    'name' => 'Akbank',
                    'bank_name' => 'Akbank',
                    'branch_name' => 'Esenler',
                    'currency' => 'TRY',
                    'account_type' => 'checking',
                    'is_active' => true,
                    'is_default' => true,
                    'opening_balance' => 0,
                    'opening_balance_date' => '2026-01-01',
                    'created_by' => $user->id,
                ]
            );
            $this->command->info("✓ Bank Account created/updated: {$bankAccount->name} (ID: {$bankAccount->id})");

            // 5. Cashbox
            $cashbox = Cashbox::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'code' => 'ANAKASA',
                ],
                [
                    'name' => 'Ana Kasa',
                    'currency' => 'TRY',
                    'is_active' => true,
                    'is_default' => true,
                    'opening_balance' => 200000.00,
                    'opening_balance_date' => '2026-01-01',
                    'created_by' => $user->id,
                ]
            );
            $this->command->info("✓ Cashbox created/updated: {$cashbox->name} (ID: {$cashbox->id})");

            // 6. Employees with Parties
            $employeesData = [
                ['full_name' => 'Aytekin İpekel', 'phone' => '05323768958', 'start_date' => '2015-08-02', 'party_id' => 1],
                ['full_name' => 'Yunus Mürtezan', 'phone' => null, 'start_date' => '2025-06-16', 'party_id' => 2],
                ['full_name' => 'Mücella İpekel', 'phone' => null, 'start_date' => '2021-06-04', 'party_id' => 3],
                ['full_name' => 'Şükrü Karadeniz', 'phone' => null, 'start_date' => '2025-05-14', 'party_id' => 4],
                ['full_name' => 'Ertuğrul Yersiz', 'phone' => null, 'start_date' => '2024-01-03', 'party_id' => 5],
                ['full_name' => 'Göksel Cenekli', 'phone' => null, 'start_date' => '2016-08-01', 'party_id' => 6],
                ['full_name' => 'Recep Pehlivan', 'phone' => null, 'start_date' => '2024-02-20', 'party_id' => 7],
                ['full_name' => 'Neriman Yersiz', 'phone' => null, 'start_date' => '2024-01-01', 'party_id' => 8],
                ['full_name' => 'Erkan Demirci', 'phone' => null, 'start_date' => '2026-01-14', 'party_id' => 9],
                ['full_name' => 'Aytaç İpekel', 'phone' => null, 'start_date' => '2026-01-01', 'party_id' => 10],
            ];

            $employees = [];
            foreach ($employeesData as $empData) {
                // Create Party first (without linkable_id, will be set after employee creation)
                $party = Party::firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'branch_id' => $branch->id,
                        'type' => PartyType::EMPLOYEE,
                        'name' => $empData['full_name'],
                    ],
                    [
                        'code' => Party::generateCode($company->id, PartyType::EMPLOYEE),
                        'phone' => $empData['phone'],
                        'is_active' => true,
                        'linkable_type' => Employee::class,
                        'linkable_id' => null, // Will be set after employee creation
                    ]
                );

                // Ensure code exists (in case party was created without code)
                if (!$party->code) {
                    $party->update(['code' => Party::generateCode($company->id, PartyType::EMPLOYEE)]);
                }

                // Create Employee
                $employee = Employee::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'branch_id' => $branch->id,
                        'full_name' => $empData['full_name'],
                    ],
                    [
                        'party_id' => $party->id,
                        'phone' => $empData['phone'],
                        'start_date' => $empData['start_date'],
                        'status' => 1,
                    ]
                );

                // Update party linkable_id if needed
                if (!$party->linkable_id || $party->linkable_id !== $employee->id) {
                    $party->update(['linkable_id' => $employee->id]);
                }

                $employees[$empData['full_name']] = $employee;
                $this->command->info("✓ Employee created/updated: {$employee->full_name} (ID: {$employee->id}, Party ID: {$party->id})");
            }

            // 7. Employee Contracts
            $contractsData = [
                'Aytekin İpekel' => [
                    'effective_from' => '2026-01-01',
                    'effective_to' => '2027-01-01',
                    'monthly_net_salary' => 100000,
                    'pay_day_1' => 5,
                    'pay_amount_1' => 50000,
                    'pay_day_2' => 20,
                    'pay_amount_2' => 50000,
                    'meal_allowance' => 2640,
                ],
                'Erkan Demirci' => [
                    'effective_from' => '2026-01-14',
                    'effective_to' => '2027-01-01',
                    'monthly_net_salary' => 45000,
                    'pay_day_1' => 5,
                    'pay_amount_1' => 22500,
                    'pay_day_2' => 20,
                    'pay_amount_2' => 22500,
                    'meal_allowance' => 2640,
                ],
                'Neriman Yersiz' => [
                    'effective_from' => '2026-01-01',
                    'effective_to' => '2027-01-01',
                    'monthly_net_salary' => 42000,
                    'pay_day_1' => 5,
                    'pay_amount_1' => 21000,
                    'pay_day_2' => 20,
                    'pay_amount_2' => 21000,
                    'meal_allowance' => 3000,
                ],
                'Recep Pehlivan' => [
                    'effective_from' => '2026-01-01',
                    'effective_to' => '2027-01-01',
                    'monthly_net_salary' => 47000,
                    'pay_day_1' => 5,
                    'pay_amount_1' => 23500,
                    'pay_day_2' => 20,
                    'pay_amount_2' => 23500,
                    'meal_allowance' => 2640,
                ],
                'Göksel Cenekli' => [
                    'effective_from' => '2026-01-01',
                    'effective_to' => '2027-01-01',
                    'monthly_net_salary' => 50000,
                    'pay_day_1' => 5,
                    'pay_amount_1' => 25000,
                    'pay_day_2' => 20,
                    'pay_amount_2' => 25000,
                    'meal_allowance' => 2640,
                ],
                'Şükrü Karadeniz' => [
                    'effective_from' => '2026-01-01',
                    'effective_to' => '2027-01-01',
                    'monthly_net_salary' => 52000,
                    'pay_day_1' => 5,
                    'pay_amount_1' => 28075.50,
                    'pay_day_2' => 20,
                    'pay_amount_2' => 23924.50,
                    'meal_allowance' => 2640,
                ],
                'Ertuğrul Yersiz' => [
                    'effective_from' => '2026-01-01',
                    'effective_to' => '2027-01-01',
                    'monthly_net_salary' => 52000,
                    'pay_day_1' => 5,
                    'pay_amount_1' => 28075.50,
                    'pay_day_2' => 20,
                    'pay_amount_2' => 23924.50,
                    'meal_allowance' => 3000,
                ],
                'Yunus Mürtezan' => [
                    'effective_from' => '2026-01-01',
                    'effective_to' => '2027-01-01',
                    'monthly_net_salary' => 65000,
                    'pay_day_1' => 5,
                    'pay_amount_1' => 32500,
                    'pay_day_2' => 20,
                    'pay_amount_2' => 32500,
                    'meal_allowance' => 2640,
                ],
                'Mücella İpekel' => [
                    'effective_from' => '2026-01-01',
                    'effective_to' => '2027-01-01',
                    'monthly_net_salary' => 52000,
                    'pay_day_1' => 5,
                    'pay_amount_1' => 28075.50,
                    'pay_day_2' => 20,
                    'pay_amount_2' => 23924.50,
                    'meal_allowance' => 2640,
                ],
            ];

            foreach ($contractsData as $employeeName => $contractData) {
                if (!isset($employees[$employeeName])) {
                    $this->command->warn("⚠ Employee not found for contract: {$employeeName}");
                    continue;
                }

                $employee = $employees[$employeeName];

                $contract = EmployeeContract::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'effective_from' => $contractData['effective_from'],
                    ],
                    [
                        'effective_to' => $contractData['effective_to'],
                        'monthly_net_salary' => $contractData['monthly_net_salary'],
                        'pay_day_1' => $contractData['pay_day_1'],
                        'pay_amount_1' => $contractData['pay_amount_1'],
                        'pay_day_2' => $contractData['pay_day_2'],
                        'pay_amount_2' => $contractData['pay_amount_2'],
                        'meal_allowance' => $contractData['meal_allowance'],
                    ]
                );

                $this->command->info("✓ Contract created/updated for {$employeeName} (ID: {$contract->id})");
            }

            // 8. Payroll Deduction Types
            $deductionTypes = [
                'Geç kalma',
                'Devamsızlık',
                'Ceza',
                'SGK Kesintisi',
                'Vergi Kesintisi',
                'Diğer',
            ];

            foreach ($deductionTypes as $typeName) {
                $deductionType = PayrollDeductionType::firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'name' => $typeName,
                    ],
                    [
                        'is_active' => true,
                    ]
                );
                $this->command->info("✓ Deduction Type created/updated: {$typeName} (ID: {$deductionType->id})");
            }

            $this->command->info('');
            $this->command->info('✅ Initial setup seeding completed successfully!');
            $this->command->info("   - Company: {$company->name}");
            $this->command->info("   - Branch: {$branch->name}");
            $this->command->info("   - User: {$user->email}");
            $this->command->info("   - Bank Account: {$bankAccount->name}");
            $this->command->info("   - Cashbox: {$cashbox->name}");
            $this->command->info("   - Employees: " . count($employees));
            $this->command->info("   - Contracts: " . count($contractsData));
            $this->command->info("   - Deduction Types: " . count($deductionTypes));
        });
    }
}
