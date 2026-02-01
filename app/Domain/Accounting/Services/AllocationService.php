<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Enums\DocumentStatus;
use App\Domain\Accounting\Enums\DocumentType;
use App\Domain\Accounting\Models\AuditLog;
use App\Domain\Accounting\Models\Document;
use App\Domain\Accounting\Models\Payment;
use App\Domain\Accounting\Models\PaymentAllocation;
use Illuminate\Support\Facades\DB;

class AllocationService
{
    protected PeriodService $periodService;
    protected DocumentService $documentService;
    
    public function __construct(PeriodService $periodService, DocumentService $documentService)
    {
        $this->periodService = $periodService;
        $this->documentService = $documentService;
    }
    
    /**
     * Allocate payment to document(s)
     */
    public function allocate(Payment $payment, array $allocations): array
    {
        return DB::transaction(function () use ($payment, $allocations) {
            // Validate payment
            if ($payment->status !== 'confirmed') {
                throw new \Exception('Sadece onaylanmış ödemeler dağıtılabilir.');
            }
            
            $availableAmount = $payment->unallocated_amount;
            if ($availableAmount <= 0) {
                throw new \Exception('Bu ödemenin tamamı zaten dağıtılmış.');
            }
            
            $createdAllocations = [];
            $totalAllocated = 0;
            
            foreach ($allocations as $allocationData) {
                $document = Document::findOrFail($allocationData['document_id']);
                $amount = $allocationData['amount'];
                
                // Validate document belongs to same party
                if ($payment->party_id && $document->party_id !== $payment->party_id) {
                    throw new \Exception("Belge farklı bir cariye ait: {$document->document_number}");
                }
                
                // Validate direction compatibility
                $this->validateDirectionCompatibility($payment, $document);
                
                // Validate amount
                $unpaidAmount = $document->unpaid_amount;
                if ($amount > $unpaidAmount) {
                    throw new \Exception("Dağıtım tutarı belgenin kalan borcundan fazla: {$document->document_number} (Kalan: {$unpaidAmount})");
                }
                
                if ($totalAllocated + $amount > $availableAmount) {
                    throw new \Exception('Dağıtım toplamı ödeme tutarını aşıyor.');
                }
                
                // Create allocation
                $allocation = PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'document_id' => $document->id,
                    'amount' => $amount,
                    'allocation_date' => $allocationData['allocation_date'] ?? now()->toDateString(),
                    'notes' => $allocationData['notes'] ?? null,
                    'status' => 'active',
                ]);
                
                $createdAllocations[] = $allocation;
                $totalAllocated += $amount;
                
                // Update document status
                $document->updateStatus();
                
                AuditLog::log($allocation, 'create', null, $allocation->toArray());
            }
            
            return $createdAllocations;
        });
    }
    
    /**
     * Allocate payment with automatic distribution
     * Allocates to oldest unpaid documents first (FIFO)
     */
    public function autoAllocate(Payment $payment, ?int $partyId = null): array
    {
        return DB::transaction(function () use ($payment, $partyId) {
            $availableAmount = $payment->unallocated_amount;
            if ($availableAmount <= 0) {
                return [];
            }
            
            $partyId = $partyId ?? $payment->party_id;
            if (!$partyId) {
                throw new \Exception('Otomatik dağıtım için cari belirtilmelidir.');
            }
            
            // Get open documents for party, ordered by due date (oldest first)
            $direction = $payment->direction === 'out' ? 'payable' : 'receivable';
            
            $documents = Document::where('party_id', $partyId)
                ->where('direction', $direction)
                ->open()
                ->orderBy('due_date')
                ->orderBy('document_date')
                ->get();
            
            $allocations = [];
            $remaining = $availableAmount;
            
            foreach ($documents as $document) {
                if ($remaining <= 0) {
                    break;
                }
                
                $unpaid = $document->unpaid_amount;
                $toAllocate = min($unpaid, $remaining);
                
                if ($toAllocate > 0) {
                    $allocations[] = [
                        'document_id' => $document->id,
                        'amount' => $toAllocate,
                    ];
                    
                    $remaining -= $toAllocate;
                }
            }
            
            if (empty($allocations)) {
                return [];
            }
            
            return $this->allocate($payment, $allocations);
        });
    }
    
    /**
     * Handle overpayment by creating advance credit document
     */
    public function handleOverpayment(Payment $payment, float $overpaymentAmount): Document
    {
        return DB::transaction(function () use ($payment, $overpaymentAmount) {
            if ($overpaymentAmount <= 0) {
                throw new \Exception('Fazla ödeme tutarı pozitif olmalıdır.');
            }
            
            if (!$payment->party_id) {
                throw new \Exception('Fazla ödeme için cari gereklidir.');
            }
            
            // Determine document type and direction based on payment direction
            // If payment is OUT (we paid them), overpayment creates ADVANCE_GIVEN (receivable - they owe us back)
            // If payment is IN (they paid us), overpayment creates ADVANCE_RECEIVED (payable - we owe them back)
            if ($payment->direction === 'out') {
                // We paid supplier extra → they owe us back (receivable)
                $docType = DocumentType::ADVANCE_GIVEN;
                $docDirection = 'receivable';
            } else {
                // Customer paid us extra → we owe them back (payable)
                $docType = DocumentType::ADVANCE_RECEIVED;
                $docDirection = 'payable';
            }
            
            // Create advance document
            $document = $this->documentService->createDocument([
                'company_id' => $payment->company_id,
                'branch_id' => $payment->branch_id,
                'type' => $docType,
                'direction' => $docDirection, // Explicitly set direction
                'party_id' => $payment->party_id,
                'document_date' => $payment->payment_date,
                'due_date' => null,
                'total_amount' => $overpaymentAmount,
                'description' => "Fazla ödeme: {$payment->payment_number}",
            ]);
            
            // IMPORTANT: We cannot allocate payment to advance document if directions don't match
            // Instead, create a special allocation that represents the overpayment
            // The payment is already fully allocated to original documents
            // The advance document is just a record of what they owe us (or we owe them)
            
            // Note: The payment's unallocated amount should already be 0 after allocating to original documents
            // This method is called AFTER allocating to original documents, so payment is already fully allocated
            // The advance document serves as a record but doesn't need an allocation
            
            return $document;
        });
    }
    
    /**
     * Cancel an allocation
     */
    public function cancelAllocation(PaymentAllocation $allocation, ?string $reason = null): void
    {
        DB::transaction(function () use ($allocation, $reason) {
            if ($allocation->status === 'cancelled') {
                throw new \Exception('Dağıtım zaten iptal edilmiş.');
            }
            
            $oldValues = $allocation->toArray();
            
            $allocation->update([
                'status' => 'cancelled',
                'notes' => $allocation->notes 
                    ? $allocation->notes . "\n\nİptal: " . ($reason ?? 'Belirtilmedi')
                    : "İptal: " . ($reason ?? 'Belirtilmedi'),
            ]);
            
            // Update document status
            $allocation->document->updateStatus();
            
            AuditLog::log($allocation, 'status_change', $oldValues, $allocation->toArray());
        });
    }
    
    /**
     * Cancel all allocations for a payment
     */
    public function cancelPaymentAllocations(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            foreach ($payment->activeAllocations as $allocation) {
                $this->cancelAllocation($allocation, 'Ödeme iptal edildi');
            }
        });
    }
    
    /**
     * Cancel all allocations for a document
     */
    public function cancelDocumentAllocations(Document $document): void
    {
        DB::transaction(function () use ($document) {
            foreach ($document->activeAllocations as $allocation) {
                $this->cancelAllocation($allocation, 'Belge iptal edildi');
            }
        });
    }
    
    /**
     * Validate that payment direction is compatible with document direction
     */
    protected function validateDirectionCompatibility(Payment $payment, Document $document): void
    {
        // Payment out settles payable documents (we pay our debts)
        // Payment in settles receivable documents (we collect what's owed to us)
        
        $expectedDocDirection = $payment->direction === 'out' ? 'payable' : 'receivable';
        
        if ($document->direction !== $expectedDocDirection) {
            $paymentDir = $payment->direction === 'out' ? 'çıkış' : 'giriş';
            $docDir = $document->direction === 'payable' ? 'borç' : 'alacak';
            
            throw new \Exception(
                "Ödeme yönü ({$paymentDir}) belge yönüyle ({$docDir}) uyumsuz: {$document->document_number}"
            );
        }
    }
    
    /**
     * Get allocation suggestions for a payment
     */
    public function getSuggestions(Payment $payment, int $limit = 10): array
    {
        if (!$payment->party_id) {
            return [];
        }
        
        $direction = $payment->direction === 'out' ? 'payable' : 'receivable';
        
        return Document::where('party_id', $payment->party_id)
            ->where('direction', $direction)
            ->open()
            ->orderBy('due_date')
            ->orderBy('document_date')
            ->limit($limit)
            ->get()
            ->map(function ($doc) {
                return [
                    'document_id' => $doc->id,
                    'document_number' => $doc->document_number,
                    'document_date' => $doc->document_date->format('Y-m-d'),
                    'due_date' => $doc->due_date?->format('Y-m-d'),
                    'total_amount' => $doc->total_amount,
                    'unpaid_amount' => $doc->unpaid_amount,
                    'description' => $doc->description,
                    'type_label' => $doc->type_label,
                ];
            })
            ->toArray();
    }
}
