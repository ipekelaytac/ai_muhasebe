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
        $documentId = $request->get('document_id');
        $suggestedAmount = $request->get('suggested_amount');
        
        // Get selected document if document_id is provided
        $selectedDocument = null;
        if ($documentId) {
            $selectedDocument = \App\Domain\Accounting\Models\Document::where('company_id', $user->company_id)
                ->where('id', $documentId)
                ->first();
            
            // If document found, use its party_id if party_id not provided
            if ($selectedDocument && !$partyId) {
                $partyId = $selectedDocument->party_id;
            }
        }
        
        // Get open overtime documents for the selected party (if any)
        $openOvertimes = collect([]);
        if ($partyId) {
            $openOvertimes = \App\Domain\Accounting\Models\Document::where('company_id', $user->company_id)
                ->where('party_id', $partyId)
                ->where('type', \App\Domain\Accounting\Enums\DocumentType::OVERTIME_DUE)
                ->where(function($q) {
                    $q->where('status', 'pending')
                      ->orWhere('status', 'partial');
                })
                ->whereRaw('total_amount > COALESCE((SELECT SUM(amount) FROM payment_allocations WHERE document_id = documents.id AND status = "active"), 0)')
                ->orderBy('document_date', 'desc')
                ->get();
        }
        
        // Get open documents for allocation (all types, not just overtime)
        $openDocuments = collect([]);
        if ($partyId) {
            $openDocuments = \App\Domain\Accounting\Models\Document::where('company_id', $user->company_id)
                ->where('party_id', $partyId)
                ->where(function($q) {
                    $q->where('status', 'pending')
                      ->orWhere('status', 'partial');
                })
                ->whereRaw('total_amount > COALESCE((SELECT SUM(amount) FROM payment_allocations WHERE document_id = documents.id AND status = "active"), 0)')
                ->orderBy('due_date', 'asc')
                ->orderBy('document_date', 'asc')
                ->get();
        }
        
        return view('accounting.payments.create', compact(
            'branches', 
            'parties', 
            'cashboxes', 
            'bankAccounts', 
            'partyId', 
            'openOvertimes',
            'openDocuments',
            'selectedDocument',
            'documentId',
            'suggestedAmount'
        ));
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
            'allocation_document_id' => 'nullable|exists:documents,id',
            'allocation_amount' => 'nullable|numeric|min:0.01',
        ]);
        
        $validated['company_id'] = $user->company_id;
        
        // Ensure fee_amount is 0 if not provided
        if (!isset($validated['fee_amount']) || $validated['fee_amount'] === null) {
            $validated['fee_amount'] = 0;
        }
        
        try {
            $payment = $this->paymentService->createPayment($validated);
            
            // If allocation_document_id is provided, automatically allocate payment to that document
            if ($request->has('allocation_document_id') && $request->allocation_document_id) {
                try {
                    $allocationService = app(\App\Domain\Accounting\Services\AllocationService::class);
                    $amount = min($payment->amount, $request->get('allocation_amount', $payment->amount));
                    
                    $allocationService->allocate($payment, [
                        [
                            'document_id' => $request->allocation_document_id,
                            'amount' => $amount,
                        ]
                    ]);
                    
                    // If coming from payroll context, redirect back to payroll item page
                    if ($request->has('context') && $request->context === 'payroll' && $request->has('payroll_item_id')) {
                        return redirect()->route('admin.payroll.item', $request->payroll_item_id)
                            ->with('success', 'Mesai ödemesi başarıyla kaydedildi ve belgeye dağıtıldı.');
                    }
                    
                    return redirect()->route('accounting.payments.show', $payment)
                        ->with('success', 'Ödeme başarıyla kaydedildi ve belgeye dağıtıldı.');
                } catch (\Exception $allocationError) {
                    // Payment created but allocation failed - still redirect but show warning
                    \Log::warning("Payment created but allocation failed: " . $allocationError->getMessage(), [
                        'payment_id' => $payment->id,
                        'document_id' => $request->allocation_document_id,
                    ]);
                    
                    return redirect()->route('accounting.payments.show', $payment)
                        ->with('warning', 'Ödeme kaydedildi ancak belgeye dağıtım yapılamadı. Lütfen manuel olarak dağıtım yapın.');
                }
            }
            
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
