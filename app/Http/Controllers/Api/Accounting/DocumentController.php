<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Domain\Accounting\Enums\DocumentType;
use App\Domain\Accounting\Enums\DocumentStatus;
use App\Domain\Accounting\Models\Document;
use App\Domain\Accounting\Services\DocumentService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    protected DocumentService $documentService;
    
    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }
    
    /**
     * List documents
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'type' => 'nullable|string|in:' . implode(',', DocumentType::ALL),
            'types' => 'nullable|array',
            'types.*' => 'string|in:' . implode(',', DocumentType::ALL),
            'direction' => 'nullable|string|in:payable,receivable',
            'status' => 'nullable|string|in:' . implode(',', DocumentStatus::ALL),
            'party_id' => 'nullable|integer|exists:parties,id',
            'category_id' => 'nullable|integer|exists:expense_categories,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'due_start' => 'nullable|date',
            'due_end' => 'nullable|date',
            'open_only' => 'nullable|boolean',
            'search' => 'nullable|string|max:100',
            'sort_by' => 'nullable|string|in:document_date,due_date,total_amount,created_at',
            'sort_dir' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);
        
        $documents = $this->documentService->listDocuments($validated);
        
        return response()->json([
            'success' => true,
            'data' => $documents,
        ]);
    }
    
    /**
     * Create document (tahakkuk)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'type' => 'required|string|in:' . implode(',', DocumentType::ALL),
            'party_id' => 'required|integer|exists:parties,id',
            'document_date' => 'required|date',
            'due_date' => 'nullable|date',
            'total_amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
            'exchange_rate' => 'nullable|numeric|min:0',
            'subtotal' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|integer|exists:expense_categories,id',
            'tags' => 'nullable|array',
            'reference_number' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'lines' => 'nullable|array',
            'lines.*.description' => 'required|string|max:255',
            'lines.*.quantity' => 'nullable|numeric|min:0',
            'lines.*.unit' => 'nullable|string|max:20',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'lines.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'lines.*.category_id' => 'nullable|integer|exists:expense_categories,id',
        ]);
        
        try {
            $document = $this->documentService->createDocument($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Belge oluşturuldu.',
                'data' => $document,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Get document details
     */
    public function show(int $id): JsonResponse
    {
        try {
            $document = $this->documentService->getDocument($id);
            
            return response()->json([
                'success' => true,
                'data' => $document,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Belge bulunamadı.',
            ], 404);
        }
    }
    
    /**
     * Update document
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $document = Document::findOrFail($id);
        
        $validated = $request->validate([
            'due_date' => 'nullable|date',
            'category_id' => 'nullable|integer|exists:expense_categories,id',
            'tags' => 'nullable|array',
            'reference_number' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
        
        try {
            $document = $this->documentService->updateDocument($document, $validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Belge güncellendi.',
                'data' => $document,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Cancel document
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $document = Document::findOrFail($id);
        
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);
        
        try {
            $document = $this->documentService->cancelDocument($document, $validated['reason'] ?? null);
            
            return response()->json([
                'success' => true,
                'message' => 'Belge iptal edildi.',
                'data' => $document,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Reverse document (create reversal entry)
     */
    public function reverse(Request $request, int $id): JsonResponse
    {
        $document = Document::findOrFail($id);
        
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);
        
        try {
            $reversalDocument = $this->documentService->reverseDocument($document, $validated['reason'] ?? null);
            
            return response()->json([
                'success' => true,
                'message' => 'Ters kayıt oluşturuldu.',
                'data' => $reversalDocument,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
