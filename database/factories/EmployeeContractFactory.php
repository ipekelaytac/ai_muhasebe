<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeContractFactory extends Factory
{
    protected $model = \App\Models\EmployeeContract::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'effective_from' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'effective_to' => null,
            'monthly_net_salary' => $this->faker->randomFloat(2, 10000, 50000),
            'pay_day_1' => 5,
            'pay_amount_1' => $this->faker->randomFloat(2, 3000, 15000),
            'pay_day_2' => 20,
            'pay_amount_2' => $this->faker->randomFloat(2, 3000, 15000),
            'meal_allowance' => $this->faker->randomFloat(2, 0, 500),
            'notes' => null,
        ];
    }
}
