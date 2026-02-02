<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
// Legacy models removed - tables dropped
// use App\Models\CustomerTransaction;
// use App\Models\Customer;
use App\Models\Party;
use App\Models\Document;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Services\CreateObligationService;
use App\Services\RecordPaymentService;
use Illuminate\Support\Facades\DB;

class MigrateCustomerTransactions extends Command
{
    protected $signature = 'accounting:migrate-customer-transactions {--dry-run : Run without making changes}';
    protected $description = 'Migrate customer_transactions to documents/payments/allocations';

    protected $createObligationService;
    protected $recordPaymentService;

    public function __construct(
        CreateObligationService $createObligationService,
        RecordPaymentService $recordPaymentService
    ) {
        parent::__construct();
        $this->createObligationService = $createObligationService;
        $this->recordPaymentService = $recordPaymentService;
    }

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        $transactions = CustomerTransaction::all();
        $this->info("Found {$transactions->count()} transactions to migrate");

        $bar = $this->output->createProgressBar($transactions->count());
        $bar->start();

        $documentsCreated = 0;
        $paymentsCreated = 0;
        $skipped = 0;

        foreach ($transactions as $transaction) {
            try {
                // Find party
                $customer = Customer::find($transaction->customer_id);
                if (!$customer) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                $party = Party::where('partyable_type', Customer::class)
                    ->where('partyable_id', $customer->id)
                    ->first();

                if (!$party) {
                    $this->warn("Party not found for customer {$customer->id}, skipping");
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                if (!$dryRun) {
                    DB::transaction(function () use ($transaction, $party, &$documentsCreated, &$paymentsCreated) {
                        // Determine document type and direction
                        $documentType = $transaction->type === 'income' ? 'customer_invoice' : 'supplier_invoice';
                        $direction = $transaction->type === 'income' ? 'receivable' : 'payable';

                        // Create document
                        $document = Document::create([
                            'company_id' => $transaction->company_id,
                            'branch_id' => $transaction->branch_id,
                            'document_type' => $documentType,
                            'direction' => $direction,
                            'status' => 'posted',
                            'party_id' => $party->id,
                            'document_date' => $transaction->transaction_date,
                            'due_date' => $transaction->transaction_date,
                            'total_amount' => $transaction->amount,
                            'paid_amount' => 0,
                            'unpaid_amount' => $transaction->amount,
                            'description' => $transaction->description,
                            'created_by' => $transaction->created_by ?? 1,
                            'metadata' => [
                                'migrated_from' => 'customer_transactions',
                                'original_id' => $transaction->id,
                            ],
                        ]);

                        $documentsCreated++;

                        // For now, assume transaction represents both accrual and payment
                        // In real scenario, you might need to separate accruals from payments
                        // Create payment and allocation
                        $payment = Payment::create([
                            'company_id' => $transaction->company_id,
                            'branch_id' => $transaction->branch_id,
                            'payment_type' => $transaction->type === 'income' ? 'cash_in' : 'cash_out',
                            'direction' => $transaction->type === 'income' ? 'inflow' : 'outflow',
                            'status' => 'posted',
                            'party_id' => $party->id,
                            'payment_date' => $transaction->transaction_date,
                            'amount' => $transaction->amount,
                            'allocated_amount' => 0,
                            'unallocated_amount' => $transaction->amount,
                            'description' => $transaction->description,
                            'created_by' => $transaction->created_by ?? 1,
                            'metadata' => [
                                'migrated_from' => 'customer_transactions',
                                'original_id' => $transaction->id,
                            ],
                        ]);

                        $paymentsCreated++;

                        // Create allocation
                        PaymentAllocation::create([
                            'payment_id' => $payment->id,
                            'document_id' => $document->id,
                            'amount' => $transaction->amount,
                            'created_by' => $transaction->created_by ?? 1,
                        ]);

                        // Recalculate
                        $document->recalculatePaidAmount();
                        $payment->recalculateAllocatedAmount();
                    });
                } else {
                    $documentsCreated++;
                    $paymentsCreated++;
                }

                $bar->advance();
            } catch (\Exception $e) {
                $this->error("Error migrating transaction {$transaction->id}: " . $e->getMessage());
                $skipped++;
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();

        $this->info("Migration complete!");
        $this->info("Documents created: {$documentsCreated}");
        $this->info("Payments created: {$paymentsCreated}");
        $this->info("Skipped: {$skipped}");

        return 0;
    }
}
