<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\AccountingPeriod;
use App\Models\Cashbox;
use App\Models\BankAccount;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class RecordPaymentService
{
    /**
     * Record a payment (cash/bank movement)
     *
     * @param array $data
     * @return Payment
     * @throws \Exception
     */
    public function create(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            // Validate period is not locked
            $period = AccountingPeriod::findOrCreateForDate(
                $data['company_id'],
                $data['branch_id'],
                $data['payment_date']
            );

            if ($period->isLocked()) {
                throw new \Exception('Cannot create payment in locked period');
            }

            // Validate payment type constraints
            $this->validatePaymentType($data);

            // Validate balance for outflows
            if ($data['direction'] === 'outflow') {
                $this->validateBalance($data);
            }

            // Create payment
            $payment = Payment::create([
                'company_id' => $data['company_id'],
                'branch_id' => $data['branch_id'],
                'accounting_period_id' => $period->id,
                'payment_number' => $data['payment_number'] ?? $this->generatePaymentNumber($data),
                'payment_type' => $data['payment_type'],
                'direction' => $data['direction'],
                'status' => $data['status'] ?? 'posted',
                'party_id' => $data['party_id'] ?? null,
                'cashbox_id' => $data['cashbox_id'] ?? null,
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'from_cashbox_id' => $data['from_cashbox_id'] ?? null,
                'to_cashbox_id' => $data['to_cashbox_id'] ?? null,
                'from_bank_account_id' => $data['from_bank_account_id'] ?? null,
                'to_bank_account_id' => $data['to_bank_account_id'] ?? null,
                'payment_date' => $data['payment_date'],
                'amount' => $data['amount'],
                'allocated_amount' => 0,
                'unallocated_amount' => $data['amount'],
                'description' => $data['description'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'created_by' => Auth::id(),
            ]);

            // Log audit
            AuditLog::create([
                'company_id' => $payment->company_id,
                'branch_id' => $payment->branch_id,
                'auditable_type' => Payment::class,
                'auditable_id' => $payment->id,
                'user_id' => Auth::id(),
                'event' => 'created',
                'new_values' => $payment->toArray(),
                'description' => "Payment {$payment->payment_number} created",
            ]);

            return $payment->fresh();
        });
    }

    /**
     * Validate payment type constraints
     */
    private function validatePaymentType(array $data): void
    {
        $type = $data['payment_type'];
        $direction = $data['direction'];

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
                // Transfer must have from and to accounts
                $hasFrom = isset($data['from_cashbox_id']) || isset($data['from_bank_account_id']);
                $hasTo = isset($data['to_cashbox_id']) || isset($data['to_bank_account_id']);
                if (!$hasFrom || !$hasTo) {
                    throw new \Exception('transfer requires from and to accounts');
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
     */
    private function validateBalance(array $data): void
    {
        $amount = $data['amount'];

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

        if (isset($data['from_cashbox_id'])) {
            $cashbox = Cashbox::findOrFail($data['from_cashbox_id']);
            $balance = $cashbox->balance;
            if ($balance < $amount) {
                throw new \Exception("Insufficient cash balance for transfer. Available: {$balance}, Required: {$amount}");
            }
        }

        if (isset($data['from_bank_account_id'])) {
            $bankAccount = BankAccount::findOrFail($data['from_bank_account_id']);
            $balance = $bankAccount->balance;
            if ($balance < $amount) {
                throw new \Exception("Insufficient bank balance for transfer. Available: {$balance}, Required: {$amount}");
            }
        }
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

        $lastPayment = Payment::where('company_id', $data['company_id'])
            ->where('branch_id', $data['branch_id'])
            ->where('payment_type', $data['payment_type'])
            ->whereYear('payment_date', $year)
            ->whereMonth('payment_date', $date->month)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastPayment ? (int) substr($lastPayment->payment_number ?? '0000', -4) + 1 : 1;

        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $sequence);
    }
}
