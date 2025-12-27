<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        $company = Company::first();
        $branch = Branch::first();

        User::create([
            'name' => 'Admin User',
            'email' => 'admin@muhasebe.test',
            'password' => Hash::make('123456'),
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'is_admin' => true,
        ]);
    }
}

