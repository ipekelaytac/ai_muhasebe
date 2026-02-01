<?php

namespace Database\Factories;

use App\Domain\Accounting\Models\Cashbox;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class CashboxFactory extends Factory
{
    protected $model = Cashbox::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'branch_id' => null,
            'code' => $this->faker->unique()->bothify('KASA-??'),
            'name' => $this->faker->words(2, true) . ' Kasa',
            'currency' => 'TRY',
            'description' => null,
            'is_active' => true,
            'is_default' => false,
            'opening_balance' => 0,
            'opening_balance_date' => null,
        ];
    }
}
