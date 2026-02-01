<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Enums\ChequeStatus;
use App\Domain\Accounting\Enums\DocumentType;
use App\Domain\Accounting\Enums\PaymentType;
use App\Domain\Accounting\Models\AuditLog;
use App\Domain\Accounting\Models\Cheque;
use App\Domain\Accounting\Models\Document;
use Illuminate\Support\Facades\DB;

class ChequeService
{
    protected DocumentService $documentService;
    protected PaymentService $paymentService;
    protected AllocationService $allocationService;
    
    public function __construct(
        DocumentService $documentService,
        PaymentService $paymentService,
        AllocationService $allocationService
    ) {
        $this->documentService = $documentService;
        $this->paymentService = $paymentService;
        $this->allocationService = $allocationService;
    }
    
    /**
     * Receive a cheque (create cheque + document)
     */
    public function receiveCheque(array $data): Cheque
    {
        return DB::transaction(function () use ($data) {
            // Create cheque
            $data['type'] = 'received';
            $data['status'] = ChequeStatus::IN_PORTFOLIO;
            
            if (empty($data['cheque_number'])) {
                $data['cheque_number'] = Cheque::generateNumber($data['company_id'], 'received');
            }
            
            $cheque = Cheque::create($data);
            
            // Create receivable document
            $document = $this->documentService->createDocument([
                'company_id' => $data['company_id'],
                'branch_id' => $data['branch_id'] ?? null,
                'type' => DocumentType::CHEQUE_RECEIVABLE,
                'party_id' => $data['party_id'],
                'document_date' => $data['receive_date'] ?? $data['issue_date'],
                'due_date' => $data['due_date'],
                'total_amount' => $data['amount'],
                'cheque_id' => $cheque->id,
                'description' => "Alınan çek: {$cheque->cheque_number}",
            ]);
            
            // Link document to cheque
            $cheque->update(['document_id' => $document->id]);
            
            // Record event
            $cheque->recordEvent(ChequeStatus::IN_PORTFOLIO, null, null, 'Çek alındı');
            
            AuditLog::log($cheque, 'create', null, $cheque->toArray());
            
            return $cheque->fresh(['party', 'document', 'bankAccount']);
        });
    }
    
    /**
     * Issue a cheque (create cheque + document)
     */
    public function issueCheque(array $data): Cheque
    {
        return DB::transaction(function () use ($data) {
            $data['type'] = 'issued';
            $data['status'] = ChequeStatus::PENDING_ISSUE;
            
            if (empty($data['cheque_number'])) {
                $data['cheque_number'] = Cheque::generateNumber($data['company_id'], 'issued');
            }
            
            $cheque = Cheque::create($data);
            
            // Create payable document
            $document = $this->documentService->createDocument([
                'company_id' => $data['company_id'],
                'branch_id' => $data['branch_id'] ?? null,
                'type' => DocumentType::CHEQUE_PAYABLE,
                'party_id' => $data['party_id'],
                'document_date' => $data['issue_date'],
                'due_date' => $data['due_date'],
                'total_amount' => $data['amount'],
                'cheque_id' => $cheque->id,
                'description' => "Verilen çek: {$cheque->cheque_number}",
            ]);
            
            $cheque->update(['document_id' => $document->id]);
            
            $cheque->recordEvent(ChequeStatus::PENDING_ISSUE, null, null, 'Çek kesildi');
            
            AuditLog::log($cheque, 'create', null, $cheque->toArray());
            
            return $cheque->fresh(['party', 'document', 'bankAccount']);
        });
    }
    
    /**
     * Deposit cheque to bank
     */
    public function depositCheque(Cheque $cheque, int $bankAccountId): Cheque
    {
        return DB::transaction(function () use ($cheque, $bankAccountId) {
            if ($cheque->type !== 'received') {
                throw new \Exception('Sadece alınan çekler bankaya verilebilir.');
            }
            
            if ($cheque->status !== ChequeStatus::IN_PORTFOLIO) {
                throw new \Exception('Sadece portföydeki çekler bankaya verilebilir.');
            }
            
            $oldValues = $cheque->toArray();
            
            $cheque->deposit($bankAccountId, 'Bankaya verildi');
            
            AuditLog::log($cheque, 'status_change', $oldValues, $cheque->toArray());
            
            return $cheque;
        });
    }
    
    /**
     * Collect cheque (mark as paid/collected)
     */
    public function collectCheque(Cheque $cheque, ?int $bankAccountId = null): Cheque
    {
        return DB::transaction(function () use ($cheque, $bankAccountId) {
            if ($cheque->type !== 'received') {
                throw new \Exception('Sadece alınan çekler tahsil edilebilir.');
            }
            
            if (!in_array($cheque->status, [ChequeStatus::IN_PORTFOLIO, ChequeStatus::DEPOSITED])) {
                throw new \Exception('Bu çek tahsil edilemez.');
            }
            
            $bankAccountId = $bankAccountId ?? $cheque->bank_account_id;
            if (!$bankAccountId) {
                throw new \Exception('Tahsilat için banka hesabı gereklidir.');
            }
            
            // Create bank payment
            $payment = $this->paymentService->createPayment([
                'company_id' => $cheque->company_id,
                'branch_id' => $cheque->branch_id,
                'type' => PaymentType::CHEQUE_IN,
                'direction' => 'in',
                'party_id' => $cheque->party_id,
                'bank_account_id' => $bankAccountId,
                'payment_date' => now()->toDateString(),
                'amount' => $cheque->amount,
                'cheque_id' => $cheque->id,
                'description' => "Çek tahsilat: {$cheque->cheque_number}",
            ]);
            
            $oldValues = $cheque->toArray();
            
            // Update cheque status
            $cheque->markCollected($payment, 'Çek tahsil edildi');
            
            // Settle the cheque document
            if ($cheque->document) {
                $this->allocationService->allocate($payment, [
                    [
                        'document_id' => $cheque->document_id,
                        'amount' => $cheque->amount,
                    ],
                ]);
            }
            
            AuditLog::log($cheque, 'status_change', $oldValues, $cheque->toArray());
            
            return $cheque->fresh(['clearedPayment']);
        });
    }
    
    /**
     * Mark cheque as bounced
     */
    public function bounceCheque(Cheque $cheque, string $reason, float $fee = 0): Cheque
    {
        return DB::transaction(function () use ($cheque, $reason, $fee) {
            if (!in_array($cheque->status, [ChequeStatus::IN_PORTFOLIO, ChequeStatus::DEPOSITED])) {
                throw new \Exception('Bu çek karşılıksız olarak işaretlenemez.');
            }
            
            $oldValues = $cheque->toArray();
            
            $cheque->markBounced($reason, $fee, "Karşılıksız: {$reason}");
            
            // Document remains open (unpaid)
            
            AuditLog::log($cheque, 'status_change', $oldValues, $cheque->toArray());
            
            return $cheque;
        });
    }
    
    /**
     * Endorse cheque to another party
     */
    public function endorseCheque(Cheque $cheque, int $toPartyId, ?string $notes = null): Cheque
    {
        return DB::transaction(function () use ($cheque, $toPartyId, $notes) {
            if ($cheque->type !== 'received') {
                throw new \Exception('Sadece alınan çekler ciro edilebilir.');
            }
            
            if ($cheque->status !== ChequeStatus::IN_PORTFOLIO) {
                throw new \Exception('Sadece portföydeki çekler ciro edilebilir.');
            }
            
            $oldValues = $cheque->toArray();
            
            $cheque->endorse($toPartyId, $notes);
            
            // The original receivable document is now settled (we got value by endorsing)
            // Create a payable to the new party we endorsed to
            if ($cheque->document) {
                // Create an adjustment to settle original document
                $adjustmentDoc = $this->documentService->createDocument([
                    'company_id' => $cheque->company_id,
                    'branch_id' => $cheque->branch_id,
                    'type' => DocumentType::ADJUSTMENT_CREDIT,
                    'party_id' => $cheque->party_id,
                    'document_date' => now()->toDateString(),
                    'total_amount' => $cheque->amount,
                    'description' => "Ciro ile kapama: {$cheque->cheque_number}",
                ]);
            }
            
            AuditLog::log($cheque, 'status_change', $oldValues, $cheque->toArray());
            
            return $cheque->fresh(['endorsedToParty']);
        });
    }
    
    /**
     * Pay an issued cheque (when our cheque is presented)
     */
    public function payIssuedCheque(Cheque $cheque): Cheque
    {
        return DB::transaction(function () use ($cheque) {
            if ($cheque->type !== 'issued') {
                throw new \Exception('Sadece verilen çekler ödenebilir.');
            }
            
            if ($cheque->status !== ChequeStatus::PENDING_ISSUE) {
                throw new \Exception('Bu çek zaten ödenmiş veya iptal edilmiş.');
            }
            
            if (!$cheque->bank_account_id) {
                throw new \Exception('Çek için banka hesabı tanımlanmamış.');
            }
            
            // Create bank payment out
            $payment = $this->paymentService->createPayment([
                'company_id' => $cheque->company_id,
                'branch_id' => $cheque->branch_id,
                'type' => PaymentType::CHEQUE_OUT,
                'direction' => 'out',
                'party_id' => $cheque->party_id,
                'bank_account_id' => $cheque->bank_account_id,
                'payment_date' => now()->toDateString(),
                'amount' => $cheque->amount,
                'cheque_id' => $cheque->id,
                'description' => "Çek ödeme: {$cheque->cheque_number}",
            ]);
            
            $oldValues = $cheque->toArray();
            
            $cheque->update([
                'status' => ChequeStatus::PAID,
                'cleared_payment_id' => $payment->id,
            ]);
            
            $cheque->recordEvent(ChequeStatus::PAID, null, $payment->id, 'Çek ödendi');
            
            // Settle the cheque document
            if ($cheque->document) {
                $this->allocationService->allocate($payment, [
                    [
                        'document_id' => $cheque->document_id,
                        'amount' => $cheque->amount,
                    ],
                ]);
            }
            
            AuditLog::log($cheque, 'status_change', $oldValues, $cheque->toArray());
            
            return $cheque->fresh(['clearedPayment']);
        });
    }
    
    /**
     * Cancel a cheque
     */
    public function cancelCheque(Cheque $cheque, ?string $reason = null): Cheque
    {
        return DB::transaction(function () use ($cheque, $reason) {
            if (in_array($cheque->status, [ChequeStatus::COLLECTED, ChequeStatus::PAID])) {
                throw new \Exception('Tahsil/ödeme yapılmış çek iptal edilemez.');
            }
            
            $oldValues = $cheque->toArray();
            
            $cheque->update([
                'status' => ChequeStatus::CANCELLED,
                'notes' => $cheque->notes 
                    ? $cheque->notes . "\n\nİptal: " . ($reason ?? 'Belirtilmedi')
                    : "İptal: " . ($reason ?? 'Belirtilmedi'),
            ]);
            
            $cheque->recordEvent(ChequeStatus::CANCELLED, null, null, $reason ?? 'İptal edildi');
            
            // Cancel the document too
            if ($cheque->document) {
                $this->documentService->cancelDocument($cheque->document, 'Çek iptal edildi');
            }
            
            AuditLog::log($cheque, 'status_change', $oldValues, $cheque->toArray());
            
            return $cheque;
        });
    }
    
    /**
     * List cheques with filters
     */
    public function listCheques(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Cheque::with(['party', 'bankAccount', 'document'])
            ->scoped($filters['company_id'], $filters['branch_id'] ?? null);
        
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->status($filters['status']);
            }
        }
        
        if (!empty($filters['party_id'])) {
            $query->forParty($filters['party_id']);
        }
        
        if (!empty($filters['bank_account_id'])) {
            $query->where('bank_account_id', $filters['bank_account_id']);
        }
        
        if (!empty($filters['due_start'])) {
            $query->where('due_date', '>=', $filters['due_start']);
        }
        
        if (!empty($filters['due_end'])) {
            $query->where('due_date', '<=', $filters['due_end']);
        }
        
        if (!empty($filters['in_portfolio'])) {
            $query->inPortfolio();
        }
        
        if (!empty($filters['for_forecast'])) {
            $query->forForecast();
        }
        
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('cheque_number', 'like', "%{$filters['search']}%")
                  ->orWhere('drawer_name', 'like', "%{$filters['search']}%")
                  ->orWhere('bank_name', 'like', "%{$filters['search']}%");
            });
        }
        
        $sortBy = $filters['sort_by'] ?? 'due_date';
        $sortDir = $filters['sort_dir'] ?? 'asc';
        $query->orderBy($sortBy, $sortDir);
        
        $perPage = $filters['per_page'] ?? 20;
        
        return $query->paginate($perPage);
    }
}
