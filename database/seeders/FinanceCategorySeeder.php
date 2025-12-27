<?php

namespace Database\Seeders;

use App\Models\FinanceCategory;
use App\Models\Company;
use Illuminate\Database\Seeder;

class FinanceCategorySeeder extends Seeder
{
    public function run()
    {
        $company = Company::first();
        
        $expenseCategories = [
            'Kira',
            'Elektrik',
            'İnternet',
            'Muhasebe',
            'İSG',
            'Maaş Ödemesi',
            'Avans',
        ];
        
        foreach ($expenseCategories as $category) {
            FinanceCategory::create([
                'company_id' => $company->id,
                'type' => 'expense',
                'name' => $category,
                'is_active' => true,
            ]);
        }
        
        // Add some income categories
        FinanceCategory::create([
            'company_id' => $company->id,
            'type' => 'income',
            'name' => 'Satış Geliri',
            'is_active' => true,
        ]);
    }
}

