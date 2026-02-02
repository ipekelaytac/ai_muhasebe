<?php

namespace App\Console\Commands;

use App\Domain\Accounting\Models\Document;
use App\Domain\Accounting\Models\Payment;
use App\Domain\Accounting\Models\PaymentAllocation;
use App\Domain\Accounting\Models\Party;
use App\Domain\Accounting\Models\Cashbox;
use App\Domain\Accounting\Models\BankAccount;
// Legacy models removed - tables dropped
// use App\Models\FinanceTransaction;
// use App\Models\CustomerTransaction;
use App\Models\PayrollPayment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyAccountingIntegrity extends Command
{
    protected $signature = 'accounting:verify-integrity 
                            {--company-id= : Specific company ID to verify}
                            {--fail-on-mismatch : Exit with error code if mismatches found}';

    protected $description = 'Verify accounting system integrity by comparing old vs new balances';

    public function handle(): int
    {
        $this->info('🔍 Verifying Accounting System Integrity...');
        $this->newLine();
        
        $companyId = $this->option('company-id');
        $failOnMismatch = $this->option('fail-on-mismatch');
        
        $errors = [];
        $warnings = [];
        
        // Check for old system usage
        $this->info('1. Checking for deprecated model usage...');
        $oldUsage = $this->checkOldSystemUsage($companyId);
        if (!empty($oldUsage)) {
            $warnings[] = 'Old accounting models still have records. Migration may be incomplete.';
            foreach ($oldUsage as $model => $count) {
                $this->warn("   - {$model}: {$count} records");
            }
        } else {
            $this->info('   ✅ No old system records found');
        }
        $this->newLine();
        
        // Verify party balances
        $this->info('2. Verifying party balances...');
        $partyErrors = $this->verifyPartyBalances($companyId);
        $errors = array_merge($errors, $partyErrors);
        if (empty($partyErrors)) {
            $this->info('   ✅ All party balances are consistent');
        }
        $this->newLine();
        
        // Verify cash/bank balances
        $this->info('3. Verifying cash/bank balances...');
        $balanceErrors = $this->verifyCashBankBalances($companyId);
        $errors = array_merge($errors, $balanceErrors);
        if (empty($balanceErrors)) {
            $this->info('   ✅ All cash/bank balances are consistent');
        }
        $this->newLine();
        
        // Verify allocation constraints
        $this->info('4. Verifying allocation constraints...');
        $allocationErrors = $this->verifyAllocationConstraints();
        $errors = array_merge($errors, $allocationErrors);
        if (empty($allocationErrors)) {
            $this->info('   ✅ All allocation constraints are valid');
        }
        $this->newLine();
        
        // Verify document status consistency
        $this->info('5. Verifying document status consistency...');
        $statusErrors = $this->verifyDocumentStatuses();
        $errors = array_merge($errors, $statusErrors);
        if (empty($statusErrors)) {
            $this->info('   ✅ All document statuses are consistent');
        }
        $this->newLine();
        
        // Summary
        $this->info('📊 Summary:');
        $this->newLine();
        
        if (!empty($warnings)) {
            foreach ($warnings as $warning) {
                $this->warn("   ⚠️  {$warning}");
            }
            $this->newLine();
        }
        
        if (empty($errors)) {
            $this->info('   ✅ All integrity checks passed!');
            return Command::SUCCESS;
        } else {
            $this->error('   ❌ Found ' . count($errors) . ' error(s):');
            foreach ($errors as $error) {
                $this->error("      - {$error}");
            }
            
            if ($failOnMismatch) {
                return Command::FAILURE;
            }
            
            return Command::SUCCESS;
        }
    }
    
    private function checkOldSystemUsage(?int $companyId): array
    {
        $usage = [];
        
        $query = FinanceTransaction::query();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        $count = $query->count();
        if ($count > 0) {
            $usage['FinanceTransaction'] = $count;
        }
        
        $query = CustomerTransaction::query();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        $count = $query->count();
        if ($count > 0) {
            $usage['CustomerTransaction'] = $count;
        }
        
        $query = PayrollPayment::query();
        if ($companyId) {
            $query->whereHas('payrollItem', function ($q) use ($companyId) {
                $q->whereHas('payrollPeriod', function ($q2) use ($companyId) {
                    $q2->where('company_id', $companyId);
                });
            });
        }
        $count = $query->count();
        if ($count > 0) {
            $usage['PayrollPayment'] = $count;
        }
        
        return $usage;
    }
    
    private function verifyPartyBalances(?int $companyId): array
    {
        $errors = [];
        
        $query = Party::query();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        
        $parties = $query->get();
        
        foreach ($parties as $party) {
            // Calculate receivable balance from documents
            $receivableDocs = Document::where('party_id', $party->id)
                ->where('direction', 'receivable')
                ->whereIn('status', ['pending', 'partial'])
                ->get();
            
            $calculatedReceivable = $receivableDocs->sum(fn($doc) => $doc->unpaid_amount);
            
            // Calculate payable balance from documents
            $payableDocs = Document::where('party_id', $party->id)
                ->where('direction', 'payable')
                ->whereIn('status', ['pending', 'partial'])
                ->get();
            
            $calculatedPayable = $payableDocs->sum(fn($doc) => $doc->unpaid_amount);
            
            // Compare with model attributes
            $modelReceivable = $party->receivable_balance;
            $modelPayable = $party->payable_balance;
            
            if (abs($calculatedReceivable - $modelReceivable) > 0.01) {
                $errors[] = "Party {$party->id} ({$party->name}): Receivable mismatch. Calculated: {$calculatedReceivable}, Model: {$modelReceivable}";
            }
            
            if (abs($calculatedPayable - $modelPayable) > 0.01) {
                $errors[] = "Party {$party->id} ({$party->name}): Payable mismatch. Calculated: {$calculatedPayable}, Model: {$modelPayable}";
            }
        }
        
        return $errors;
    }
    
    private function verifyCashBankBalances(?int $companyId): array
    {
        $errors = [];
        
        // Check cashboxes
        $cashboxQuery = Cashbox::query();
        if ($companyId) {
            $cashboxQuery->where('company_id', $companyId);
        }
        
        foreach ($cashboxQuery->get() as $cashbox) {
            $calculatedBalance = $cashbox->getBalanceAsOf(now()->toDateString());
            $modelBalance = $cashbox->balance;
            
            if (abs($calculatedBalance - $modelBalance) > 0.01) {
                $errors[] = "Cashbox {$cashbox->id} ({$cashbox->name}): Balance mismatch. Calculated: {$calculatedBalance}, Model: {$modelBalance}";
            }
        }
        
        // Check bank accounts
        $bankQuery = BankAccount::query();
        if ($companyId) {
            $bankQuery->where('company_id', $companyId);
        }
        
        foreach ($bankQuery->get() as $bank) {
            $calculatedBalance = $bank->getBalanceAsOf(now()->toDateString());
            $modelBalance = $bank->balance;
            
            if (abs($calculatedBalance - $modelBalance) > 0.01) {
                $errors[] = "Bank Account {$bank->id} ({$bank->name}): Balance mismatch. Calculated: {$calculatedBalance}, Model: {$modelBalance}";
            }
        }
        
        return $errors;
    }
    
    private function verifyAllocationConstraints(): array
    {
        $errors = [];
        
        // Check: sum(allocations for document) <= document.total_amount
        $documents = Document::whereIn('status', ['pending', 'partial', 'settled'])->get();
        
        foreach ($documents as $doc) {
            $totalAllocated = PaymentAllocation::where('document_id', $doc->id)
                ->where('status', 'active')
                ->sum('amount');
            
            if ($totalAllocated > $doc->total_amount + 0.01) {
                $errors[] = "Document {$doc->id} ({$doc->document_number}): Over-allocated. Total: {$doc->total_amount}, Allocated: {$totalAllocated}";
            }
        }
        
        // Check: sum(allocations for payment) <= payment.amount
        $payments = Payment::where('status', 'confirmed')->get();
        
        foreach ($payments as $payment) {
            $totalAllocated = PaymentAllocation::where('payment_id', $payment->id)
                ->where('status', 'active')
                ->sum('amount');
            
            if ($totalAllocated > $payment->amount + 0.01) {
                $errors[] = "Payment {$payment->id} ({$payment->payment_number}): Over-allocated. Amount: {$payment->amount}, Allocated: {$totalAllocated}";
            }
        }
        
        return $errors;
    }
    
    private function verifyDocumentStatuses(): array
    {
        $errors = [];
        
        $documents = Document::whereIn('status', ['pending', 'partial', 'settled'])->get();
        
        foreach ($documents as $doc) {
            $doc->refresh();
            $calculatedStatus = $doc->status;
            
            // Recalculate status
            $allocated = $doc->allocated_amount;
            $expectedStatus = 'pending';
            
            if ($allocated >= $doc->total_amount - 0.001) {
                $expectedStatus = 'settled';
            } elseif ($allocated > 0) {
                $expectedStatus = 'partial';
            }
            
            if ($calculatedStatus !== $expectedStatus && !in_array($doc->status, ['cancelled', 'reversed', 'draft'])) {
                $errors[] = "Document {$doc->id} ({$doc->document_number}): Status mismatch. Current: {$calculatedStatus}, Expected: {$expectedStatus}";
            }
        }
        
        return $errors;
    }
}
