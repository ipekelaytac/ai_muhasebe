<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

class PayrollItemFactory extends Factory
{
    protected $model = PayrollItem::class;

    public function definition(): array
    {
        $baseSalary = $this->faker->randomFloat(2, 10000, 30000);
        $mealAllowance = $this->faker->randomFloat(2, 0, 500);
        $overtimeTotal = $this->faker->randomFloat(2, 0, 1000);
        $bonusTotal = 0;
        $deductionTotal = 0;
        $advancesDeductedTotal = 0;
        $netPayable = $baseSalary + $mealAllowance + $overtimeTotal - $deductionTotal - $advancesDeductedTotal;

        return [
            'payroll_period_id' => PayrollPeriod::factory(),
            'employee_id' => Employee::factory(),
            'base_net_salary' => $baseSalary,
            'meal_allowance' => $mealAllowance,
            'overtime_total' => $overtimeTotal,
            'bonus_total' => $bonusTotal,
            'deduction_total' => $deductionTotal,
            'advances_deducted_total' => $advancesDeductedTotal,
            'net_payable' => $netPayable,
        ];
    }
}
