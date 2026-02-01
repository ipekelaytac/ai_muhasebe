<?php

namespace Database\Factories;

use App\Domain\Accounting\Models\BankAccount;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'branch_id' => null,
            'code' => $this->faker->unique()->bothify('BANKA-??'),
            'name' => $this->faker->words(2, true) . ' Banka',
            'bank_name' => $this->faker->company() . ' Bankası',
            'branch_name' => $this->faker->optional()->city() . ' Şubesi',
            'account_number' => $this->faker->optional()->numerify('##########'),
            'iban' => $this->faker->optional()->iban(),
            'currency' => 'TRY',
            'account_type' => 'checking',
            'description' => null,
            'is_active' => true,
            'is_default' => false,
            'opening_balance' => 0,
            'opening_balance_date' => null,
        ];
    }
}
