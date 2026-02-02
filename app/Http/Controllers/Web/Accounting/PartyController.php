<?php

namespace App\Http\Controllers\Web\Accounting;

use App\Domain\Accounting\Models\Party;
use App\Domain\Accounting\Services\PartyService;
use App\Domain\Accounting\Services\ReportService;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PartyController extends Controller
{
    protected PartyService $partyService;
    protected ReportService $reportService;
    
    public function __construct(PartyService $partyService, ReportService $reportService)
    {
        $this->partyService = $partyService;
        $this->reportService = $reportService;
    }
    
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $query = Party::where('company_id', $user->company_id);
        
        if ($user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }
        
        // Filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%')
                  ->orWhere('tax_number', 'like', '%' . $request->search . '%');
            });
        }
        
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }
        
        $parties = $query->orderBy('name')->paginate(20);
        
        return view('accounting.parties.index', compact('parties'));
    }
    
    public function create()
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $branches = Branch::where('company_id', $user->company_id)->get();
        if ($user->branch_id) {
            $branches = Branch::where('id', $user->branch_id)->get();
        }
        
        return view('accounting.parties.create', compact('branches'));
    }
    
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'type' => 'required|in:customer,supplier,employee,other,tax_authority,bank',
            'code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'tax_number' => 'nullable|string|max:50',
            'tax_office' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'payment_terms_days' => 'nullable|integer|min:0',
            'credit_limit' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);
        
        $validated['company_id'] = $user->company_id;
        
        // Set default values for non-nullable fields
        if (!isset($validated['payment_terms_days']) || $validated['payment_terms_days'] === null || $validated['payment_terms_days'] === '') {
            $validated['payment_terms_days'] = 0;
        }
        
        if (empty($validated['code'])) {
            $validated['code'] = Party::generateCode($user->company_id, $validated['type']);
        }
        
        if (!isset($validated['is_active'])) {
            $validated['is_active'] = true;
        }
        
        $party = $this->partyService->createParty($validated);
        
        return redirect()->route('accounting.parties.show', $party)
            ->with('success', 'Cari hesap başarıyla oluşturuldu.');
    }
    
    public function show(Party $party)
    {
        $user = Auth::user();
        if ($party->company_id != $user->company_id) {
            abort(403);
        }
        
        // Get party statement
        $statement = $this->reportService->getPartyStatement($party->id);
        
        // Get open documents
        $openDocuments = $party->documents()
            ->whereIn('status', ['pending', 'partial'])
            ->orderBy('due_date')
            ->orderBy('document_date')
            ->get();
        
        return view('accounting.parties.show', compact('party', 'statement', 'openDocuments'));
    }
    
    public function edit(Party $party)
    {
        $user = Auth::user();
        if ($party->company_id != $user->company_id) {
            abort(403);
        }
        
        $branches = Branch::where('company_id', $user->company_id)->get();
        if ($user->branch_id) {
            $branches = Branch::where('id', $user->branch_id)->get();
        }
        
        return view('accounting.parties.edit', compact('party', 'branches'));
    }
    
    public function update(Request $request, Party $party)
    {
        $user = Auth::user();
        if ($party->company_id != $user->company_id) {
            abort(403);
        }
        
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'tax_number' => 'nullable|string|max:50',
            'tax_office' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'payment_terms_days' => 'nullable|integer|min:0',
            'credit_limit' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);
        
        // Set default values for non-nullable fields
        if (!isset($validated['payment_terms_days']) || $validated['payment_terms_days'] === null || $validated['payment_terms_days'] === '') {
            $validated['payment_terms_days'] = 0;
        }
        
        $party = $this->partyService->updateParty($party, $validated);
        
        return redirect()->route('accounting.parties.show', $party)
            ->with('success', 'Cari hesap başarıyla güncellendi.');
    }
}
