<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeContract;
use Illuminate\Database\Seeder;

class EmployeeContractSeeder extends Seeder
{
    public function run()
    {
        $employees = Employee::all();
        
        foreach ($employees as $employee) {
            EmployeeContract::create([
                'employee_id' => $employee->id,
                'effective_from' => $employee->start_date ?? '2024-01-01',
                'effective_to' => null,
                'monthly_net_salary' => rand(15000, 30000),
                'pay_day_1' => 5,
                'pay_amount_1' => rand(5000, 10000),
                'pay_day_2' => 20,
                'pay_amount_2' => rand(5000, 10000),
                'meal_allowance' => 1500,
            ]);
        }
    }
}

