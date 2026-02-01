<?php

namespace App\Console\Commands;

use App\Domain\Accounting\Enums\DocumentType;
use App\Domain\Accounting\Models\Cashbox;
use App\Domain\Accounting\Models\Document;
use App\Domain\Accounting\Models\ExpenseCategory;
use App\Domain\Accounting\Models\Party;
use App\Domain\Accounting\Models\Payment;
use App\Domain\Accounting\Models\PaymentAllocation;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\Employee;
use App\Models\FinanceTransaction;
use App\Models\Overtime;
use App\Models\PayrollItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateToNewAccounting extends Command
{
    protected $signature = 'accounting:migrate 
                            {--company= : Company ID to migrate}
                            {--dry-run : Run without making changes}
                            {--step= : Run specific step only (parties|cashboxes|categories|customers|payroll|overtimes|finance)}';
    
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
            
            if (!$step || $step === 'customers') {
                $this->migrateCustomerTransactions();
            }
            
            if (!$step || $step === 'payroll') {
                $this->migratePayroll();
            }
            
            if (!$step || $step === 'overtimes') {
                $this->migrateOvertimes();
            }
            
            if (!$step || $step === 'finance') {
                $this->migrateFinanceTransactions();
            }
            
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
        $this->info('Migrating parties from customers and employees...');
        
        // Migrate customers
        $customers = Customer::where('company_id', $this->companyId)->get();
        $this->info("Found {$customers->count()} customers to migrate");
        
        foreach ($customers as $customer) {
            $existing = Party::where('company_id', $this->companyId)
                ->where('linkable_type', Customer::class)
                ->where('linkable_id', $customer->id)
                ->first();
            
            if ($existing) {
                $this->partyMap['customer_' . $customer->id] = $existing->id;
                continue;
            }
            
            $type = $customer->type === 'supplier' ? 'supplier' : 'customer';
            
            if (!$this->dryRun) {
                $party = Party::create([
                    'company_id' => $this->companyId,
                    'type' => $type,
                    'linkable_type' => Customer::class,
                    'linkable_id' => $customer->id,
                    'code' => Party::generateCode($this->companyId, $type),
                    'name' => $customer->name,
                    'tax_number' => $customer->tax_number ?? null,
                    'tax_office' => $customer->tax_office ?? null,
                    'phone' => $customer->phone ?? null,
                    'email' => $customer->email ?? null,
                    'address' => $customer->address ?? null,
                    'is_active' => true,
                ]);
                
                $this->partyMap['customer_' . $customer->id] = $party->id;
            }
        }
        
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
    
    protected function migrateCustomerTransactions(): void
    {
        $this->info('Migrating customer transactions...');
        
        $transactions = CustomerTransaction::whereHas('customer', function ($q) {
            $q->where('company_id', $this->companyId);
        })->with('customer')->get();
        
        $this->info("Found {$transactions->count()} customer transactions");
        
        $cashbox = Cashbox::where('company_id', $this->companyId)->first();
        
        foreach ($transactions as $tx) {
            $partyKey = 'customer_' . $tx->customer_id;
            $partyId = $this->partyMap[$partyKey] ?? null;
            
            if (!$partyId) {
                $this->warn("No party found for customer {$tx->customer_id}, skipping");
                continue;
            }
            
            // Determine if this is a document or payment
            // In the old system, income = we receive money (payment in) or they owe us (receivable)
            // expense = we pay money (payment out) or we owe them (payable)
            
            // For simplicity, treat all as documents with immediate payment
            $isReceivable = $tx->type === 'income';
            
            if (!$this->dryRun) {
                // Create document
                $doc = Document::create([
                    'company_id' => $this->companyId,
                    'branch_id' => $tx->customer->branch_id ?? null,
                    'document_number' => Document::generateNumber(
                        $this->companyId,
                        null,
                        $isReceivable ? DocumentType::CUSTOMER_INVOICE : DocumentType::SUPPLIER_INVOICE
                    ),
                    'type' => $isReceivable ? DocumentType::CUSTOMER_INVOICE : DocumentType::SUPPLIER_INVOICE,
                    'direction' => $isReceivable ? 'receivable' : 'payable',
                    'party_id' => $partyId,
                    'document_date' => $tx->date ?? $tx->created_at,
                    'total_amount' => abs($tx->amount),
                    'status' => 'settled', // Assume settled since it was recorded
                    'source_type' => CustomerTransaction::class,
                    'source_id' => $tx->id,
                    'description' => $tx->description ?? 'Migrated from customer transactions',
                ]);
                
                // Create payment
                $payment = Payment::create([
                    'company_id' => $this->companyId,
                    'branch_id' => $tx->customer->branch_id ?? null,
                    'payment_number' => Payment::generateNumber(
                        $this->companyId,
                        null,
                        $isReceivable ? 'cash_in' : 'cash_out'
                    ),
                    'type' => $isReceivable ? 'cash_in' : 'cash_out',
                    'direction' => $isReceivable ? 'in' : 'out',
                    'party_id' => $partyId,
                    'cashbox_id' => $cashbox?->id,
                    'payment_date' => $tx->date ?? $tx->created_at,
                    'amount' => abs($tx->amount),
                    'net_amount' => abs($tx->amount),
                    'status' => 'confirmed',
                    'description' => $tx->description ?? 'Migrated from customer transactions',
                ]);
                
                // Create allocation
                PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'document_id' => $doc->id,
                    'amount' => abs($tx->amount),
                    'allocation_date' => $tx->date ?? $tx->created_at,
                    'status' => 'active',
                ]);
            }
        }
        
        $this->info('Migrated customer transactions');
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
    
    protected function migrateFinanceTransactions(): void
    {
        $this->info('Migrating finance transactions...');
        
        $transactions = FinanceTransaction::where('company_id', $this->companyId)
            ->with('category')
            ->get();
        
        $this->info("Found {$transactions->count()} finance transactions");
        
        $cashbox = Cashbox::where('company_id', $this->companyId)->first();
        
        foreach ($transactions as $tx) {
            // Skip if already linked to a party-based transaction
            if ($tx->related_table && in_array($tx->related_table, ['customer_transactions', 'payroll_payments'])) {
                continue;
            }
            
            // Create as expense/income document + payment
            $isIncome = $tx->type === 'income';
            
            if (!$this->dryRun) {
                // Find or create "other" party for non-party transactions
                $otherParty = Party::firstOrCreate(
                    [
                        'company_id' => $this->companyId,
                        'type' => 'other',
                        'code' => 'DIG-GENEL',
                    ],
                    [
                        'name' => 'Genel İşlemler',
                        'is_active' => true,
                    ]
                );
                
                $doc = Document::create([
                    'company_id' => $this->companyId,
                    'branch_id' => $tx->branch_id,
                    'document_number' => Document::generateNumber(
                        $this->companyId,
                        $tx->branch_id,
                        $isIncome ? DocumentType::INCOME_DUE : DocumentType::EXPENSE_DUE
                    ),
                    'type' => $isIncome ? DocumentType::INCOME_DUE : DocumentType::EXPENSE_DUE,
                    'direction' => $isIncome ? 'receivable' : 'payable',
                    'party_id' => $otherParty->id,
                    'document_date' => $tx->date ?? $tx->created_at,
                    'total_amount' => abs($tx->amount),
                    'category_id' => $tx->category_id ? ($this->categoryMap[$tx->category?->code] ?? null) : null,
                    'status' => 'settled',
                    'source_type' => FinanceTransaction::class,
                    'source_id' => $tx->id,
                    'description' => $tx->description,
                ]);
                
                $payment = Payment::create([
                    'company_id' => $this->companyId,
                    'branch_id' => $tx->branch_id,
                    'payment_number' => Payment::generateNumber(
                        $this->companyId,
                        $tx->branch_id,
                        $isIncome ? 'cash_in' : 'cash_out'
                    ),
                    'type' => $isIncome ? 'cash_in' : 'cash_out',
                    'direction' => $isIncome ? 'in' : 'out',
                    'party_id' => $otherParty->id,
                    'cashbox_id' => $cashbox?->id,
                    'payment_date' => $tx->date ?? $tx->created_at,
                    'amount' => abs($tx->amount),
                    'net_amount' => abs($tx->amount),
                    'status' => 'confirmed',
                    'description' => $tx->description,
                ]);
                
                PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'document_id' => $doc->id,
                    'amount' => abs($tx->amount),
                    'allocation_date' => $tx->date ?? $tx->created_at,
                    'status' => 'active',
                ]);
            }
        }
        
        $this->info('Migrated finance transactions');
    }
}
