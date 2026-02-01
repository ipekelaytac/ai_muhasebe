<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentLine;
use App\Models\AccountingPeriod;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

/**
 * @deprecated This service is deprecated. Use App\Domain\Accounting\Services\DocumentService instead.
 * This class is kept for backward compatibility during migration only.
 */
class CreateObligationService
{
    /**
     * Create a new document (obligation)
     *
     * @param array $data
     * @return Document
     * @throws \Exception
     * @deprecated Use App\Domain\Accounting\Services\DocumentService::createDocument() instead
     */
    public function create(array $data): Document
    {
        // Delegate to Domain service
        $documentService = app(\App\Domain\Accounting\Services\DocumentService::class);
        
        // Map old data format to new format
        $newData = [
            'company_id' => $data['company_id'],
            'branch_id' => $data['branch_id'] ?? null,
            'type' => $data['document_type'] ?? $data['type'] ?? 'expense_due',
            'direction' => $data['direction'] ?? 'payable',
            'party_id' => $data['party_id'],
            'document_date' => $data['document_date'],
            'due_date' => $data['due_date'] ?? $data['document_date'],
            'total_amount' => $data['total_amount'],
            'category_id' => $data['category_id'] ?? null,
            'description' => $data['description'] ?? null,
            'lines' => $data['lines'] ?? [],
        ];
        
        return $documentService->createDocument($newData);
    }
    
    /**
     * @deprecated This method is deprecated. Use App\Domain\Accounting\Services\DocumentService instead.
     */
    private function createLegacy(array $data): Document
    {
        return DB::transaction(function () use ($data) {
            // Validate period is not locked (periods are company-level only)
            $period = AccountingPeriod::findOrCreateForDate(
                $data['company_id'],
                $data['document_date']
            );

            if ($period->isLocked()) {
                throw new \Exception('Cannot create document in locked period');
            }

            // Extract period year/month from document date
            $docDate = Carbon::parse($data['document_date']);
            
            // Build document data array (only include columns that exist in schema)
            $documentData = [
                'company_id' => $data['company_id'],
                'branch_id' => $data['branch_id'],
                'document_number' => $data['document_number'] ?? $this->generateDocumentNumber($data),
                'type' => $data['document_type'], // Map document_type input to type column
                'direction' => $data['direction'],
                'status' => $data['status'] ?? 'pending', // Schema default is 'pending', not 'posted'
                'party_id' => $data['party_id'],
                'document_date' => $data['document_date'],
                'due_date' => $data['due_date'] ?? $data['document_date'],
                'period_year' => $docDate->year, // Schema uses period_year/month, not FK
                'period_month' => $docDate->month,
                'total_amount' => $data['total_amount'],
                'category_id' => $data['category_id'] ?? null,
                'description' => $data['description'] ?? null,
                'created_by' => Auth::id(),
            ];
            
            // Filter to only existing columns (safety check)
            $documentData = $this->filterByExistingColumns('documents', $documentData);
            
            // Create document (schema uses period_year/month, NOT accounting_period_id FK)
            // Schema does NOT have paid_amount/unpaid_amount/metadata columns - these are calculated
            $document = Document::create($documentData);

            // Create document lines if provided
            if (isset($data['lines']) && is_array($data['lines'])) {
                foreach ($data['lines'] as $index => $lineData) {
                    // Calculate values based on schema columns
                    $quantity = $lineData['quantity'] ?? 1;
                    $unitPrice = $lineData['unit_price'] ?? 0;
                    $discountPercent = $lineData['discount_percent'] ?? 0;
                    $discountAmount = $lineData['discount_amount'] ?? ($quantity * $unitPrice * $discountPercent / 100);
                    $subtotal = ($quantity * $unitPrice) - $discountAmount;
                    $taxRate = $lineData['tax_rate'] ?? 0;
                    $taxAmount = $lineData['tax_amount'] ?? ($subtotal * $taxRate / 100);
                    $total = $subtotal + $taxAmount;
                    
                    // Build line data (only include columns that exist in schema)
                    $lineDataArray = [
                        'document_id' => $document->id,
                        'line_number' => $index + 1,
                        'description' => $lineData['description'] ?? '',
                        'quantity' => $quantity,
                        'unit' => $lineData['unit'] ?? null,
                        'unit_price' => $unitPrice,
                        'discount_percent' => $discountPercent,
                        'discount_amount' => $discountAmount,
                        'subtotal' => $subtotal, // Schema uses subtotal, not amount
                        'tax_rate' => $taxRate,
                        'tax_amount' => $taxAmount,
                        'total' => $total,
                        'category_id' => $lineData['category_id'] ?? null,
                    ];
                    
                    // Filter to only existing columns (schema does NOT have metadata)
                    $lineDataArray = $this->filterByExistingColumns('document_lines', $lineDataArray);
                    
                    DocumentLine::create($lineDataArray);
                }

                // Recalculate total from lines if lines provided
                $totalFromLines = $document->lines->sum('amount');
                if (abs($totalFromLines - $document->total_amount) > 0.01) {
                    $document->total_amount = $totalFromLines;
                    // Schema does NOT have unpaid_amount column - it's calculated
                    $document->save();
                }
            }

            // Log audit (schema uses 'action' not 'event', no branch_id/description, only created_at not updated_at)
            $auditData = [
                'company_id' => $document->company_id,
                'auditable_type' => Document::class,
                'auditable_id' => $document->id,
                'action' => 'create', // Schema uses 'action' enum, not 'event'
                'new_values' => $document->toArray(),
                'user_id' => Auth::id(),
                'created_at' => now(), // Schema only has created_at, not updated_at
            ];
            // Filter to only existing columns (schema does NOT have branch_id/description/event/updated_at)
            $auditData = $this->filterByExistingColumns('audit_logs', $auditData);
            AuditLog::create($auditData);

            return $document->fresh();
        });
    }

    /**
     * Generate document number if not provided
     */
    private function generateDocumentNumber(array $data): string
    {
        $prefix = strtoupper(substr($data['document_type'], 0, 3));
        $date = Carbon::parse($data['document_date']);
        $year = $date->year;
        $month = str_pad($date->month, 2, '0', STR_PAD_LEFT);

        // Query using 'type' column as per schema (not 'document_type')
        // Unique constraint is on company_id + document_number (NOT branch_id)
        // Query by document_number pattern to find the highest sequence number
        $pattern = sprintf('%s-%s%s-%%', $prefix, $year, $month);
        
        $lastDoc = Document::where('company_id', $data['company_id'])
            ->where('type', $data['document_type']) // Use 'type' column
            ->where('document_number', 'like', $pattern)
            ->orderBy('document_number', 'desc')
            ->first();

        if ($lastDoc) {
            // Extract sequence from document_number (last 4 digits)
            $lastNumber = $lastDoc->document_number;
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
