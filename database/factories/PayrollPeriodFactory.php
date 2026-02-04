<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Branch;
use App\Models\PayrollPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

class PayrollPeriodFactory extends Factory
{
    protected $model = PayrollPeriod::class;

    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween('-6 months', 'now');
        return [
            'company_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'year' => (int) $date->format('Y'),
            'month' => (int) $date->format('n'),
            'status' => true,
        ];
    }
}
