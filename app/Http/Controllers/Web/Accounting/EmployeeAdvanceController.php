<?php

namespace App\Http\Controllers\Web\Accounting;

use App\Domain\Accounting\Enums\DocumentType;
use App\Domain\Accounting\Models\Document;
use App\Domain\Accounting\Models\Party;
use App\Domain\Accounting\Services\EmployeeAdvanceService;
use App\Domain\Accounting\Services\PeriodService;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeAdvanceController extends Controller
{
    protected EmployeeAdvanceService $advanceService;
    protected PeriodService $periodService;
    
    public function __construct(
        EmployeeAdvanceService $advanceService,
        PeriodService $periodService
    ) {
        $this->advanceService = $advanceService;
        $this->periodService = $periodService;
    }
    
    /**
     * List advances for an employee
     */
    public function index(Party $party)
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        // Validate party is employee
        if ($party->type !== 'employee') {
            abort(404, 'Bu sayfa sadece personeller için kullanılabilir.');
        }
        
        // Validate party belongs to user's company
        if ($party->company_id !== $user->company_id) {
            abort(403, 'Bu cariye erişim yetkiniz yok.');
        }
        
        // Get advances
        $advances = Document::where('party_id', $party->id)
            ->where('type', DocumentType::ADVANCE_GIVEN)
            ->orderBy('document_date', 'desc')
            ->orderBy('document_number', 'desc')
            ->paginate(20);
        
        // Get open advances for suggestion
        $openAdvances = $this->advanceService->suggestOpenAdvancesForEmployee($party->id);
        
        return view('accounting.employees.advances.index', compact('party', 'advances', 'openAdvances'));
    }
    
    /**
     * Show form to give advance
     */
    public function create(Party $party)
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        // Validate party is employee
        if ($party->type !== 'employee') {
            abort(404, 'Bu sayfa sadece personeller için kullanılabilir.');
        }
        
        // Validate party belongs to user's company
        if ($party->company_id !== $user->company_id) {
            abort(403, 'Bu cariye erişim yetkiniz yok.');
        }
        
        $branches = Branch::where('company_id', $user->company_id)->get();
        if ($user->branch_id) {
            $branches = Branch::where('id', $user->branch_id)->get();
        }
        
        $cashboxes = \App\Domain\Accounting\Models\Cashbox::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        $bankAccounts = \App\Domain\Accounting\Models\BankAccount::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        // Check if period is locked
        $isLocked = !$this->periodService->isDateInOpenPeriod($user->company_id, now());
        
        return view('accounting.employees.advances.create', compact(
            'party',
            'branches',
            'cashboxes',
            'bankAccounts',
            'isLocked'
        ));
    }
    
    /**
     * Store advance
     */
    public function store(Request $request, Party $party)
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        // Validate party is employee
        if ($party->type !== 'employee') {
            abort(404, 'Bu sayfa sadece personeller için kullanılabilir.');
        }
        
        // Validate party belongs to user's company
        if ($party->company_id !== $user->company_id) {
            abort(403, 'Bu cariye erişim yetkiniz yok.');
        }
        
        $validated = $request->validate([
            'advance_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_source_type' => 'required|in:cash,bank',
            'cashbox_id' => 'required_if:payment_source_type,cash|nullable|exists:cashboxes,id',
            'bank_account_id' => 'required_if:payment_source_type,bank|nullable|exists:bank_accounts,id',
            'due_date' => 'nullable|date|after_or_equal:advance_date',
            'description' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ]);
        
        try {
            $result = $this->advanceService->giveAdvance([
                'company_id' => $user->company_id,
                'branch_id' => $user->branch_id,
                'party_id' => $party->id,
                'advance_date' => $validated['advance_date'],
                'amount' => $validated['amount'],
                'payment_source_type' => $validated['payment_source_type'],
                'cashbox_id' => $validated['cashbox_id'] ?? null,
                'bank_account_id' => $validated['bank_account_id'] ?? null,
                'due_date' => $validated['due_date'] ?? null,
                'description' => $validated['description'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);
            
            return redirect()
                ->route('accounting.employees.advances.index', $party)
                ->with('success', "Avans başarıyla verildi. Belge: {$result['advance_document_number']}");
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }
}
