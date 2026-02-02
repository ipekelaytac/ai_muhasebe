<?php

namespace App\Http\Controllers\Web\Accounting;

use App\Domain\Accounting\Models\Cheque;
use App\Domain\Accounting\Models\Party;
use App\Domain\Accounting\Services\ChequeService;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChequeController extends Controller
{
    protected ChequeService $chequeService;
    
    public function __construct(ChequeService $chequeService)
    {
        $this->chequeService = $chequeService;
    }
    
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $query = Cheque::where('company_id', $user->company_id);
        
        if ($user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }
        
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('party_id')) {
            $query->where('party_id', $request->party_id);
        }
        
        $cheques = $query->with('party')->orderBy('due_date')->orderBy('created_at', 'desc')->paginate(20);
        // Get parties including employee parties
        $parties = Party::where('company_id', $user->company_id)
            ->active()
            ->orderBy('type')
            ->orderBy('name')
            ->get();
        
        return view('accounting.cheques.index', compact('cheques', 'parties'));
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
        
        // Get parties including employee parties
        $parties = Party::where('company_id', $user->company_id)
            ->active()
            ->orderBy('type')
            ->orderBy('name')
            ->get();
        $bankAccounts = \App\Domain\Accounting\Models\BankAccount::where('company_id', $user->company_id)->active()->get();
        
        return view('accounting.cheques.create', compact('branches', 'parties', 'bankAccounts'));
    }
    
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'type' => 'required|in:received,issued',
            'party_id' => 'required|exists:parties,id',
            'cheque_number' => 'required|string|max:50',
            'bank_name' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
            'notes' => 'nullable|string',
        ]);
        
        $validated['company_id'] = $user->company_id;
        $validated['receive_date'] = $validated['type'] === 'received' ? ($validated['issue_date'] ?? now()) : null;
        
        try {
            $cheque = $this->chequeService->receiveCheque($validated);
            
            return redirect()->route('accounting.cheques.show', $cheque)
                ->with('success', 'Çek başarıyla kaydedildi.');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }
    
    public function show(Cheque $cheque)
    {
        $user = Auth::user();
        if ($cheque->company_id != $user->company_id) {
            abort(403);
        }
        
        $cheque->load(['party', 'bankAccount', 'document', 'clearedPayment', 'events']);
        
        return view('accounting.cheques.show', compact('cheque'));
    }
    
    public function deposit(Request $request, Cheque $cheque)
    {
        $user = Auth::user();
        if ($cheque->company_id != $user->company_id) {
            abort(403);
        }
        
        $validated = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'notes' => 'nullable|string|max:500',
        ]);
        
        try {
            $this->chequeService->depositCheque($cheque, $validated['bank_account_id']);
            
            return back()->with('success', 'Çek bankaya verildi.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
    
    public function collect(Request $request, Cheque $cheque)
    {
        $user = Auth::user();
        if ($cheque->company_id != $user->company_id) {
            abort(403);
        }
        
        $validated = $request->validate([
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
        ]);
        
        try {
            $this->chequeService->collectCheque($cheque, $validated['bank_account_id'] ?? null);
            
            return back()->with('success', 'Çek tahsil edildi ve ödeme kaydedildi.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
    
    public function bounce(Request $request, Cheque $cheque)
    {
        $user = Auth::user();
        if ($cheque->company_id != $user->company_id) {
            abort(403);
        }
        
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
            'fee' => 'nullable|numeric|min:0',
        ]);
        
        try {
            $this->chequeService->bounceCheque($cheque, $validated['reason'], $validated['fee'] ?? 0);
            
            return back()->with('success', 'Çek karşılıksız olarak işaretlendi.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
