<?php

namespace Database\Seeders;

use App\Models\PayrollDeductionType;
use App\Models\Company;
use Illuminate\Database\Seeder;

class PayrollDeductionTypeSeeder extends Seeder
{
    public function run()
    {
        $company = Company::first();
        
        $types = [
            'Geç kalma',
            'Devamsızlık',
            'Ceza',
        ];
        
        foreach ($types as $type) {
            PayrollDeductionType::create([
                'company_id' => $company->id,
                'name' => $type,
                'is_active' => true,
            ]);
        }
    }
}

