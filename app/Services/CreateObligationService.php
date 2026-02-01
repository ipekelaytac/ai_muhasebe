<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentLine;
use App\Models\AccountingPeriod;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CreateObligationService
{
    /**
     * Create a new document (obligation)
     *
     * @param array $data
     * @return Document
     * @throws \Exception
     */
    public function create(array $data): Document
    {
        return DB::transaction(function () use ($data) {
            // Validate period is not locked
            $period = AccountingPeriod::findOrCreateForDate(
                $data['company_id'],
                $data['branch_id'],
                $data['document_date']
            );

            if ($period->isLocked()) {
                throw new \Exception('Cannot create document in locked period');
            }

            // Create document
            $document = Document::create([
                'company_id' => $data['company_id'],
                'branch_id' => $data['branch_id'],
                'accounting_period_id' => $period->id,
                'document_number' => $data['document_number'] ?? $this->generateDocumentNumber($data),
                'document_type' => $data['document_type'],
                'direction' => $data['direction'],
                'status' => $data['status'] ?? 'posted',
                'party_id' => $data['party_id'],
                'document_date' => $data['document_date'],
                'due_date' => $data['due_date'] ?? $data['document_date'],
                'total_amount' => $data['total_amount'],
                'paid_amount' => 0,
                'unpaid_amount' => $data['total_amount'],
                'category_id' => $data['category_id'] ?? null,
                'description' => $data['description'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'created_by' => Auth::id(),
            ]);

            // Create document lines if provided
            if (isset($data['lines']) && is_array($data['lines'])) {
                foreach ($data['lines'] as $index => $lineData) {
                    DocumentLine::create([
                        'document_id' => $document->id,
                        'line_number' => $index + 1,
                        'category_id' => $lineData['category_id'] ?? null,
                        'description' => $lineData['description'] ?? null,
                        'quantity' => $lineData['quantity'] ?? null,
                        'unit_price' => $lineData['unit_price'] ?? null,
                        'amount' => $lineData['amount'] ?? $lineData['quantity'] * $lineData['unit_price'] ?? 0,
                        'tax_rate' => $lineData['tax_rate'] ?? 0,
                        'tax_amount' => $lineData['tax_amount'] ?? 0,
                        'metadata' => $lineData['metadata'] ?? null,
                    ]);
                }

                // Recalculate total from lines if lines provided
                $totalFromLines = $document->lines->sum('amount');
                if (abs($totalFromLines - $document->total_amount) > 0.01) {
                    $document->total_amount = $totalFromLines;
                    $document->unpaid_amount = $totalFromLines;
                    $document->save();
                }
            }

            // Log audit
            AuditLog::create([
                'company_id' => $document->company_id,
                'branch_id' => $document->branch_id,
                'auditable_type' => Document::class,
                'auditable_id' => $document->id,
                'user_id' => Auth::id(),
                'event' => 'created',
                'new_values' => $document->toArray(),
                'description' => "Document {$document->document_number} created",
            ]);

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

        $lastDoc = Document::where('company_id', $data['company_id'])
            ->where('branch_id', $data['branch_id'])
            ->where('document_type', $data['document_type'])
            ->whereYear('document_date', $year)
            ->whereMonth('document_date', $date->month)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastDoc ? (int) substr($lastDoc->document_number, -4) + 1 : 1;

        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $sequence);
    }
}
