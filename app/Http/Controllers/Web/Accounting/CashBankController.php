<?php

namespace App\Http\Controllers\Web\Accounting;

use App\Domain\Accounting\Models\Cashbox;
use App\Domain\Accounting\Models\BankAccount;
use App\Domain\Accounting\Services\PaymentService;
use App\Domain\Accounting\Services\ReportService;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashBankController extends Controller
{
    protected PaymentService $paymentService;
    protected ReportService $reportService;
    
    public function __construct(PaymentService $paymentService, ReportService $reportService)
    {
        $this->paymentService = $paymentService;
        $this->reportService = $reportService;
    }
    
    public function index()
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $balances = $this->reportService->getCashBankBalances($user->company_id, $user->branch_id);
        
        // Get all cashboxes and bank accounts for management (not just active)
        $cashboxes = Cashbox::where('company_id', $user->company_id)
            ->when($user->branch_id, fn($q) => $q->where('branch_id', $user->branch_id))
            ->orderBy('name')
            ->get();
        
        $bankAccounts = BankAccount::where('company_id', $user->company_id)
            ->when($user->branch_id, fn($q) => $q->where('branch_id', $user->branch_id))
            ->orderBy('name')
            ->get();
        
        // Get recent payments
        $recentPayments = \App\Domain\Accounting\Models\Payment::where('company_id', $user->company_id)
            ->when($user->branch_id, fn($q) => $q->where('branch_id', $user->branch_id))
            ->where('status', 'confirmed')
            ->orderBy('payment_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->with(['party', 'cashbox', 'bankAccount'])
            ->get();
        
        return view('accounting.cash.index', compact('balances', 'recentPayments', 'cashboxes', 'bankAccounts'));
    }
    
    // Cashbox Management
    public function createCashbox()
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $branches = Branch::where('company_id', $user->company_id)->get();
        if ($user->branch_id) {
            $branches = Branch::where('id', $user->branch_id)->get();
        }
        
        return view('accounting.cash.cashbox-create', compact('branches'));
    }
    
    public function storeCashbox(Request $request)
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'currency' => 'required|string|max:3',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'opening_balance' => 'nullable|numeric',
            'opening_balance_date' => 'nullable|date',
        ]);
        
        $validated['company_id'] = $user->company_id;
        $validated['currency'] = $validated['currency'] ?? 'TRY';
        $validated['is_active'] = $request->has('is_active') ? true : false;
        $validated['is_default'] = $request->has('is_default') ? true : false;
        $validated['opening_balance'] = $validated['opening_balance'] ?? 0;
        
        // If this is set as default, unset others
        if ($validated['is_default']) {
            Cashbox::where('company_id', $user->company_id)
                ->when($user->branch_id, fn($q) => $q->where('branch_id', $user->branch_id))
                ->update(['is_default' => false]);
        }
        
        $cashbox = Cashbox::create($validated);
        
        return redirect()->route('accounting.cash.index')
            ->with('success', 'Kasa başarıyla oluşturuldu.');
    }
    
    public function editCashbox(Cashbox $cashbox)
    {
        $user = Auth::user();
        if ($cashbox->company_id != $user->company_id) {
            abort(403);
        }
        
        $branches = Branch::where('company_id', $user->company_id)->get();
        if ($user->branch_id) {
            $branches = Branch::where('id', $user->branch_id)->get();
        }
        
        return view('accounting.cash.cashbox-edit', compact('cashbox', 'branches'));
    }
    
    public function updateCashbox(Request $request, Cashbox $cashbox)
    {
        $user = Auth::user();
        if ($cashbox->company_id != $user->company_id) {
            abort(403);
        }
        
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'currency' => 'required|string|max:3',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'opening_balance' => 'nullable|numeric',
            'opening_balance_date' => 'nullable|date',
        ]);
        
        $validated['is_active'] = $request->has('is_active') ? true : false;
        $validated['is_default'] = $request->has('is_default') ? true : false;
        
        // If this is set as default, unset others
        if ($validated['is_default']) {
            Cashbox::where('company_id', $user->company_id)
                ->where('id', '!=', $cashbox->id)
                ->when($user->branch_id, fn($q) => $q->where('branch_id', $user->branch_id))
                ->update(['is_default' => false]);
        }
        
        $cashbox->update($validated);
        
        return redirect()->route('accounting.cash.index')
            ->with('success', 'Kasa başarıyla güncellendi.');
    }
    
    // Bank Account Management
    public function createBankAccount()
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $branches = Branch::where('company_id', $user->company_id)->get();
        if ($user->branch_id) {
            $branches = Branch::where('id', $user->branch_id)->get();
        }
        
        return view('accounting.cash.bank-create', compact('branches'));
    }
    
    public function storeBankAccount(Request $request)
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'branch_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:50',
            'iban' => 'nullable|string|max:34',
            'currency' => 'required|string|max:3',
            'account_type' => 'required|in:checking,savings,credit,pos',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'opening_balance' => 'nullable|numeric',
            'opening_balance_date' => 'nullable|date',
        ]);
        
        $validated['company_id'] = $user->company_id;
        $validated['currency'] = $validated['currency'] ?? 'TRY';
        $validated['is_active'] = $request->has('is_active') ? true : false;
        $validated['is_default'] = $request->has('is_default') ? true : false;
        $validated['opening_balance'] = $validated['opening_balance'] ?? 0;
        
        // If this is set as default, unset others
        if ($validated['is_default']) {
            BankAccount::where('company_id', $user->company_id)
                ->when($user->branch_id, fn($q) => $q->where('branch_id', $user->branch_id))
                ->update(['is_default' => false]);
        }
        
        $bankAccount = BankAccount::create($validated);
        
        return redirect()->route('accounting.cash.index')
            ->with('success', 'Banka hesabı başarıyla oluşturuldu.');
    }
    
    public function editBankAccount(BankAccount $bankAccount)
    {
        $user = Auth::user();
        if ($bankAccount->company_id != $user->company_id) {
            abort(403);
        }
        
        $branches = Branch::where('company_id', $user->company_id)->get();
        if ($user->branch_id) {
            $branches = Branch::where('id', $user->branch_id)->get();
        }
        
        return view('accounting.cash.bank-edit', compact('bankAccount', 'branches'));
    }
    
    public function updateBankAccount(Request $request, BankAccount $bankAccount)
    {
        $user = Auth::user();
        if ($bankAccount->company_id != $user->company_id) {
            abort(403);
        }
        
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'branch_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:50',
            'iban' => 'nullable|string|max:34',
            'currency' => 'required|string|max:3',
            'account_type' => 'required|in:checking,savings,credit,pos',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'opening_balance' => 'nullable|numeric',
            'opening_balance_date' => 'nullable|date',
        ]);
        
        $validated['is_active'] = $request->has('is_active') ? true : false;
        $validated['is_default'] = $request->has('is_default') ? true : false;
        
        // If this is set as default, unset others
        if ($validated['is_default']) {
            BankAccount::where('company_id', $user->company_id)
                ->where('id', '!=', $bankAccount->id)
                ->when($user->branch_id, fn($q) => $q->where('branch_id', $user->branch_id))
                ->update(['is_default' => false]);
        }
        
        $bankAccount->update($validated);
        
        return redirect()->route('accounting.cash.index')
            ->with('success', 'Banka hesabı başarıyla güncellendi.');
    }
    
    public function transferForm()
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $cashboxes = Cashbox::where('company_id', $user->company_id)->active()->get();
        $bankAccounts = BankAccount::where('company_id', $user->company_id)->active()->get();
        
        return view('accounting.cash.transfer', compact('cashboxes', 'bankAccounts'));
    }
    
    public function transfer(Request $request)
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        // Normalize empty strings to null for optional fields
        $input = $request->all();
        if (empty($input['from_cashbox_id'])) {
            $input['from_cashbox_id'] = null;
        }
        if (empty($input['from_bank_account_id'])) {
            $input['from_bank_account_id'] = null;
        }
        if (empty($input['to_cashbox_id'])) {
            $input['to_cashbox_id'] = null;
        }
        if (empty($input['to_bank_account_id'])) {
            $input['to_bank_account_id'] = null;
        }
        $request->merge($input);
        
        $validated = $request->validate([
            'from_type' => 'required|in:cashbox,bank',
            'from_cashbox_id' => [
                'required_if:from_type,cashbox',
                'nullable',
                function ($attribute, $value, $fail) use ($user) {
                    if ($value && !Cashbox::where('id', $value)->where('company_id', $user->company_id)->exists()) {
                        $fail('Seçilen kasa geçersiz.');
                    }
                },
            ],
            'from_bank_account_id' => [
                'required_if:from_type,bank',
                'nullable',
                function ($attribute, $value, $fail) use ($user) {
                    if ($value && !BankAccount::where('id', $value)->where('company_id', $user->company_id)->exists()) {
                        $fail('Seçilen banka hesabı geçersiz.');
                    }
                },
            ],
            'to_type' => 'required|in:cashbox,bank',
            'to_cashbox_id' => [
                'required_if:to_type,cashbox',
                'nullable',
                function ($attribute, $value, $fail) use ($user) {
                    if ($value && !Cashbox::where('id', $value)->where('company_id', $user->company_id)->exists()) {
                        $fail('Seçilen hedef kasa geçersiz.');
                    }
                },
            ],
            'to_bank_account_id' => [
                'required_if:to_type,bank',
                'nullable',
                function ($attribute, $value, $fail) use ($user) {
                    if ($value && !BankAccount::where('id', $value)->where('company_id', $user->company_id)->exists()) {
                        $fail('Seçilen hedef banka hesabı geçersiz.');
                    }
                },
            ],
            'amount' => 'required|numeric|min:0.01',
            'transfer_date' => 'required|date',
            'description' => 'nullable|string',
        ]);
        
        try {
            // Create outgoing payment
            $outgoingData = [
                'company_id' => $user->company_id,
                'branch_id' => $user->branch_id,
                'type' => $validated['from_type'] === 'cashbox' ? 'cash_out' : 'bank_out',
                'direction' => 'out',
                'cashbox_id' => $validated['from_type'] === 'cashbox' ? $validated['from_cashbox_id'] : null,
                'bank_account_id' => $validated['from_type'] === 'bank' ? $validated['from_bank_account_id'] : null,
                'to_cashbox_id' => $validated['to_type'] === 'cashbox' ? $validated['to_cashbox_id'] : null,
                'to_bank_account_id' => $validated['to_type'] === 'bank' ? $validated['to_bank_account_id'] : null,
                'payment_date' => $validated['transfer_date'],
                'amount' => $validated['amount'],
                'description' => $validated['description'] ?? 'Virman',
            ];
            
            $outgoing = $this->paymentService->createPayment($outgoingData);
            
            // Create incoming payment
            $incomingData = [
                'company_id' => $user->company_id,
                'branch_id' => $user->branch_id,
                'type' => 'transfer',
                'direction' => 'in',
                'cashbox_id' => $validated['from_type'] === 'cashbox' ? $validated['from_cashbox_id'] : null,
                'bank_account_id' => $validated['from_type'] === 'bank' ? $validated['from_bank_account_id'] : null,
                'to_cashbox_id' => $validated['to_type'] === 'cashbox' ? $validated['to_cashbox_id'] : null,
                'to_bank_account_id' => $validated['to_type'] === 'bank' ? $validated['to_bank_account_id'] : null,
                'payment_date' => $validated['transfer_date'],
                'amount' => $validated['amount'],
                'description' => $validated['description'] ?? 'Virman',
            ];
            
            $incoming = $this->paymentService->createPayment($incomingData);
            
            return redirect()->route('accounting.cash.index')
                ->with('success', 'Virman başarıyla yapıldı.');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
