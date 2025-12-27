<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run()
    {
        $company = Company::first();
        $branch = Branch::first();
        
        $employees = [
            [
                'full_name' => 'Ahmet Yılmaz',
                'phone' => '0532 123 4567',
                'start_date' => '2024-01-01',
                'status' => 1,
            ],
            [
                'full_name' => 'Ayşe Demir',
                'phone' => '0533 234 5678',
                'start_date' => '2024-02-15',
                'status' => 1,
            ],
            [
                'full_name' => 'Mehmet Kaya',
                'phone' => '0534 345 6789',
                'start_date' => '2024-03-01',
                'status' => 1,
            ],
        ];
        
        foreach ($employees as $emp) {
            Employee::create(array_merge($emp, [
                'company_id' => $company->id,
                'branch_id' => $branch->id,
            ]));
        }
    }
}

