<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentRequest;
use App\Models\Document;
use App\Services\CreateObligationService;
use App\Services\ReverseDocumentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DocumentController extends Controller
{
    protected $createObligationService;
    protected $reverseDocumentService;

    public function __construct(
        CreateObligationService $createObligationService,
        ReverseDocumentService $reverseDocumentService
    ) {
        $this->createObligationService = $createObligationService;
        $this->reverseDocumentService = $reverseDocumentService;
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
            $document = $this->createObligationService->create($request->validated());

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
            if ($document->isLocked()) {
                return response()->json([
                    'message' => 'Cannot update document in locked period'
                ], 422);
            }

            if ($document->status !== 'draft') {
                return response()->json([
                    'message' => 'Can only update draft documents'
                ], 422);
            }

            $document->update($request->validated());

            return response()->json($document->fresh()->load(['party', 'category', 'lines']));
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
            $reversalDocument = $this->reverseDocumentService->reverse(
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
            if ($document->isLocked()) {
                return response()->json([
                    'message' => 'Cannot delete document in locked period'
                ], 422);
            }

            if ($document->allocations()->count() > 0) {
                return response()->json([
                    'message' => 'Cannot delete document with allocations'
                ], 422);
            }

            $document->status = 'canceled';
            $document->save();
            $document->delete();

            return response()->json(['message' => 'Document deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
