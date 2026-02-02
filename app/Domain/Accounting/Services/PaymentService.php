<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Enums\PaymentType;
use App\Domain\Accounting\Models\AuditLog;
use App\Domain\Accounting\Models\BankAccount;
use App\Domain\Accounting\Models\Cashbox;
use App\Domain\Accounting\Models\Payment;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    protected PeriodService $periodService;
    
    public function __construct(PeriodService $periodService)
    {
        $this->periodService = $periodService;
    }
    
    /**
     * Create a new payment
     */
    public function createPayment(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            // Validate period is open
            $this->periodService->validatePeriodOpen(
                $data['company_id'],
                $data['payment_date']
            );
            
            // For internal_offset, direction should be 'internal'
            if ($data['type'] === PaymentType::INTERNAL_OFFSET) {
                $data['direction'] = 'internal'; // Special direction for internal offsets
            } elseif (empty($data['direction'])) {
                // Auto-determine direction if not provided
                $data['direction'] = PaymentType::getDirection($data['type']);
            }
            
            // Validate cashbox/bank account (not required for internal_offset)
            if (PaymentType::requiresAccount($data['type'])) {
                $this->validatePaymentAccount($data);
            }
            
            // Generate payment number if not provided
            if (empty($data['payment_number'])) {
                $data['payment_number'] = Payment::generateNumber(
                    $data['company_id'],
                    $data['branch_id'] ?? null,
                    $data['type']
                );
            }
            
            // Set default status
            if (empty($data['status'])) {
                $data['status'] = 'confirmed';
            }
            
            // Set default fee_amount if not provided
            if (!isset($data['fee_amount']) || $data['fee_amount'] === null) {
                $data['fee_amount'] = 0;
            }
            
            // Calculate net amount
            $data['net_amount'] = $data['amount'] - $data['fee_amount'];
            
            // Create payment
            $payment = Payment::create($data);
            
            AuditLog::log($payment, 'create', null, $payment->toArray());
            
            return $payment->fresh(['party', 'cashbox', 'bankAccount']);
        });
    }
    
    /**
     * Validate that correct account is provided for payment type
     */
    protected function validatePaymentAccount(array $data): void
    {
        $type = $data['type'];
        
        // Cash payments require cashbox
        if (in_array($type, PaymentType::CASH_TYPES)) {
            if (empty($data['cashbox_id'])) {
                throw new \Exception('Kasa ödemeleri için kasa seçilmelidir.');
            }
            
            $cashbox = Cashbox::find($data['cashbox_id']);
            if (!$cashbox || $cashbox->company_id != $data['company_id']) {
                throw new \Exception('Geçersiz kasa.');
            }
            
            if (!$cashbox->is_active) {
                throw new \Exception('Seçilen kasa aktif değil.');
            }
            
            // For cash out, check balance
            if ($type === PaymentType::CASH_OUT) {
                $balance = $cashbox->balance;
                $amount = $data['amount'];
                if ($balance < $amount) {
                    throw new \Exception("Yetersiz kasa bakiyesi. Mevcut: {$balance}, Gerekli: {$amount}");
                }
            }
        }
        
        // Bank payments require bank account
        if (in_array($type, PaymentType::BANK_TYPES)) {
            if (empty($data['bank_account_id'])) {
                throw new \Exception('Banka ödemeleri için banka hesabı seçilmelidir.');
            }
            
            $bankAccount = BankAccount::find($data['bank_account_id']);
            if (!$bankAccount || $bankAccount->company_id != $data['company_id']) {
                throw new \Exception('Geçersiz banka hesabı.');
            }
            
            if (!$bankAccount->is_active) {
                throw new \Exception('Seçilen banka hesabı aktif değil.');
            }
        }
        
        // Transfers require both source and destination
        if ($type === PaymentType::TRANSFER) {
            $hasSource = !empty($data['cashbox_id']) || !empty($data['bank_account_id']);
            $hasDest = !empty($data['to_cashbox_id']) || !empty($data['to_bank_account_id']);
            
            if (!$hasSource || !$hasDest) {
                throw new \Exception('Virman işlemi için kaynak ve hedef hesap gereklidir.');
            }
        }
    }
    
    /**
     * Update a payment
     */
    public function updatePayment(Payment $payment, array $data): Payment
    {
        return DB::transaction(function () use ($payment, $data) {
            if (!$payment->canModify()) {
                throw new \Exception('Bu ödeme değiştirilemez.');
            }
            
            $oldValues = $payment->toArray();
            
            // If changing date, validate new period
            if (isset($data['payment_date']) && $data['payment_date'] != $payment->payment_date) {
                $this->periodService->validatePeriodOpen(
                    $payment->company_id,
                    $data['payment_date']
                );
            }
            
            // Set default fee_amount if updating and null
            if (isset($data['fee_amount']) && $data['fee_amount'] === null) {
                $data['fee_amount'] = 0;
            }
            
            // Recalculate net amount if amount or fee changed
            if (isset($data['amount']) || isset($data['fee_amount'])) {
                $amount = $data['amount'] ?? $payment->amount;
                $fee = $data['fee_amount'] ?? $payment->fee_amount;
                $data['net_amount'] = $amount - $fee;
            }
            
            $payment->update($data);
            
            AuditLog::log($payment, 'update', $oldValues, $payment->fresh()->toArray());
            
            return $payment->fresh(['party', 'cashbox', 'bankAccount']);
        });
    }
    
    /**
     * Cancel a payment
     */
    public function cancelPayment(Payment $payment, ?string $reason = null): Payment
    {
        return DB::transaction(function () use ($payment, $reason) {
            if ($payment->status === 'cancelled') {
                throw new \Exception('Ödeme zaten iptal edilmiş.');
            }
            
            // Check for active allocations
            if ($payment->activeAllocations()->exists()) {
                throw new \Exception('Dağıtımı olan ödeme iptal edilemez. Önce dağıtımları iptal edin.');
            }
            
            // Validate period
            $this->periodService->validatePeriodOpen(
                $payment->company_id,
                $payment->payment_date
            );
            
            $oldValues = $payment->toArray();
            
            $payment->update([
                'status' => 'cancelled',
                'notes' => $payment->notes 
                    ? $payment->notes . "\n\nİptal nedeni: " . ($reason ?? 'Belirtilmedi')
                    : "İptal nedeni: " . ($reason ?? 'Belirtilmedi'),
            ]);
            
            AuditLog::log($payment, 'status_change', $oldValues, $payment->toArray());
            
            return $payment;
        });
    }
    
    /**
     * Reverse a payment (create a reversal payment)
     */
    public function reversePayment(Payment $payment, ?string $reason = null): Payment
    {
        return DB::transaction(function () use ($payment, $reason) {
            if ($payment->status === 'cancelled' || $payment->status === 'reversed') {
                throw new \Exception('Bu ödeme zaten iptal/ters kayıt edilmiş.');
            }
            
            // Cancel active allocations first
            foreach ($payment->activeAllocations as $allocation) {
                $allocation->cancel();
            }
            
            // Create reversal payment with opposite direction
            $reversalData = $payment->toArray();
            unset($reversalData['id'], $reversalData['created_at'], $reversalData['updated_at'], $reversalData['deleted_at']);
            
            $reversalData['payment_date'] = now()->toDateString();
            $reversalData['payment_number'] = Payment::generateNumber(
                $payment->company_id,
                $payment->branch_id,
                $payment->type
            );
            $reversalData['reversed_payment_id'] = $payment->id;
            $reversalData['status'] = 'reversed';
            $reversalData['direction'] = $payment->direction === 'in' ? 'out' : 'in'; // Opposite
            $reversalData['description'] = "Ters kayıt: {$payment->payment_number}" . ($reason ? " - {$reason}" : '');
            
            // Ensure fee_amount is set (not null)
            if (!isset($reversalData['fee_amount']) || $reversalData['fee_amount'] === null) {
                $reversalData['fee_amount'] = 0;
            }
            
            // Recalculate net_amount
            $reversalData['net_amount'] = $reversalData['amount'] - $reversalData['fee_amount'];
            
            $reversalPayment = Payment::create($reversalData);
            
            // Mark original as reversed
            $oldValues = $payment->toArray();
            $payment->update([
                'status' => 'reversed',
                'reversal_payment_id' => $reversalPayment->id,
            ]);
            
            AuditLog::log($payment, 'status_change', $oldValues, $payment->toArray());
            AuditLog::log($reversalPayment, 'create', null, $reversalPayment->toArray());
            
            return $reversalPayment;
        });
    }
    
    /**
     * Get payment with all relations
     */
    public function getPayment(int $id): Payment
    {
        return Payment::with([
            'party',
            'cashbox',
            'bankAccount',
            'toCashbox',
            'toBankAccount',
            'activeAllocations.document',
            'attachments',
        ])->findOrFail($id);
    }
    
    /**
     * List payments with filters
     */
    public function listPayments(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Payment::with(['party', 'cashbox', 'bankAccount'])
            ->scoped($filters['company_id'], $filters['branch_id'] ?? null);
        
        if (!empty($filters['type'])) {
            $query->ofType($filters['type']);
        }
        
        if (!empty($filters['direction'])) {
            $query->where('direction', $filters['direction']);
        }
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['party_id'])) {
            $query->forParty($filters['party_id']);
        }
        
        if (!empty($filters['cashbox_id'])) {
            $query->forCashbox($filters['cashbox_id']);
        }
        
        if (!empty($filters['bank_account_id'])) {
            $query->forBankAccount($filters['bank_account_id']);
        }
        
        if (!empty($filters['start_date'])) {
            $query->where('payment_date', '>=', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $query->where('payment_date', '<=', $filters['end_date']);
        }
        
        if (!empty($filters['min_amount'])) {
            $query->where('amount', '>=', $filters['min_amount']);
        }
        
        if (!empty($filters['max_amount'])) {
            $query->where('amount', '<=', $filters['max_amount']);
        }
        
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('payment_number', 'like', "%{$filters['search']}%")
                  ->orWhere('reference_number', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }
        
        $sortBy = $filters['sort_by'] ?? 'payment_date';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);
        
        $perPage = $filters['per_page'] ?? 20;
        
        return $query->paginate($perPage);
    }
}
