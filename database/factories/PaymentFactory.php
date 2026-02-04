<?php

namespace Database\Factories;

use App\Domain\Accounting\Enums\PaymentType;
use App\Domain\Accounting\Models\Cashbox;
use App\Domain\Accounting\Models\Payment;
use App\Domain\Accounting\Models\Party;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween('-1 year', 'now');
        $amount = $this->faker->randomFloat(2, 100, 5000);
        return [
            'company_id' => Company::factory(),
            'payment_number' => 'PAY-' . \Illuminate\Support\Str::random(8),
            'branch_id' => null,
            'type' => PaymentType::CASH_IN,
            'direction' => 'in',
            'party_id' => Party::factory(),
            'cashbox_id' => Cashbox::factory(),
            'payment_date' => $date,
            'amount' => $amount,
            'net_amount' => $amount,
            'status' => 'confirmed',
            'period_year' => (int) $date->format('Y'),
            'period_month' => (int) $date->format('n'),
        ];
    }
}
