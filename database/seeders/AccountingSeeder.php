<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds base data for the accounting system
 */
class AccountingSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPermissions();
        $this->seedRoles();
        $this->seedExpenseCategories();
        $this->seedBaseData();
    }
    
    private function seedBaseData(): void
    {
        // This seeds cashboxes, bank accounts, and periods per company/branch
        // Only runs if companies exist
        if (!\App\Models\Company::exists()) {
            return;
        }
        
        $companies = \App\Models\Company::with('branches')->get();
        foreach ($companies as $company) {
            $branches = $company->branches;
            if ($branches->isEmpty()) {
                // Create default branch if none exists
                $branch = \App\Models\Branch::create([
                    'company_id' => $company->id,
                    'name' => 'Ana Şube',
                ]);
                $branches = collect([$branch]);
            }
            
            foreach ($branches as $branch) {
                // Default cashbox
                \App\Domain\Accounting\Models\Cashbox::firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'branch_id' => $branch->id,
                        'code' => 'CASH01',
                    ],
                    [
                        'name' => 'Ana Kasa',
                        'description' => 'Default cashbox',
                        'is_active' => true,
                    ]
                );

                // Create accounting periods for current year
                $currentYear = now()->year;
                for ($month = 1; $month <= 12; $month++) {
                    \App\Domain\Accounting\Models\AccountingPeriod::firstOrCreate(
                        [
                            'company_id' => $company->id,
                            'year' => $currentYear,
                            'month' => $month,
                        ],
                        [
                            'start_date' => now()->setYear($currentYear)->setMonth($month)->startOfMonth(),
                            'end_date' => now()->setYear($currentYear)->setMonth($month)->endOfMonth(),
                            'status' => 'open',
                        ]
                    );
                }
            }
        }
    }
    
    private function seedPermissions(): void
    {
        $permissions = [
            // Documents
            ['name' => 'documents.view', 'description' => 'Belgeleri görüntüleme'],
            ['name' => 'documents.create', 'description' => 'Belge oluşturma'],
            ['name' => 'documents.update', 'description' => 'Belge güncelleme'],
            ['name' => 'documents.delete', 'description' => 'Belge silme'],
            ['name' => 'documents.reverse', 'description' => 'Belge iptali/ters kayıt'],
            
            // Payments
            ['name' => 'payments.view', 'description' => 'Ödemeleri görüntüleme'],
            ['name' => 'payments.create', 'description' => 'Ödeme oluşturma'],
            ['name' => 'payments.update', 'description' => 'Ödeme güncelleme'],
            ['name' => 'payments.delete', 'description' => 'Ödeme silme'],
            ['name' => 'payments.reverse', 'description' => 'Ödeme iptali'],
            
            // Allocations
            ['name' => 'allocations.view', 'description' => 'Kapamaları görüntüleme'],
            ['name' => 'allocations.create', 'description' => 'Kapama oluşturma'],
            ['name' => 'allocations.delete', 'description' => 'Kapama silme'],
            
            // Parties
            ['name' => 'parties.view', 'description' => 'Cari hesapları görüntüleme'],
            ['name' => 'parties.create', 'description' => 'Cari hesap oluşturma'],
            ['name' => 'parties.update', 'description' => 'Cari hesap güncelleme'],
            ['name' => 'parties.delete', 'description' => 'Cari hesap silme'],
            
            // Cheques
            ['name' => 'cheques.view', 'description' => 'Çekleri görüntüleme'],
            ['name' => 'cheques.create', 'description' => 'Çek oluşturma'],
            ['name' => 'cheques.update', 'description' => 'Çek güncelleme'],
            ['name' => 'cheques.process', 'description' => 'Çek işlemleri (ciro, tahsil, vb.)'],
            
            // Cashboxes & Bank Accounts
            ['name' => 'cashboxes.view', 'description' => 'Kasaları görüntüleme'],
            ['name' => 'cashboxes.manage', 'description' => 'Kasa yönetimi'],
            ['name' => 'bank_accounts.view', 'description' => 'Banka hesaplarını görüntüleme'],
            ['name' => 'bank_accounts.manage', 'description' => 'Banka hesabı yönetimi'],
            
            // Reports
            ['name' => 'reports.cash_balance', 'description' => 'Kasa/Banka bakiye raporu'],
            ['name' => 'reports.aging', 'description' => 'Yaşlandırma raporu'],
            ['name' => 'reports.party_statement', 'description' => 'Cari ekstre'],
            ['name' => 'reports.cashflow', 'description' => 'Nakit akış tahmini'],
            ['name' => 'reports.pnl', 'description' => 'Gelir/Gider raporu'],
            ['name' => 'reports.all', 'description' => 'Tüm raporlar'],
            
            // Period Management
            ['name' => 'periods.view', 'description' => 'Dönemleri görüntüleme'],
            ['name' => 'periods.lock', 'description' => 'Dönem kilitleme'],
            ['name' => 'periods.unlock', 'description' => 'Dönem kilit açma'],
            
            // Categories
            ['name' => 'categories.view', 'description' => 'Kategorileri görüntüleme'],
            ['name' => 'categories.manage', 'description' => 'Kategori yönetimi'],
            
            // Admin
            ['name' => 'admin.users', 'description' => 'Kullanıcı yönetimi'],
            ['name' => 'admin.settings', 'description' => 'Sistem ayarları'],
            ['name' => 'admin.audit_logs', 'description' => 'Denetim kayıtları'],
        ];
        
        $now = now();
        foreach ($permissions as &$perm) {
            $perm['guard_name'] = 'web';
            $perm['created_at'] = $now;
            $perm['updated_at'] = $now;
        }
        
        DB::table('permissions')->insertOrIgnore($permissions);
    }
    
    private function seedRoles(): void
    {
        $now = now();
        
        $roles = [
            ['name' => 'super_admin', 'description' => 'Tam yetkili yönetici', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'admin', 'description' => 'Yönetici', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'accountant', 'description' => 'Muhasebeci', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'cashier', 'description' => 'Kasiyer', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'viewer', 'description' => 'Sadece görüntüleme', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
        ];
        
        DB::table('roles')->insertOrIgnore($roles);
        
        // Assign all permissions to super_admin
        $superAdminRole = DB::table('roles')->where('name', 'super_admin')->first();
        if ($superAdminRole) {
            $allPermissions = DB::table('permissions')->pluck('id');
            $rolePermissions = $allPermissions->map(fn($permId) => [
                'role_id' => $superAdminRole->id,
                'permission_id' => $permId,
            ])->toArray();
            DB::table('role_has_permissions')->insertOrIgnore($rolePermissions);
        }
        
        // Admin permissions (everything except unlock and audit logs)
        $adminRole = DB::table('roles')->where('name', 'admin')->first();
        if ($adminRole) {
            $adminPerms = DB::table('permissions')
                ->whereNotIn('name', ['periods.unlock', 'admin.audit_logs'])
                ->pluck('id');
            $rolePermissions = $adminPerms->map(fn($permId) => [
                'role_id' => $adminRole->id,
                'permission_id' => $permId,
            ])->toArray();
            DB::table('role_has_permissions')->insertOrIgnore($rolePermissions);
        }
        
        // Accountant permissions
        $accountantRole = DB::table('roles')->where('name', 'accountant')->first();
        if ($accountantRole) {
            $accountantPerms = DB::table('permissions')
                ->whereIn('name', [
                    'documents.view', 'documents.create', 'documents.update',
                    'payments.view', 'payments.create', 'payments.update',
                    'allocations.view', 'allocations.create', 'allocations.delete',
                    'parties.view', 'parties.create', 'parties.update',
                    'cheques.view', 'cheques.create', 'cheques.update', 'cheques.process',
                    'cashboxes.view', 'bank_accounts.view',
                    'reports.cash_balance', 'reports.aging', 'reports.party_statement', 'reports.cashflow', 'reports.pnl',
                    'periods.view', 'categories.view',
                ])
                ->pluck('id');
            $rolePermissions = $accountantPerms->map(fn($permId) => [
                'role_id' => $accountantRole->id,
                'permission_id' => $permId,
            ])->toArray();
            DB::table('role_has_permissions')->insertOrIgnore($rolePermissions);
        }
        
        // Cashier permissions
        $cashierRole = DB::table('roles')->where('name', 'cashier')->first();
        if ($cashierRole) {
            $cashierPerms = DB::table('permissions')
                ->whereIn('name', [
                    'payments.view', 'payments.create',
                    'allocations.view', 'allocations.create',
                    'parties.view',
                    'cheques.view',
                    'cashboxes.view',
                    'reports.cash_balance',
                ])
                ->pluck('id');
            $rolePermissions = $cashierPerms->map(fn($permId) => [
                'role_id' => $cashierRole->id,
                'permission_id' => $permId,
            ])->toArray();
            DB::table('role_has_permissions')->insertOrIgnore($rolePermissions);
        }
        
        // Viewer permissions
        $viewerRole = DB::table('roles')->where('name', 'viewer')->first();
        if ($viewerRole) {
            $viewerPerms = DB::table('permissions')
                ->where('name', 'like', '%.view')
                ->orWhere('name', 'like', 'reports.%')
                ->pluck('id');
            $rolePermissions = $viewerPerms->map(fn($permId) => [
                'role_id' => $viewerRole->id,
                'permission_id' => $permId,
            ])->toArray();
            DB::table('role_has_permissions')->insertOrIgnore($rolePermissions);
        }
    }
    
    private function seedExpenseCategories(): void
    {
        // These are company-agnostic system categories
        // Real categories should be created per company
        
        // For now, we'll create them when a company is created
        // This is just a placeholder for default category structure
    }
}
