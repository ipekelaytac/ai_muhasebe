<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run()
    {
        Company::create([
            'name' => 'Demo Şirket',
        ]);
    }
}

