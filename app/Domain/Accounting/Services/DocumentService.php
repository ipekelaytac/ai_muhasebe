<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Enums\DocumentStatus;
use App\Domain\Accounting\Enums\DocumentType;
use App\Domain\Accounting\Models\AuditLog;
use App\Domain\Accounting\Models\Document;
use App\Domain\Accounting\Models\DocumentLine;
use Illuminate\Support\Facades\DB;

class DocumentService
{
    protected PeriodService $periodService;
    
    public function __construct(PeriodService $periodService)
    {
        $this->periodService = $periodService;
    }
    
    /**
     * Create a new document (obligation/accrual)
     */
    public function createDocument(array $data): Document
    {
        $maxRetries = 10;
        $attempt = 0;
        
        while ($attempt < $maxRetries) {
            try {
                return DB::transaction(function () use ($data) {
                    // Validate period is open
                    $this->periodService->validatePeriodOpen(
                        $data['company_id'],
                        $data['document_date']
                    );
                    
                    // Auto-determine direction if not provided
                    if (empty($data['direction'])) {
                        $data['direction'] = DocumentType::getDirection($data['type']);
                    }
                    
                    // Generate document number if not provided (required)
                    if (empty($data['document_number'])) {
                        $data['document_number'] = Document::generateNumber(
                            $data['company_id'],
                            $data['branch_id'] ?? null,
                            $data['type']
                        );
                    }
                    
                    // Set default status
                    if (empty($data['status'])) {
                        $data['status'] = DocumentStatus::PENDING;
                    }
                    
                    // Extract lines if provided
                    $lines = $data['lines'] ?? [];
                    unset($data['lines']);
                    
                    // Create document
                    $document = Document::create($data);
                    
                    // Create lines if provided
                    if (!empty($lines)) {
                        foreach ($lines as $i => $lineData) {
                            $lineData['document_id'] = $document->id;
                            $lineData['line_number'] = $i + 1;
                            
                            $line = new DocumentLine($lineData);
                            $line->calculateTotals();
                            $line->save();
                        }
                    }
                    
                    AuditLog::log($document, 'create', null, $document->toArray());
                    
                    return $document->fresh(['party', 'category', 'lines']);
                });
            } catch (\Illuminate\Database\QueryException $e) {
                // Check if it's a duplicate entry error for document_number
                if ($e->getCode() == 23000 && (str_contains($e->getMessage(), 'unique_document_number') || str_contains($e->getMessage(), 'Duplicate entry'))) {
                    $attempt++;
                    
                    \Log::warning("Document number duplicate detected (attempt {$attempt}/{$maxRetries}), regenerating...", [
                        'company_id' => $data['company_id'] ?? null,
                        'branch_id' => $data['branch_id'] ?? null,
                        'type' => $data['type'] ?? null,
                        'document_number' => $data['document_number'] ?? null,
                    ]);
                    
                    if ($attempt >= $maxRetries) {
                        \Log::error("Document creation failed after {$maxRetries} attempts due to duplicate document numbers", [
                            'company_id' => $data['company_id'] ?? null,
                            'type' => $data['type'] ?? null,
                        ]);
                        
                        throw new \Exception(
                            "Belge numarası çakışması nedeniyle belge oluşturulamadı. " .
                            "Lütfen sayfayı yenileyip tekrar deneyin."
                        );
                    }
                    
                    // Clear document_number to regenerate
                    unset($data['document_number']);
                    
                    // Small delay before retry to avoid immediate collision
                    usleep(100000 * $attempt); // 100ms * attempt number
                    
                    continue; // Retry the loop
                }
                
                // Re-throw if it's not a duplicate entry error
                throw $e;
            }
        }
        
        throw new \Exception("Belge oluşturulamadı. Maksimum deneme sayısına ulaşıldı.");
    }
    
    /**
     * Update a document
     */
    public function updateDocument(Document $document, array $data): Document
    {
        return DB::transaction(function () use ($document, $data) {
            // Check if document can be modified
            if (!$document->canModify()) {
                throw new \Exception('Bu belge değiştirilemez. Dönem kilitli veya belge kapalı.');
            }
            
            // Check if document has allocations
            if ($document->activeAllocations()->exists()) {
                throw new \Exception('Ödemesi olan belge değiştirilemez.');
            }
            
            $oldValues = $document->toArray();
            
            // If changing document date, validate new period
            if (isset($data['document_date']) && $data['document_date'] != $document->document_date) {
                $this->periodService->validatePeriodOpen(
                    $document->company_id,
                    $data['document_date']
                );
            }
            
            // Extract lines if provided
            $lines = $data['lines'] ?? null;
            unset($data['lines']);
            
            $document->update($data);
            
            // Update lines if provided
            if ($lines !== null) {
                // Delete existing lines
                $document->lines()->delete();
                
                // Create new lines
                foreach ($lines as $i => $lineData) {
                    $lineData['document_id'] = $document->id;
                    $lineData['line_number'] = $i + 1;
                    
                    $line = new DocumentLine($lineData);
                    $line->calculateTotals();
                    $line->save();
                }
            }
            
            AuditLog::log($document, 'update', $oldValues, $document->fresh()->toArray());
            
            return $document->fresh(['party', 'category', 'lines']);
        });
    }
    
    /**
     * Cancel a document
     */
    public function cancelDocument(Document $document, ?string $reason = null): Document
    {
        return DB::transaction(function () use ($document, $reason) {
            if ($document->status === DocumentStatus::CANCELLED) {
                throw new \Exception('Belge zaten iptal edilmiş.');
            }
            
            if ($document->status === DocumentStatus::SETTLED) {
                throw new \Exception('Kapatılmış belge iptal edilemez. Önce ödemeleri iptal edin.');
            }
            
            // Check for active allocations
            if ($document->activeAllocations()->exists()) {
                throw new \Exception('Ödemesi olan belge iptal edilemez. Önce kapamaları iptal edin.');
            }
            
            // Validate period
            $this->periodService->validatePeriodOpen(
                $document->company_id,
                $document->document_date
            );
            
            $oldValues = $document->toArray();
            
            $document->update([
                'status' => DocumentStatus::CANCELLED,
                'notes' => $document->notes 
                    ? $document->notes . "\n\nİptal nedeni: " . ($reason ?? 'Belirtilmedi')
                    : "İptal nedeni: " . ($reason ?? 'Belirtilmedi'),
            ]);
            
            AuditLog::log($document, 'status_change', $oldValues, $document->toArray());
            
            return $document;
        });
    }
    
    /**
     * Reverse a document (create a reversal document)
     * Used when document is in a locked period
     */
    public function reverseDocument(Document $document, ?string $reason = null): Document
    {
        return DB::transaction(function () use ($document, $reason) {
            if ($document->status === DocumentStatus::CANCELLED || $document->status === DocumentStatus::REVERSED) {
                throw new \Exception('Bu belge zaten iptal/ters kayıt edilmiş.');
            }
            
            // Reversals must only be created in an open period
            $this->periodService->validatePeriodOpen($document->company_id, now()->toDateString());
            
            // Cancel active allocations first
            foreach ($document->activeAllocations as $allocation) {
                $allocation->cancel();
            }
            
            // Create reversal document
            $reversalData = $document->toArray();
            unset($reversalData['id'], $reversalData['created_at'], $reversalData['updated_at'], $reversalData['deleted_at']);
            
            // Set reversal properties
            $reversalData['document_date'] = now()->toDateString();
            $reversalData['document_number'] = Document::generateNumber(
                $document->company_id,
                $document->branch_id,
                $document->type
            );
            $reversalData['reversed_document_id'] = $document->id;
            $reversalData['status'] = DocumentStatus::REVERSED;
            $reversalData['total_amount'] = -$document->total_amount; // Negative amount
            $reversalData['description'] = "Ters kayıt: {$document->document_number}" . ($reason ? " - {$reason}" : '');
            
            $reversalDocument = Document::create($reversalData);
            
            // Mark original as reversed
            $oldValues = $document->toArray();
            $document->update([
                'status' => DocumentStatus::REVERSED,
                'reversal_document_id' => $reversalDocument->id,
            ]);
            
            AuditLog::log($document, 'status_change', $oldValues, $document->toArray());
            AuditLog::log($reversalDocument, 'create', null, $reversalDocument->toArray());
            
            return $reversalDocument;
        });
    }
    
    /**
     * Get document with all relations
     */
    public function getDocument(int $id): Document
    {
        return Document::with([
            'party',
            'category',
            'lines',
            'activeAllocations.payment',
            'attachments',
            'reversedDocument',
            'reversalDocument',
        ])->findOrFail($id);
    }
    
    /**
     * List documents with filters
     */
    public function listDocuments(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Document::with(['party', 'category'])
            ->scoped($filters['company_id'], $filters['branch_id'] ?? null);
        
        if (!empty($filters['type'])) {
            $query->ofType($filters['type']);
        }
        
        if (!empty($filters['types'])) {
            $query->ofTypes($filters['types']);
        }
        
        if (!empty($filters['direction'])) {
            $query->where('direction', $filters['direction']);
        }
        
        if (!empty($filters['status'])) {
            $query->status($filters['status']);
        }
        
        if (!empty($filters['party_id'])) {
            $query->forParty($filters['party_id']);
        }
        
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        
        if (!empty($filters['start_date'])) {
            $query->where('document_date', '>=', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $query->where('document_date', '<=', $filters['end_date']);
        }
        
        if (!empty($filters['due_start'])) {
            $query->where('due_date', '>=', $filters['due_start']);
        }
        
        if (!empty($filters['due_end'])) {
            $query->where('due_date', '<=', $filters['due_end']);
        }
        
        if (!empty($filters['open_only'])) {
            $query->open();
        }
        
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('document_number', 'like', "%{$filters['search']}%")
                  ->orWhere('reference_number', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }
        
        $sortBy = $filters['sort_by'] ?? 'document_date';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);
        
        $perPage = $filters['per_page'] ?? 20;
        
        return $query->paginate($perPage);
    }
}
