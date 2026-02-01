<?php

namespace Database\Factories;

use App\Domain\Accounting\Models\Party;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class PartyFactory extends Factory
{
    protected $model = Party::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['customer', 'supplier', 'employee', 'other']);
        
        return [
            'company_id' => Company::factory(),
            'branch_id' => null,
            'type' => $type,
            'code' => strtoupper($this->faker->unique()->bothify('???#####')),
            'name' => $this->faker->company(),
            'tax_number' => $this->faker->optional()->numerify('##########'),
            'tax_office' => $this->faker->optional()->city(),
            'phone' => $this->faker->optional()->phoneNumber(),
            'email' => $this->faker->optional()->safeEmail(),
            'address' => $this->faker->optional()->address(),
            'city' => $this->faker->optional()->city(),
            'country' => 'Türkiye',
            'payment_terms_days' => 0,
            'credit_limit' => null,
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function customer(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'customer',
        ]);
    }

    public function supplier(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'supplier',
        ]);
    }

    public function employee(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'employee',
        ]);
    }
}
