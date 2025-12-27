<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run()
    {
        $company = Company::first();
        
        Branch::create([
            'company_id' => $company->id,
            'name' => 'Merkez',
            'address' => 'Merkez Şube Adresi',
        ]);
    }
}

