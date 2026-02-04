<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'full_name' => $this->faker->name(),
            'phone' => $this->faker->optional()->phoneNumber(),
            'start_date' => $this->faker->optional()->dateTimeBetween('-2 years', 'now'),
            'end_date' => null,
            'status' => true,
        ];
    }
}
