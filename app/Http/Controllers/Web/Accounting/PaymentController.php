<?php

namespace App\Http\Controllers\Web\Accounting;

use App\Domain\Accounting\Enums\PaymentType;
use App\Domain\Accounting\Models\Payment;
use App\Domain\Accounting\Models\Party;
use App\Domain\Accounting\Services\PaymentService;
use App\Domain\Accounting\Services\PeriodService;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;
    protected PeriodService $periodService;
    
    public function __construct(PaymentService $paymentService, PeriodService $periodService)
    {
        $this->paymentService = $paymentService;
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
            'cashbox_id' => $request->get('cashbox_id'),
            'bank_account_id' => $request->get('bank_account_id'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
            'search' => $request->get('search'),
        ];
        
        $payments = $this->paymentService->listPayments($filters);
        $parties = Party::where('company_id', $user->company_id)->active()->orderBy('name')->get();
        $cashboxes = \App\Domain\Accounting\Models\Cashbox::where('company_id', $user->company_id)->active()->get();
        $bankAccounts = \App\Domain\Accounting\Models\BankAccount::where('company_id', $user->company_id)->active()->get();
        
        return view('accounting.payments.index', compact('payments', 'parties', 'cashboxes', 'bankAccounts'));
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
        $cashboxes = \App\Domain\Accounting\Models\Cashbox::where('company_id', $user->company_id)->active()->get();
        $bankAccounts = \App\Domain\Accounting\Models\BankAccount::where('company_id', $user->company_id)->active()->get();
        
        $partyId = $request->get('party_id');
        
        return view('accounting.payments.create', compact('branches', 'parties', 'cashboxes', 'bankAccounts', 'partyId'));
    }
    
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'type' => 'required|in:' . implode(',', PaymentType::ALL),
            'party_id' => 'nullable|exists:parties,id',
            'cashbox_id' => 'nullable|exists:cashboxes,id',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'to_cashbox_id' => 'nullable|exists:cashboxes,id',
            'to_bank_account_id' => 'nullable|exists:bank_accounts,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'fee_amount' => 'nullable|numeric|min:0',
            'reference_number' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
        
        $validated['company_id'] = $user->company_id;
        
        // Ensure fee_amount is 0 if not provided
        if (!isset($validated['fee_amount']) || $validated['fee_amount'] === null) {
            $validated['fee_amount'] = 0;
        }
        
        try {
            $payment = $this->paymentService->createPayment($validated);
            
            return redirect()->route('accounting.payments.show', $payment)
                ->with('success', 'Ödeme başarıyla kaydedildi.');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }
    
    public function show(Payment $payment)
    {
        $user = Auth::user();
        if ($payment->company_id != $user->company_id) {
            abort(403);
        }
        
        $payment->load(['party', 'cashbox', 'bankAccount', 'toCashbox', 'toBankAccount', 'activeAllocations.document']);
        
        return view('accounting.payments.show', compact('payment'));
    }
    
    public function edit(Payment $payment)
    {
        $user = Auth::user();
        if ($payment->company_id != $user->company_id) {
            abort(403);
        }
        
        if (!$payment->canModify()) {
            return back()->withErrors(['error' => 'Bu ödeme değiştirilemez. Dönem kilitli veya ödeme kapalı.']);
        }
        
        $branches = Branch::where('company_id', $user->company_id)->get();
        $parties = Party::where('company_id', $user->company_id)->active()->orderBy('name')->get();
        $cashboxes = \App\Domain\Accounting\Models\Cashbox::where('company_id', $user->company_id)->active()->get();
        $bankAccounts = \App\Domain\Accounting\Models\BankAccount::where('company_id', $user->company_id)->active()->get();
        
        return view('accounting.payments.edit', compact('payment', 'branches', 'parties', 'cashboxes', 'bankAccounts'));
    }
    
    public function update(Request $request, Payment $payment)
    {
        $user = Auth::user();
        if ($payment->company_id != $user->company_id) {
            abort(403);
        }
        
        // Check period lock
        if ($payment->isInLockedPeriod()) {
            return back()->withErrors(['error' => 'Bu ödeme kilitli bir dönemde. Düzenleme yapılamaz. Ters kayıt kullanın.']);
        }
        
        // Check if payment can be modified
        if (!$payment->canModify()) {
            return back()->withErrors(['error' => 'Bu ödeme değiştirilemez. Dağıtımı olan ödemeler değiştirilemez.']);
        }
        
        $validated = $request->validate([
            'reference_number' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
        
        try {
            $payment = $this->paymentService->updatePayment($payment, $validated);
            
            return redirect()->route('accounting.payments.show', $payment)
                ->with('success', 'Ödeme başarıyla güncellendi.');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }
    
    public function reverse(Request $request, Payment $payment)
    {
        $user = Auth::user();
        if ($payment->company_id != $user->company_id) {
            abort(403);
        }
        
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);
        
        try {
            $reversalPayment = $this->paymentService->reversePayment($payment, $validated['reason'] ?? null);
            
            return redirect()->route('accounting.payments.show', $reversalPayment)
                ->with('success', 'Ters kayıt oluşturuldu.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
    
    public function cancel(Request $request, Payment $payment)
    {
        $user = Auth::user();
        if ($payment->company_id != $user->company_id) {
            abort(403);
        }
        
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);
        
        try {
            $payment = $this->paymentService->cancelPayment($payment, $validated['reason'] ?? null);
            
            return redirect()->route('accounting.payments.show', $payment)
                ->with('success', 'Ödeme iptal edildi.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
