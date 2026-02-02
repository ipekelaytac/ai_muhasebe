<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentRequest;
use App\Domain\Accounting\Models\Document;
use App\Domain\Accounting\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DocumentController extends Controller
{
    protected DocumentService $documentService;

    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    /**
     * Display a listing of documents
     */
    public function index(Request $request): JsonResponse
    {
        $query = Document::query()
            ->forCompany($request->get('company_id'))
            ->forBranch($request->get('branch_id'));

        if ($request->has('document_type')) {
            $query->byDocumentType($request->get('document_type'));
        }

        if ($request->has('direction')) {
            $query->where('direction', $request->get('direction'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('party_id')) {
            $query->where('party_id', $request->get('party_id'));
        }

        if ($request->has('unpaid_only')) {
            $query->unpaid();
        }

        if ($request->has('overdue_only')) {
            $query->overdue();
        }

        $documents = $query->with(['party', 'category', 'accountingPeriod', 'lines'])
            ->orderBy('document_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($documents);
    }

    /**
     * Store a newly created document
     */
    public function store(StoreDocumentRequest $request): JsonResponse
    {
        try {
            $document = $this->documentService->createDocument($request->validated());

            return response()->json($document->load(['party', 'category', 'lines']), 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display the specified document
     */
    public function show(Document $document): JsonResponse
    {
        $document->load([
            'party',
            'category',
            'lines',
            'allocations.payment',
            'accountingPeriod',
            'reversalDocuments',
            'originalDocument'
        ]);

        return response()->json($document);
    }

    /**
     * Update the specified document
     */
    public function update(StoreDocumentRequest $request, Document $document): JsonResponse
    {
        try {
            // Check period lock
            if ($document->isInLockedPeriod()) {
                return response()->json([
                    'message' => 'Bu belge kilitli bir dönemde. Düzenleme yapılamaz. Ters kayıt kullanın.'
                ], 422);
            }

            // Check if document can be modified
            if (!$document->canModify()) {
                return response()->json([
                    'message' => 'Bu belge değiştirilemez. Ödemesi olan belgeler değiştirilemez.'
                ], 422);
            }

            $document = $this->documentService->updateDocument($document, $request->validated());

            return response()->json($document->load(['party', 'category', 'lines']));
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Reverse the specified document
     */
    public function reverse(Request $request, Document $document): JsonResponse
    {
        try {
            $reversalDocument = $this->documentService->reverseDocument(
                $document,
                $request->get('reason')
            );

            return response()->json([
                'message' => 'Document reversed successfully',
                'reversal_document' => $reversalDocument
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove the specified document (soft delete)
     */
    public function destroy(Document $document): JsonResponse
    {
        try {
            // Check period lock
            if ($document->isInLockedPeriod()) {
                return response()->json([
                    'message' => 'Bu belge kilitli bir dönemde. Silinemez. İptal edin veya ters kayıt kullanın.'
                ], 422);
            }

            // Use service to cancel (soft delete)
            $this->documentService->cancelDocument($document, 'API üzerinden silindi');

            return response()->json(['message' => 'Document cancelled successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
