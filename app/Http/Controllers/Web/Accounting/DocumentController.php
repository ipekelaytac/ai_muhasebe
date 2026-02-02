<?php

namespace App\Http\Controllers\Web\Accounting;

use App\Domain\Accounting\Enums\DocumentType;
use App\Domain\Accounting\Enums\DocumentStatus;
use App\Domain\Accounting\Models\Document;
use App\Domain\Accounting\Models\Party;
use App\Domain\Accounting\Services\DocumentService;
use App\Domain\Accounting\Services\PeriodService;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentController extends Controller
{
    protected DocumentService $documentService;
    protected PeriodService $periodService;
    
    public function __construct(DocumentService $documentService, PeriodService $periodService)
    {
        $this->documentService = $documentService;
        $this->periodService = $periodService;
    }
    
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $filters = [
            'company_id' => $user->company_id,
            'branch_id' => $user->branch_id,
            'type' => $request->get('type'),
            'direction' => $request->get('direction'),
            'status' => $request->get('status'),
            'party_id' => $request->get('party_id'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
            'open_only' => $request->boolean('open_only'),
            'search' => $request->get('search'),
        ];
        
        $documents = $this->documentService->listDocuments($filters);
        $parties = Party::where('company_id', $user->company_id)->active()->orderBy('name')->get();
        
        return view('accounting.documents.index', compact('documents', 'parties'));
    }
    
    public function create(Request $request)
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $branches = Branch::where('company_id', $user->company_id)->get();
        if ($user->branch_id) {
            $branches = Branch::where('id', $user->branch_id)->get();
        }
        
        $parties = Party::where('company_id', $user->company_id)->active()->orderBy('name')->get();
        $categories = \App\Domain\Accounting\Models\ExpenseCategory::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        $partyId = $request->get('party_id');
        
        return view('accounting.documents.create', compact('branches', 'parties', 'categories', 'partyId'));
    }
    
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'type' => 'required|in:' . implode(',', DocumentType::ALL),
            'party_id' => 'required|exists:parties,id',
            'document_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:document_date',
            'total_amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
            'subtotal' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|exists:expense_categories,id',
            'reference_number' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'lines' => 'nullable|array',
            'lines.*.description' => 'required|string|max:255',
            'lines.*.quantity' => 'nullable|numeric|min:0',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'lines.*.category_id' => 'nullable|exists:expense_categories,id',
        ]);
        
        $validated['company_id'] = $user->company_id;
        
        try {
            $document = $this->documentService->createDocument($validated);
            
            return redirect()->route('accounting.documents.show', $document)
                ->with('success', 'Tahakkuk başarıyla oluşturuldu.');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }
    
    public function show(Document $document)
    {
        $user = Auth::user();
        if ($document->company_id != $user->company_id) {
            abort(403);
        }
        
        $document->load(['party', 'category', 'lines', 'activeAllocations.payment', 'reversedDocument', 'reversalDocument']);
        
        return view('accounting.documents.show', compact('document'));
    }
    
    public function edit(Document $document)
    {
        $user = Auth::user();
        if ($document->company_id != $user->company_id) {
            abort(403);
        }
        
        if (!$document->canModify()) {
            return back()->withErrors(['error' => 'Bu belge değiştirilemez. Dönem kilitli veya belge kapalı.']);
        }
        
        $branches = Branch::where('company_id', $user->company_id)->get();
        // Get parties including employee parties
        $parties = Party::where('company_id', $user->company_id)
            ->active()
            ->orderBy('type')
            ->orderBy('name')
            ->get();
        $categories = \App\Domain\Accounting\Models\ExpenseCategory::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        $document->load('lines');
        
        return view('accounting.documents.edit', compact('document', 'branches', 'parties', 'categories'));
    }
    
    public function update(Request $request, Document $document)
    {
        $user = Auth::user();
        if ($document->company_id != $user->company_id) {
            abort(403);
        }
        
        // Check period lock
        if ($document->isInLockedPeriod()) {
            return back()->withErrors(['error' => 'Bu belge kilitli bir dönemde. Düzenleme yapılamaz. Ters kayıt kullanın.']);
        }
        
        // Check if document can be modified
        if (!$document->canModify()) {
            return back()->withErrors(['error' => 'Bu belge değiştirilemez. Ödemesi olan belgeler değiştirilemez.']);
        }
        
        $validated = $request->validate([
            'due_date' => 'nullable|date|after_or_equal:document_date',
            'category_id' => 'nullable|exists:expense_categories,id',
            'reference_number' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
        
        try {
            $document = $this->documentService->updateDocument($document, $validated);
            
            return redirect()->route('accounting.documents.show', $document)
                ->with('success', 'Belge başarıyla güncellendi.');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }
    
    public function reverse(Request $request, Document $document)
    {
        $user = Auth::user();
        if ($document->company_id != $user->company_id) {
            abort(403);
        }
        
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);
        
        try {
            $reversalDocument = $this->documentService->reverseDocument($document, $validated['reason'] ?? null);
            
            return redirect()->route('accounting.documents.show', $reversalDocument)
                ->with('success', 'Ters kayıt oluşturuldu.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
    
    public function cancel(Request $request, Document $document)
    {
        $user = Auth::user();
        if ($document->company_id != $user->company_id) {
            abort(403);
        }
        
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);
        
        try {
            $document = $this->documentService->cancelDocument($document, $validated['reason'] ?? null);
            
            return redirect()->route('accounting.documents.show', $document)
                ->with('success', 'Belge iptal edildi.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
