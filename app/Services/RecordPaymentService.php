<?php

namespace App\Services;

use App\Domain\Accounting\Models\Payment;
use App\Models\AccountingPeriod;
use App\Domain\Accounting\Models\Cashbox;
use App\Models\BankAccount;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

/**
 * @deprecated This service is deprecated. Use App\Domain\Accounting\Services\PaymentService instead.
 * This class is kept for backward compatibility during migration only.
 */
class RecordPaymentService
{
    /**
     * Record a payment (cash/bank movement)
     *
     * @param array $data
     * @return \App\Domain\Accounting\Models\Payment
     * @throws \Exception
     * @deprecated Use App\Domain\Accounting\Services\PaymentService::createPayment() instead
     */
    public function create(array $data): Payment
    {
        // Delegate to Domain service
        $paymentService = app(\App\Domain\Accounting\Services\PaymentService::class);
        
        // Map old data format to new format
        $newData = [
            'company_id' => $data['company_id'],
            'branch_id' => $data['branch_id'] ?? null,
            'type' => $data['payment_type'] ?? $data['type'] ?? 'cash_out',
            'direction' => $data['direction'] === 'outflow' ? 'out' : ($data['direction'] === 'inflow' ? 'in' : $data['direction']),
            'party_id' => $data['party_id'] ?? null,
            'cashbox_id' => $data['cashbox_id'] ?? null,
            'bank_account_id' => $data['bank_account_id'] ?? null,
            'to_cashbox_id' => $data['to_cashbox_id'] ?? null,
            'to_bank_account_id' => $data['to_bank_account_id'] ?? null,
            'payment_date' => $data['payment_date'],
            'amount' => $data['amount'],
            'fee_amount' => $data['fee_amount'] ?? 0,
            'description' => $data['description'] ?? null,
        ];
        
        return $paymentService->createPayment($newData);
    }
    
    /**
     * @deprecated This method is deprecated. Use App\Domain\Accounting\Services\PaymentService instead.
     */
    private function createLegacy(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            // Validate period is not locked (periods are company-level only)
            $period = AccountingPeriod::findOrCreateForDate(
                $data['company_id'],
                $data['payment_date']
            );

            if ($period->isLocked()) {
                throw new \Exception('Cannot create payment in locked period');
            }

            // Validate payment type constraints
            $this->validatePaymentType($data);

            // Validate balance for outflows (map 'outflow' to 'out' for schema)
            $direction = $data['direction'] === 'outflow' ? 'out' : ($data['direction'] === 'inflow' ? 'in' : $data['direction']);
            if ($direction === 'out') {
                $this->validateBalance($data);
            }

            // Extract period year/month from payment date
            $paymentDate = Carbon::parse($data['payment_date']);

            // Build payment data array (only include columns that exist in schema)
            $paymentData = [
                'company_id' => $data['company_id'],
                'branch_id' => $data['branch_id'],
                'payment_number' => $data['payment_number'] ?? $this->generatePaymentNumber($data),
                'type' => $data['payment_type'], // Map payment_type input to type column
                'direction' => $direction, // Map inflow/outflow to in/out
                'status' => $data['status'] ?? 'confirmed', // Schema default is 'confirmed', not 'posted'
                'party_id' => $data['party_id'] ?? null,
                'cashbox_id' => $data['cashbox_id'] ?? null,
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'to_cashbox_id' => $data['to_cashbox_id'] ?? null, // Schema has to_* but NOT from_*
                'to_bank_account_id' => $data['to_bank_account_id'] ?? null,
                'payment_date' => $data['payment_date'],
                'period_year' => $paymentDate->year, // Schema uses period_year/month, not FK
                'period_month' => $paymentDate->month,
                'amount' => $data['amount'],
                'description' => $data['description'] ?? null,
                'created_by' => Auth::id(),
            ];
            
            // Calculate net_amount (schema requires net_amount - no default value)
            if (isset($data['fee_amount'])) {
                $paymentData['fee_amount'] = $data['fee_amount'];
                $paymentData['net_amount'] = $data['amount'] - $data['fee_amount'];
            } else {
                $paymentData['fee_amount'] = 0; // Default fee_amount
                $paymentData['net_amount'] = $data['amount']; // Default: net = amount if no fee
            }
            
            // Filter to only existing columns (safety check - schema does NOT have metadata)
            // Note: net_amount is REQUIRED, so filter must keep it
            $paymentData = $this->filterByExistingColumns('payments', $paymentData);

            // Create payment (schema uses period_year/month, NOT accounting_period_id FK)
            // Schema does NOT have allocated_amount/unallocated_amount/metadata columns - these are calculated
            $payment = Payment::create($paymentData);

            // Log audit (schema uses 'action' not 'event', no branch_id/description, only created_at not updated_at)
            $auditData = [
                'company_id' => $payment->company_id,
                'auditable_type' => Payment::class,
                'auditable_id' => $payment->id,
                'action' => 'create', // Schema uses 'action' enum, not 'event'
                'new_values' => $payment->toArray(),
                'user_id' => Auth::id(),
                'created_at' => now(), // Schema only has created_at, not updated_at
            ];
            // Filter to only existing columns (schema does NOT have branch_id/description/event/updated_at)
            $auditData = $this->filterByExistingColumns('audit_logs', $auditData);
            AuditLog::create($auditData);

            return $payment->fresh();
        });
    }

    /**
     * Validate payment type constraints
     */
    private function validatePaymentType(array $data): void
    {
        $type = $data['payment_type'];
        $direction = $data['direction']; // Accept inflow/outflow from input, will map to in/out

        switch ($type) {
            case 'cash_in':
                if ($direction !== 'inflow' || !isset($data['cashbox_id'])) {
                    throw new \Exception('cash_in must be inflow and require cashbox_id');
                }
                break;
            case 'cash_out':
                if ($direction !== 'outflow' || !isset($data['cashbox_id'])) {
                    throw new \Exception('cash_out must be outflow and require cashbox_id');
                }
                break;
            case 'bank_in':
                if ($direction !== 'inflow' || !isset($data['bank_account_id'])) {
                    throw new \Exception('bank_in must be inflow and require bank_account_id');
                }
                break;
            case 'bank_out':
                if ($direction !== 'outflow' || !isset($data['bank_account_id'])) {
                    throw new \Exception('bank_out must be outflow and require bank_account_id');
                }
                break;
            case 'transfer':
                // Transfer: from account is cashbox_id or bank_account_id, to account is to_cashbox_id or to_bank_account_id
                // Schema has to_* columns but NOT from_* columns (from is indicated by cashbox_id/bank_account_id)
                $hasFrom = isset($data['cashbox_id']) || isset($data['bank_account_id']);
                $hasTo = isset($data['to_cashbox_id']) || isset($data['to_bank_account_id']);
                if (!$hasFrom || !$hasTo) {
                    throw new \Exception('transfer requires from account (cashbox_id or bank_account_id) and to account (to_cashbox_id or to_bank_account_id)');
                }
                break;
            case 'pos_in':
                if ($direction !== 'inflow' || !isset($data['bank_account_id'])) {
                    throw new \Exception('pos_in must be inflow and require bank_account_id');
                }
                break;
        }
    }

    /**
     * Validate balance for outflows
     * Schema uses cashbox_id/bank_account_id for source account (NOT from_cashbox_id/from_bank_account_id)
     */
    private function validateBalance(array $data): void
    {
        $amount = $data['amount'];

        // For regular payments: check cashbox_id or bank_account_id
        if (isset($data['cashbox_id'])) {
            $cashbox = Cashbox::findOrFail($data['cashbox_id']);
            $balance = $cashbox->balance;
            if ($balance < $amount) {
                throw new \Exception("Insufficient cash balance. Available: {$balance}, Required: {$amount}");
            }
        }

        if (isset($data['bank_account_id'])) {
            $bankAccount = BankAccount::findOrFail($data['bank_account_id']);
            $balance = $bankAccount->balance;
            if ($balance < $amount) {
                throw new \Exception("Insufficient bank balance. Available: {$balance}, Required: {$amount}");
            }
        }

        // For transfers: source account is cashbox_id or bank_account_id (schema has NO from_* columns)
        // The balance check above already covers transfers since they use cashbox_id/bank_account_id
    }

    /**
     * Generate payment number if not provided
     */
    private function generatePaymentNumber(array $data): string
    {
        $prefix = strtoupper(substr($data['payment_type'], 0, 3));
        $date = Carbon::parse($data['payment_date']);
        $year = $date->year;
        $month = str_pad($date->month, 2, '0', STR_PAD_LEFT);

        // Query using 'type' column as per schema (not 'payment_type')
        // Unique constraint is on company_id + payment_number (NOT branch_id, NOT type)
        // So payment numbers must be unique across ALL types for the same company
        // Query by payment_number pattern to find the highest sequence number across all types
        $pattern = sprintf('%s-%s%s-%%', $prefix, $year, $month);
        
        $lastPayment = Payment::where('company_id', $data['company_id'])
            ->where('payment_number', 'like', $pattern)
            ->orderBy('payment_number', 'desc')
            ->first();

        if ($lastPayment) {
            // Extract sequence from payment_number (last 4 digits)
            $lastNumber = $lastPayment->payment_number;
            $lastSequence = (int) substr($lastNumber, -4);
            $sequence = $lastSequence + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $sequence);
    }

    /**
     * Filter array to only include columns that exist in the table schema
     *
     * @param string $table
     * @param array $data
     * @return array
     */
    private function filterByExistingColumns(string $table, array $data): array
    {
        $filtered = [];
        foreach ($data as $key => $value) {
            if (Schema::hasColumn($table, $key)) {
                $filtered[$key] = $value;
            }
        }
        return $filtered;
    }
}
