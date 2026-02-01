<?php

namespace App\Services;

use App\Models\Document;
use App\Models\AccountingPeriod;
use App\Models\AuditLog;
use App\Services\CreateObligationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReverseDocumentService
{
    protected $createObligationService;

    public function __construct(CreateObligationService $createObligationService)
    {
        $this->createObligationService = $createObligationService;
    }

    /**
     * Reverse a document by creating a reversal document
     *
     * @param Document $document
     * @param string|null $reason
     * @return Document Reversal document
     * @throws \Exception
     */
    public function reverse(Document $document, ?string $reason = null): Document
    {
        return DB::transaction(function () use ($document, $reason) {
            // Validate document can be reversed
            if ($document->status === 'reversed') {
                throw new \Exception('Document is already reversed');
            }

            if ($document->status === 'canceled') {
                throw new \Exception('Cannot reverse canceled document');
            }

            // Check if period is locked - reversals must be in open period
            $currentPeriod = AccountingPeriod::findOrCreateForDate(
                $document->company_id,
                $document->branch_id,
                now()->toDateString()
            );

            if ($currentPeriod->isLocked()) {
                throw new \Exception('Cannot create reversal in locked period');
            }

            // Create reversal document
            $reversalDocument = $this->createObligationService->create([
                'company_id' => $document->company_id,
                'branch_id' => $document->branch_id,
                'document_type' => 'reversal',
                'direction' => $document->direction === 'receivable' ? 'payable' : 'receivable', // Reverse direction
                'status' => 'posted',
                'party_id' => $document->party_id,
                'document_date' => now()->toDateString(),
                'due_date' => now()->toDateString(),
                'total_amount' => $document->unpaid_amount, // Only reverse unpaid amount
                'category_id' => $document->category_id,
                'description' => "Reversal of {$document->document_number}" . ($reason ? ": {$reason}" : ''),
                'metadata' => [
                    'reversal_reason' => $reason,
                    'original_document_id' => $document->id,
                ],
            ]);

            // Link reversal to original
            $reversalDocument->original_document_id = $document->id;
            $reversalDocument->reverses_document_id = $document->id;
            $reversalDocument->save();

            // Mark original as reversed
            $document->status = 'reversed';
            $document->updated_by = Auth::id();
            $document->save();

            // If document has allocations, we need to handle them
            // Option 1: Reverse allocations (create negative allocations)
            // Option 2: Just mark document as reversed and let the reversal document handle the balance
            // We'll go with option 2 for simplicity - the reversal document will offset the balance

            // Log audit
            AuditLog::create([
                'company_id' => $document->company_id,
                'branch_id' => $document->branch_id,
                'auditable_type' => Document::class,
                'auditable_id' => $document->id,
                'user_id' => Auth::id(),
                'event' => 'reversed',
                'old_values' => ['status' => 'posted'],
                'new_values' => ['status' => 'reversed', 'reversal_document_id' => $reversalDocument->id],
                'description' => "Document {$document->document_number} reversed by {$reversalDocument->document_number}",
            ]);

            return $reversalDocument;
        });
    }
}
