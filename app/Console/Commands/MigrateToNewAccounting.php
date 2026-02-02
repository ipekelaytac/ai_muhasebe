<?php

namespace App\Console\Commands;

use App\Domain\Accounting\Enums\DocumentType;
use App\Domain\Accounting\Models\Cashbox;
use App\Domain\Accounting\Models\Document;
use App\Domain\Accounting\Models\ExpenseCategory;
use App\Domain\Accounting\Models\Party;
use App\Domain\Accounting\Models\Payment;
use App\Domain\Accounting\Models\PaymentAllocation;
// Legacy models removed - tables dropped
// use App\Models\Customer;
// use App\Models\CustomerTransaction;
use App\Models\Employee;
// use App\Models\FinanceTransaction;
use App\Models\Overtime;
use App\Models\PayrollItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateToNewAccounting extends Command
{
    protected $signature = 'accounting:migrate 
                            {--company= : Company ID to migrate}
                            {--dry-run : Run without making changes}
                            {--step= : Run specific step only (parties|cashboxes|categories|payroll|overtimes)}';
    
    protected $description = 'Migrate existing data to new accounting model';
    
    protected bool $dryRun = false;
    protected int $companyId;
    protected array $partyMap = [];
    protected array $categoryMap = [];
    
    public function handle(): int
    {
        $this->companyId = $this->option('company');
        $this->dryRun = $this->option('dry-run');
        $step = $this->option('step');
        
        if (!$this->companyId) {
            $this->error('Company ID is required. Use --company=X');
            return 1;
        }
        
        $this->info('Starting migration for company: ' . $this->companyId);
        if ($this->dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }
        
        try {
            DB::beginTransaction();
            
            if (!$step || $step === 'cashboxes') {
                $this->migrateCashboxes();
            }
            
            if (!$step || $step === 'categories') {
                $this->migrateCategories();
            }
            
            if (!$step || $step === 'parties') {
                $this->migrateParties();
            }
            
            // Legacy steps removed - customers and finance_transactions tables dropped
            // if (!$step || $step === 'customers') {
            //     $this->migrateCustomerTransactions();
            // }
            
            if (!$step || $step === 'payroll') {
                $this->migratePayroll();
            }
            
            if (!$step || $step === 'overtimes') {
                $this->migrateOvertimes();
            }
            
            // Legacy step removed - finance_transactions table dropped
            // if (!$step || $step === 'finance') {
            //     $this->migrateFinanceTransactions();
            // }
            
            if ($this->dryRun) {
                DB::rollBack();
                $this->warn('Dry run complete - changes rolled back');
            } else {
                DB::commit();
                $this->info('Migration complete!');
            }
            
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Migration failed: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
    
    protected function migrateCashboxes(): void
    {
        $this->info('Creating default cashbox...');
        
        $exists = Cashbox::where('company_id', $this->companyId)->exists();
        if ($exists) {
            $this->warn('Cashboxes already exist, skipping');
            return;
        }
        
        if (!$this->dryRun) {
            Cashbox::create([
                'company_id' => $this->companyId,
                'code' => 'KASA-01',
                'name' => 'Ana Kasa',
                'currency' => 'TRY',
                'is_active' => true,
                'is_default' => true,
            ]);
        }
        
        $this->info('Created default cashbox');
    }
    
    protected function migrateCategories(): void
    {
        $this->info('Migrating categories...');
        
        $exists = ExpenseCategory::where('company_id', $this->companyId)->exists();
        if ($exists) {
            $this->warn('Categories already exist, loading mapping');
            
            ExpenseCategory::where('company_id', $this->companyId)
                ->get()
                ->each(function ($cat) {
                    $this->categoryMap[$cat->code] = $cat->id;
                });
            
            return;
        }
        
        if (!$this->dryRun) {
            ExpenseCategory::createDefaultsForCompany($this->companyId);
            
            ExpenseCategory::where('company_id', $this->companyId)
                ->get()
                ->each(function ($cat) {
                    $this->categoryMap[$cat->code] = $cat->id;
                });
        }
        
        $this->info('Created default categories');
    }
    
    protected function migrateParties(): void
    {
        $this->info('Migrating parties from employees...');
        
        // Legacy customer migration removed - customers table dropped
        // Customers should already be migrated to parties via separate migration
        
        // Migrate employees
        $employees = Employee::where('company_id', $this->companyId)->get();
        $this->info("Found {$employees->count()} employees to migrate");
        
        foreach ($employees as $employee) {
            $existing = Party::where('company_id', $this->companyId)
                ->where('linkable_type', Employee::class)
                ->where('linkable_id', $employee->id)
                ->first();
            
            if ($existing) {
                $this->partyMap['employee_' . $employee->id] = $existing->id;
                continue;
            }
            
            if (!$this->dryRun) {
                $party = Party::create([
                    'company_id' => $this->companyId,
                    'branch_id' => $employee->branch_id,
                    'type' => 'employee',
                    'linkable_type' => Employee::class,
                    'linkable_id' => $employee->id,
                    'code' => Party::generateCode($this->companyId, 'employee'),
                    'name' => $employee->name,
                    'phone' => $employee->phone ?? null,
                    'is_active' => $employee->status === 'active',
                ]);
                
                $this->partyMap['employee_' . $employee->id] = $party->id;
            }
        }
        
        $this->info('Migrated ' . count($this->partyMap) . ' parties');
    }
    
    /**
     * @deprecated Legacy method - customer_transactions table dropped
     * This method is disabled and kept for reference only
     */
    protected function migrateCustomerTransactions(): void
    {
        $this->warn('Legacy customer transactions migration disabled - customer_transactions table dropped');
        return;
    }
    
    protected function migratePayroll(): void
    {
        $this->info('Migrating payroll items...');
        
        $payrollItems = PayrollItem::whereHas('payrollPeriod', function ($q) {
            $q->where('company_id', $this->companyId);
        })->with(['employee', 'payrollPeriod'])->get();
        
        $this->info("Found {$payrollItems->count()} payroll items");
        
        foreach ($payrollItems as $item) {
            $partyKey = 'employee_' . $item->employee_id;
            $partyId = $this->partyMap[$partyKey] ?? null;
            
            if (!$partyId) {
                $this->warn("No party found for employee {$item->employee_id}, skipping");
                continue;
            }
            
            if (!$this->dryRun && $item->net_payable > 0) {
                // Create payroll due document
                $doc = Document::create([
                    'company_id' => $this->companyId,
                    'branch_id' => $item->employee->branch_id ?? null,
                    'document_number' => Document::generateNumber($this->companyId, null, DocumentType::PAYROLL_DUE),
                    'type' => DocumentType::PAYROLL_DUE,
                    'direction' => 'payable',
                    'party_id' => $partyId,
                    'document_date' => $item->payrollPeriod->end_date ?? $item->created_at,
                    'due_date' => $item->payrollPeriod->end_date ?? $item->created_at,
                    'total_amount' => $item->net_payable,
                    'status' => 'pending',
                    'source_type' => PayrollItem::class,
                    'source_id' => $item->id,
                    'description' => "Maaş: {$item->payrollPeriod->year}/{$item->payrollPeriod->month}",
                ]);
                
                // Check if paid
                $totalPaid = $item->payments()->sum('amount');
                if ($totalPaid > 0) {
                    // Mark as settled/partial
                    $doc->update([
                        'status' => $totalPaid >= $item->net_payable ? 'settled' : 'partial',
                    ]);
                }
            }
        }
        
        $this->info('Migrated payroll items');
    }
    
    protected function migrateOvertimes(): void
    {
        $this->info('Migrating overtimes...');
        
        $overtimes = Overtime::whereHas('employee', function ($q) {
            $q->where('company_id', $this->companyId);
        })->with('employee')->get();
        
        $this->info("Found {$overtimes->count()} overtime records");
        
        foreach ($overtimes as $overtime) {
            $partyKey = 'employee_' . $overtime->employee_id;
            $partyId = $this->partyMap[$partyKey] ?? null;
            
            if (!$partyId) {
                $this->warn("No party found for employee {$overtime->employee_id}, skipping");
                continue;
            }
            
            if (!$this->dryRun && $overtime->amount > 0) {
                Document::create([
                    'company_id' => $this->companyId,
                    'branch_id' => $overtime->employee->branch_id ?? null,
                    'document_number' => Document::generateNumber($this->companyId, null, DocumentType::OVERTIME_DUE),
                    'type' => DocumentType::OVERTIME_DUE,
                    'direction' => 'payable',
                    'party_id' => $partyId,
                    'document_date' => $overtime->date,
                    'due_date' => $overtime->date,
                    'total_amount' => $overtime->amount,
                    'status' => 'pending',
                    'source_type' => Overtime::class,
                    'source_id' => $overtime->id,
                    'description' => "Mesai: {$overtime->hours} saat x {$overtime->rate}",
                ]);
            }
        }
        
        $this->info('Migrated overtimes');
    }
    
    /**
     * @deprecated Legacy method - finance_transactions table dropped
     * This method is disabled and kept for reference only
     */
    protected function migrateFinanceTransactions(): void
    {
        $this->warn('Legacy finance transactions migration disabled - finance_transactions table dropped');
        return;
    }
}
