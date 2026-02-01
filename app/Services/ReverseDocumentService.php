<?php

namespace App\Services;

use App\Models\Document;
use App\Models\AccountingPeriod;
use App\Models\AuditLog;
use App\Services\CreateObligationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

            // Check if period is locked - reversals must be in open period (periods are company-level only)
            $currentPeriod = AccountingPeriod::findOrCreateForDate(
                $document->company_id,
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
                'status' => 'pending', // Schema uses 'pending', not 'posted'
                'party_id' => $document->party_id,
                'document_date' => now()->toDateString(),
                'due_date' => now()->toDateString(),
                'total_amount' => $document->unpaid_amount, // Only reverse unpaid amount
                'category_id' => $document->category_id,
                'description' => "Reversal of {$document->document_number}" . ($reason ? ": {$reason}" : ''),
                // Schema does NOT have metadata column - store reversal info in description/notes if needed
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

            // Log audit (schema uses 'action' not 'event', no branch_id/description, only created_at not updated_at)
            $auditData = [
                'company_id' => $document->company_id,
                'auditable_type' => Document::class,
                'auditable_id' => $document->id,
                'action' => 'status_change', // Schema uses 'action' enum, not 'event'
                'old_values' => ['status' => $document->status], // Use actual document status
                'new_values' => ['status' => 'reversed', 'reversal_document_id' => $reversalDocument->id],
                'user_id' => Auth::id(),
                'created_at' => now(), // Schema only has created_at, not updated_at
            ];
            // Filter to only existing columns (schema does NOT have branch_id/description/event/updated_at)
            $auditData = $this->filterByExistingColumns('audit_logs', $auditData);
            AuditLog::create($auditData);

            return $reversalDocument;
        });
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
